<?php

namespace GP247\Front\Library;

use GP247\Core\Models\AdminStore;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use function Illuminate\Support\defer;

/**
 * IndexNow (SEO phase 2): report changed URLs to api.indexnow.org after the response.
 * Off by default (`seo.indexnow_enabled`); the key is per store (`seo.indexnow_key`).
 * URLs are absolute on the store's own domain, collected per request and sent as one
 * batch per store with `defer()` — no queue worker (NFR-AVAIL-001). Failures are only
 * logged: a save never fails or waits for IndexNow (RISK-OPS-seo-indexnow-delay).
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-indexnow
 * @aidlc-adr ADR-seo-url-lifecycle
 */
class IndexNow
{
    /** Fixed endpoint — never taken from input. */
    public const ENDPOINT = 'https://api.indexnow.org/indexnow';

    public const CONFIG_ENABLED = 'seo.indexnow_enabled';

    public const CONFIG_KEY = 'seo.indexnow_key';

    public const KEY_PATTERN = '/^[a-f0-9]{32}$/';

    private const TIMEOUT_SECONDS = 3;

    /** IndexNow accepts at most this many URLs per request. */
    private const MAX_URLS = 10000;

    /** @var array<string, array<string, true>> store id => absolute URL set */
    private static array $pending = [];

    /** Name of the deferred send (a named callback is registered once per request). */
    private const DEFER_NAME = 'gp247-indexnow';

    /**
     * New random key (32 lowercase hex characters, CSPRNG).
     *
     * @return string
     */
    public static function generateKey(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Key of a store, '' when not set or malformed.
     *
     * @param int|string $storeId
     * @return string
     */
    public static function key($storeId): string
    {
        $key = trim((string) gp247_config(self::CONFIG_KEY, $storeId, ''));

        return preg_match(self::KEY_PATTERN, $key) ? $key : '';
    }

    /**
     * Whether IndexNow is on for a store (switch on and a valid key).
     *
     * @param int|string $storeId
     * @return bool
     */
    public static function enabled($storeId): bool
    {
        return (string) gp247_config(self::CONFIG_ENABLED, $storeId, '0') === '1' && self::key($storeId) !== '';
    }

    /**
     * Scheme + host of the store's storefront (its own domain, else APP_URL).
     *
     * @param int|string $storeId
     * @return string e.g. `https://shop.example.com`
     */
    public static function baseUrl($storeId): string
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';
        $domain = trim((string) optional(AdminStore::find($storeId))->domain);
        if ($domain === '') {
            return $appUrl;
        }
        $domain = preg_replace('#^https?://#i', '', rtrim($domain, '/'));

        return $scheme . '://' . $domain;
    }

    /**
     * Add paths of a store to this request's batch and schedule the send once.
     *
     * @param int|string    $storeId
     * @param array<string> $paths   Storefront paths starting with `/`.
     * @return void
     */
    public static function queue($storeId, array $paths): void
    {
        if ($paths === [] || !self::enabled($storeId)) {
            return;
        }
        $base = self::baseUrl($storeId);
        foreach ($paths as $path) {
            self::$pending[(string) $storeId][$base . $path] = true;
        }
        // WHY named + always: registered once per request, and still runs after a 4xx/5xx
        // response so a long-running worker never keeps an unsent batch.
        defer(static fn () => self::flush(), self::DEFER_NAME, true);
    }

    /**
     * Send every pending batch (one POST per store); errors are logged only.
     *
     * @return void
     */
    public static function flush(): void
    {
        $pending = self::$pending;
        self::reset();
        foreach ($pending as $storeId => $urls) {
            self::send((string) $storeId, array_slice(array_keys($urls), 0, self::MAX_URLS));
        }
    }

    /**
     * Forget pending URLs (tests; start of a new unit of work).
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$pending = [];
    }

    /**
     * @param string        $storeId
     * @param array<string> $urls
     * @return void
     */
    private static function send(string $storeId, array $urls): void
    {
        $key = self::key($storeId);
        if ($urls === [] || $key === '') {
            return;
        }
        $base = self::baseUrl($storeId);
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)->acceptJson()->post(self::ENDPOINT, [
                'host' => parse_url($base, PHP_URL_HOST),
                'key' => $key,
                'keyLocation' => $base . '/' . $key . '.txt',
                'urlList' => $urls,
            ]);
            if (!$response->successful()) {
                Log::warning('IndexNow: submission refused', ['store' => $storeId, 'status' => $response->status()]);
            }
        } catch (\Throwable $e) {
            Log::warning('IndexNow: submission failed', ['store' => $storeId, 'error' => $e->getMessage()]);
        }
    }
}
