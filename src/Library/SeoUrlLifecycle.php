<?php

namespace GP247\Front\Library;

use GP247\Core\Models\AdminLanguage;
use GP247\Front\Models\FrontRedirect;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * URL lifecycle of storefront content (SEO phase 2): an alias change leaves a 301 from
 * the old address to the new one, and saved/deleted content is reported to IndexNow.
 * Driven by model events (watch()) so the admin screens, the REST API, DataImport and
 * MCP all behave alike. Never breaks a save: errors are logged
 * (ADR-seo-url-lifecycle, RISK-OPS-seo-auto-redirect-misfire).
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-alias-auto-redirect, US-SEO-indexnow
 * @aidlc-adr ADR-seo-url-lifecycle
 */
class SeoUrlLifecycle
{
    /** Per-store switch of automatic redirects; on unless set to `0`. */
    public const CONFIG_AUTO_REDIRECT = 'seo.auto_redirect_alias';

    /**
     * Hook a content model: alias changes and saves/deletes of `$class` follow `$type`'s
     * route (registry `seo_content_types.<type>.route`).
     *
     * @param class-string $class Eloquent model class.
     * @param string       $type  Registered SEO content type.
     * @return void
     */
    public static function watch(string $class, string $type): void
    {
        // WHY `updated`: written only once the row is saved — a failed or cancelled update
        // must not leave a 301 hiding the still-live old address.
        $class::updated(static fn ($model) => self::guard(static fn () => self::onUpdated($type, $model)));
        $class::saved(static fn ($model) => self::guard(static fn () => self::onSaved($type, $model)));
        $class::deleted(static fn ($model) => self::guard(static fn () => self::onDeleted($type, $model)));
    }

    /**
     * Whether automatic redirects are on for a store.
     *
     * @param int|string $storeId
     * @return bool
     */
    public static function autoRedirectEnabled($storeId): bool
    {
        return (string) gp247_config(self::CONFIG_AUTO_REDIRECT, $storeId, '1') !== '0';
    }

    /**
     * Record an alias change: one 301 per language from the old path to the new one.
     *
     * @param string     $type     SEO content type.
     * @param int|string $storeId  Store of the content.
     * @param string     $oldAlias
     * @param string     $newAlias
     * @return void
     */
    public static function aliasChanged(string $type, $storeId, string $oldAlias, string $newAlias): void
    {
        if ($oldAlias === '' || $oldAlias === $newAlias || !self::autoRedirectEnabled($storeId)) {
            return;
        }
        $old = self::pathsFor($type, $oldAlias);
        $new = self::pathsFor($type, $newAlias);
        foreach ($old as $lang => $from) {
            if (isset($new[$lang])) {
                self::applyRedirect($storeId, $from, $new[$lang]);
            }
        }
    }

    /**
     * Storefront paths of a content alias, one per active language when the language
     * is part of the URL.
     *
     * @param string $type
     * @param string $alias
     * @return array<string, string> language (or `_`) => path
     */
    public static function pathsFor(string $type, string $alias): array
    {
        $route = (string) (SeoContentMeta::types()[$type]['route'] ?? '');
        if ($route === '' || $alias === '' || !Route::has($route)) {
            return [];
        }
        $langs = GP247_SEO_LANG ? array_keys(AdminLanguage::getListActive()->all()) : [null];
        $paths = [];
        foreach ($langs as $lang) {
            $path = (string) parse_url(gp247_route_front($route, ['alias' => $alias, 'lang' => $lang]), PHP_URL_PATH);
            // WHY: a route that could not be built falls back to url('#…'), whose path is not the
            // content's; the generated path is URL-encoded (ja/CJK aliases), the alias is not.
            if ($path !== '' && (str_contains($path, $alias) || str_contains(rawurldecode($path), $alias))) {
                $paths[$lang ?? '_'] = $path;
            }
        }

        return $paths;
    }

    /**
     * Content wins over redirects, chains are flattened, then old → new is written.
     *
     * @param int|string $storeId
     * @param string     $from
     * @param string     $to
     * @return void
     */
    private static function applyRedirect($storeId, string $from, string $to): void
    {
        // WHY: only same-site paths — `//host` would be an open redirect.
        if ($from === $to || !self::isLocalPath($from) || !self::isLocalPath($to)) {
            return;
        }
        $store = (string) $storeId;
        self::releasePath($store, $to);
        FrontRedirect::where('store_id', $store)->where('to', $from)->where('status', 1)->update(['to' => $to]);

        $existing = FrontRedirect::where('store_id', $store)->where('from', $from)->first();
        $values = ['to' => $to, 'code' => 301, 'status' => 1, 'source' => FrontRedirect::SOURCE_AUTO];
        if ($existing !== null) {
            $existing->update($values);

            return;
        }
        FrontRedirect::create($values + ['from' => $from, 'store_id' => $store]);
    }

    /**
     * @param string $type
     * @param object $model Eloquent content model.
     * @return void
     */
    private static function onUpdated(string $type, $model): void
    {
        if (!$model->wasChanged('alias')) {
            return;
        }
        $old = (string) $model->getOriginal('alias');
        $new = (string) $model->alias;
        $storeId = self::storeOf($model);
        self::aliasChanged($type, $storeId, $old, $new);
        // The old address changed too: IndexNow should re-crawl it (now a 301).
        IndexNow::queue($storeId, array_values(self::pathsFor($type, $old)));
    }

    /**
     * @param string $type
     * @param object $model
     * @return void
     */
    private static function onSaved(string $type, $model): void
    {
        $storeId = self::storeOf($model);
        if ($model->wasRecentlyCreated && self::autoRedirectEnabled($storeId)) {
            foreach (self::pathsFor($type, (string) $model->alias) as $path) {
                self::releasePath((string) $storeId, $path);
            }
        }
        if (!IndexNow::enabled($storeId) || (isset($model->status) && (int) $model->status === 0)) {
            return;
        }
        if ((SeoContentMeta::for($type, $model->getKey())['noindex'] ?? false) === true) {
            return;
        }
        IndexNow::queue($storeId, array_values(self::pathsFor($type, (string) $model->alias)));
    }

    /**
     * Content now lives at `$path`: switch off the store's enabled redirects from it,
     * otherwise FrontRedirectMiddleware (before the route) would hide the content.
     *
     * @param string $store
     * @param string $path
     * @return void
     */
    private static function releasePath(string $store, string $path): void
    {
        $hidden = FrontRedirect::where('store_id', $store)->where('from', $path)->where('status', 1)->pluck('id');
        if ($hidden->isEmpty()) {
            return;
        }
        FrontRedirect::whereIn('id', $hidden)->update(['status' => 0]);
        Log::info('SEO: redirect switched off because content now lives at its address', ['store' => $store, 'path' => $path]);
    }

    /**
     * @param string $path
     * @return bool True for a path of this site (`/x`, never `//host`).
     */
    private static function isLocalPath(string $path): bool
    {
        return (bool) preg_match('#^/(?!/)#', $path);
    }

    /**
     * @param string $type
     * @param object $model
     * @return void
     */
    private static function onDeleted(string $type, $model): void
    {
        IndexNow::queue(self::storeOf($model), array_values(self::pathsFor($type, (string) $model->alias)));
    }

    /**
     * @param object $model
     * @return int|string
     */
    private static function storeOf($model)
    {
        $store = $model->store_id ?? null;

        return ($store === null || $store === '') ? config('app.storeId') : $store;
    }

    /**
     * Run a lifecycle step without ever breaking the save that triggered it.
     *
     * @param callable $step
     * @return void
     */
    private static function guard(callable $step): void
    {
        try {
            $step();
        } catch (\Throwable $e) {
            Log::warning('SEO URL lifecycle step failed', ['error' => $e->getMessage()]);
        }
    }
}
