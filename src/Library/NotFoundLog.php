<?php

namespace GP247\Front\Library;

use GP247\Front\Models\FrontNotFoundLog;
use GP247\Front\Support\SeoUrlLifecycleInstaller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Storefront 404 log (SEO phase 2): which addresses fail, how often, from where.
 * Off by default (`seo.log_404`); bounded per store and cleaned while writing — no cron
 * (NFR-AVAIL-001, RISK-OPS-seo-not-found-log-flood).
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-not-found-log
 * @aidlc-adr ADR-seo-url-lifecycle
 */
class NotFoundLog
{
    public const CONFIG_KEY = 'seo.log_404';

    /** Rows kept per store; the least recently seen go first. */
    public const MAX_ROWS = 1000;

    /** Rows not seen for this many days are removed. */
    public const RETENTION_DAYS = 90;

    /** One write in this many also removes expired rows. */
    private const PRUNE_ODDS = 100;

    private const MAX_LENGTH = 500;

    /** First path segments that are never storefront pages. */
    private const SKIP_PREFIXES = ['api', 'GP247', 'vendor', 'storage', 'livewire', 'build', 'filemanager'];

    /** File extensions of assets. */
    private const ASSET_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'css', 'js', 'map', 'woff', 'woff2', 'ttf', 'eot', 'avif'];

    /** Extensions/dot-files only vulnerability scanners ask for (RISK-OPS-seo-not-found-log-flood). */
    private const PROBE_EXTENSIONS = ['php', 'asp', 'aspx', 'jsp', 'cgi', 'env', 'sql', 'bak', 'old', 'ini', 'log', 'git', 'yml', 'yaml', 'xml~', 'swp'];

    /** Memo of "the table exists" for this process. */
    private static ?bool $tableReady = null;

    /**
     * Whether a 404 response to this request belongs in the log.
     *
     * @param Request $request
     * @return bool
     */
    public static function shouldRecord(Request $request): bool
    {
        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return false;
        }
        $path = trim($request->getPathInfo(), '/');
        $first = strtolower((string) strtok($path, '/'));
        $admin = strtolower(defined('GP247_ADMIN_PREFIX') ? GP247_ADMIN_PREFIX : 'gp247_admin');
        if ($first === $admin || in_array($first, array_map('strtolower', self::SKIP_PREFIXES), true)) {
            return false;
        }
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $base = strtolower((string) basename($path));

        return !in_array($extension, self::ASSET_EXTENSIONS, true)
            && !in_array($extension, self::PROBE_EXTENSIONS, true)
            && !str_starts_with($base, '.');
    }

    /**
     * Whether the log is switched on for a store.
     *
     * @param int|string $storeId
     * @return bool
     */
    public static function enabled($storeId): bool
    {
        return (string) gp247_config(self::CONFIG_KEY, $storeId, '0') === '1';
    }

    /**
     * Count one 404 of a path (no query string) for a store.
     *
     * @param int|string  $storeId
     * @param string      $path    Request path, starting with `/`.
     * @param string|null $referer
     * @return void
     */
    public static function record($storeId, string $path, ?string $referer): void
    {
        if (!self::tableReady()) {
            return;
        }
        $path = mb_substr(mb_scrub('/' . ltrim($path, '/'), 'UTF-8'), 0, self::MAX_LENGTH);
        $referer = self::cleanReferer($referer);
        $hash = sha1($path);
        $now = now();

        $updated = FrontNotFoundLog::where('store_id', (string) $storeId)->where('path_hash', $hash)
            ->update(array_filter([
                'hits' => \Illuminate\Support\Facades\DB::raw('hits + 1'),
                'last_seen_at' => $now,
                'referer' => $referer,
            ], static fn ($v) => $v !== null));
        if ($updated === 0) {
            FrontNotFoundLog::insertOrIgnore([
                'store_id' => (string) $storeId, 'path' => $path, 'path_hash' => $hash, 'hits' => 1,
                'referer' => $referer, 'first_seen_at' => $now, 'last_seen_at' => $now,
            ]);
            self::enforceCap($storeId);
        }
        if (random_int(1, self::PRUNE_ODDS) === 1) {
            self::prune($storeId);
        }
    }

    /**
     * Referrer without query string or fragment (they may carry search terms, emails,
     * tokens), valid UTF-8, at most MAX_LENGTH characters; null when unusable.
     *
     * @param string|null $referer
     * @return string|null
     */
    private static function cleanReferer(?string $referer): ?string
    {
        if ($referer === null || trim($referer) === '') {
            return null;
        }
        $parts = parse_url(mb_scrub($referer, 'UTF-8'));
        if (!is_array($parts) || empty($parts['host'])) {
            return null;
        }
        $clean = ($parts['scheme'] ?? 'http') . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '') . ($parts['path'] ?? '');

        return mb_substr($clean, 0, self::MAX_LENGTH);
    }

    /**
     * Remove rows of a store not seen for RETENTION_DAYS.
     *
     * @param int|string $storeId
     * @return void
     */
    public static function prune($storeId): void
    {
        if (!self::tableReady()) {
            return;
        }
        FrontNotFoundLog::where('store_id', (string) $storeId)
            ->where('last_seen_at', '<', now()->subDays(self::RETENTION_DAYS))
            ->delete();
    }

    /**
     * Keep at most MAX_ROWS rows for a store, dropping the least recently seen.
     *
     * @param int|string $storeId
     * @return void
     */
    private static function enforceCap($storeId): void
    {
        $count = FrontNotFoundLog::where('store_id', (string) $storeId)->count();
        if ($count <= self::MAX_ROWS) {
            return;
        }
        $ids = FrontNotFoundLog::where('store_id', (string) $storeId)
            ->orderBy('last_seen_at')->orderBy('id')
            ->limit($count - self::MAX_ROWS)->pluck('id');
        FrontNotFoundLog::whereIn('id', $ids)->delete();
    }

    /**
     * @return bool Whether the log table exists (memoized once found).
     */
    public static function tableReady(): bool
    {
        if (self::$tableReady !== true) {
            try {
                self::$tableReady = Schema::connection(GP247_DB_CONNECTION)
                    ->hasTable(GP247_DB_PREFIX . SeoUrlLifecycleInstaller::TABLE_NOT_FOUND) ?: null;
            } catch (\Throwable $e) {
                Log::warning('404 log: table check failed', ['error' => $e->getMessage()]);

                return false;
            }
        }

        return self::$tableReady === true;
    }
}
