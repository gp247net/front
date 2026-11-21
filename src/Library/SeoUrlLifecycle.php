<?php

namespace GP247\Front\Library;

use GP247\Core\Models\AdminLanguage;
use GP247\Front\Models\FrontPage;
use GP247\Front\Models\FrontRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * URL lifecycle of storefront content (SEO phase 2): an alias change leaves a 301 from
 * the old address to the new one, and saved/deleted content is reported to IndexNow.
 * Driven by model events (watch()) so the admin screens, the REST API, DataImport and
 * MCP all behave alike. Never breaks a save: errors are logged
 * (ADR-seo-url-lifecycle, RISK-OPS-seo-auto-redirect-misfire).
 *
 * Packages that bring content from another platform write a 301 from its old
 * addresses through redirectLegacyPath(), under the same rules.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-alias-auto-redirect, US-SEO-indexnow, US-SEO-legacy-path-redirect, US-SEO-plugin-redirect-source
 * @aidlc-adr ADR-seo-url-lifecycle
 */
class SeoUrlLifecycle
{
    /** Per-store switch of automatic redirects; on unless set to `0`. */
    public const CONFIG_AUTO_REDIRECT = 'seo.auto_redirect_alias';

    /** checkLegacyPath(): the redirect can be written. */
    public const LEGACY_OK = 'ok';

    /** checkLegacyPath(): not a safe path of this site (`//host`, scheme, control characters, `..`). */
    public const LEGACY_NOT_LOCAL = 'not_local';

    /** checkLegacyPath(): the old path already is the content's address. */
    public const LEGACY_SAME_AS_TARGET = 'same_as_target';

    /** checkLegacyPath(): the old path is the address of content of this site. */
    public const LEGACY_OWNED_BY_CONTENT = 'owned_by_content';

    /** checkLegacyPath(): the old path is a fixed page of this site (cart, login, admin…). */
    public const LEGACY_RESERVED_ROUTE = 'reserved_route';

    /** checkLegacyPath(): the same 301 is already in place (writing it again changes nothing). */
    public const LEGACY_ALREADY_REDIRECTED = 'already_redirected';

    /** checkLegacyPath(): another redirect (hand-made, switched off, or pointing elsewhere) already starts there — never overwritten. */
    public const LEGACY_REDIRECT_EXISTS = 'redirect_exists';

    /** checkLegacyPath(): unknown content type, type without route, empty alias or old database. */
    public const LEGACY_UNSUPPORTED_TYPE = 'unsupported_type';

    /** Longest path the `from` column holds. */
    private const MAX_PATH_LENGTH = 500;

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
     * Whether a 301 from an address of a previous platform to a content of this site
     * can be written, without writing it (for an import preview).
     *
     * @param int|string $storeId    Store of the content.
     * @param string     $legacyPath Old path (`/products/a`), never a full URL.
     * @param string     $type       Registered SEO content type with a `route`.
     * @param string     $alias      Alias of the content on this site.
     * @return string One of the LEGACY_* reasons.
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-legacy-path-redirect
     * @aidlc-adr ADR-seo-url-lifecycle
     */
    public static function checkLegacyPath($storeId, string $legacyPath, string $type, string $alias): string
    {
        return self::inspectLegacyPath($storeId, $legacyPath, $type, $alias)['reason'];
    }

    /**
     * Write a 301 (source `import`) from an address of a previous platform to the
     * content's address in the store's default language, under the same rules as the
     * automatic redirect: content and fixed pages win, chains are flattened, one row
     * per old path. Safe to call again with the same data. Database errors are thrown
     * to the caller, who is inside its own transaction.
     *
     * @param int|string $storeId    Store of the content.
     * @param string     $legacyPath Old path (`/products/a`), never a full URL.
     * @param string     $type       Registered SEO content type with a `route`.
     * @param string     $alias      Alias of the content on this site.
     * @return int|null Redirect id, or null when skipped (see checkLegacyPath()).
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-legacy-path-redirect
     * @aidlc-adr ADR-seo-url-lifecycle
     */
    public static function redirectLegacyPath($storeId, string $legacyPath, string $type, string $alias): ?int
    {
        $check = self::inspectLegacyPath($storeId, $legacyPath, $type, $alias);
        if ($check['reason'] !== self::LEGACY_OK && $check['reason'] !== self::LEGACY_ALREADY_REDIRECTED) {
            return null;
        }

        return self::writeRedirect((string) $storeId, $check['from'], $check['to'], FrontRedirect::SOURCE_IMPORT);
    }

    /**
     * Whether a 301 from a path to a content of this site can be written, without writing
     * it. Same rules and reasons as checkLegacyPath(); for packages and plugins writing
     * redirects under their own source (redirectToContent()).
     *
     * @param int|string $storeId Store of the content.
     * @param string     $from    Path to send away (`/old-name`), never a full URL.
     * @param string     $type    Registered SEO content type with a `route`.
     * @param string     $alias   Alias of the content on this site.
     * @return string One of the LEGACY_* reasons.
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-plugin-redirect-source
     * @aidlc-adr ADR-seo-url-lifecycle
     */
    public static function checkContentRedirect($storeId, string $from, string $type, string $alias): string
    {
        return self::inspectLegacyPath($storeId, $from, $type, $alias)['reason'];
    }

    /**
     * Write a 301 from a path to the content's address under a source a package or
     * plugin registered in `gp247-config.front.seo_redirect_sources`, with the rules of
     * redirectLegacyPath(). An identical redirect that already exists is confirmed as
     * it is (its source stays with whoever made it).
     *
     * @param int|string $storeId Store of the content.
     * @param string     $from    Path to send away (`/old-name`), never a full URL.
     * @param string     $type    Registered SEO content type with a `route`.
     * @param string     $alias   Alias of the content on this site.
     * @param string     $source  Registered source name.
     * @return int|null Redirect id, or null when the source is not registered or the
     *                  path is skipped (see checkContentRedirect()).
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-plugin-redirect-source
     * @aidlc-adr ADR-seo-url-lifecycle
     */
    public static function redirectToContent($storeId, string $from, string $type, string $alias, string $source): ?int
    {
        if (!FrontRedirect::isRegisteredSource($source)) {
            Log::warning('Redirect refused: source is not registered in seo_redirect_sources', ['source' => $source]);

            return null;
        }
        $check = self::inspectLegacyPath($storeId, $from, $type, $alias);
        if ($check['reason'] === self::LEGACY_ALREADY_REDIRECTED) {
            $existing = FrontRedirect::where('store_id', (string) $storeId)->where('from', $check['from'])->value('id');

            return $existing !== null ? (int) $existing : null;
        }
        if ($check['reason'] !== self::LEGACY_OK) {
            return null;
        }

        return self::writeRedirect((string) $storeId, $check['from'], $check['to'], $source);
    }

    /**
     * Remove a redirect written by redirectLegacyPath() while nobody touched it since:
     * still source `import` (a hand edit makes it `manual`), still pointing at
     * `$expectedTo` (a later rename re-points it) and created at or after
     * `$createdSince` (an older row belongs to an earlier import).
     *
     * @param int                $id           Redirect id returned by redirectLegacyPath().
     * @param string             $expectedTo   Target path when it was written.
     * @param \DateTimeInterface $createdSince Start of the import that wrote it.
     * @return bool True when the row was removed.
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-legacy-path-redirect
     * @aidlc-adr ADR-seo-url-lifecycle
     */
    public static function removeImportedRedirect(int $id, string $expectedTo, \DateTimeInterface $createdSince): bool
    {
        return FrontRedirect::whereKey($id)
            ->where('source', FrontRedirect::SOURCE_IMPORT)
            ->where('to', $expectedTo)
            ->where('created_at', '>=', $createdSince)
            ->delete() === 1;
    }

    /**
     * @param int|string $storeId
     * @param string     $legacyPath
     * @param string     $type
     * @param string     $alias
     * @return array{reason: string, from: string, to: string}
     */
    private static function inspectLegacyPath($storeId, string $legacyPath, string $type, string $alias): array
    {
        $result = ['reason' => self::LEGACY_OK, 'from' => '', 'to' => ''];
        if (!self::isSafeLegacyPath($legacyPath)) {
            return ['reason' => self::LEGACY_NOT_LOCAL] + $result;
        }
        $to = self::defaultLanguagePath($type, $alias, $storeId);
        if ($to === null || !self::hasSourceColumn()) {
            return ['reason' => self::LEGACY_UNSUPPORTED_TYPE] + $result;
        }
        $from = FrontRedirect::normalizePath($legacyPath);
        if ($from === FrontRedirect::normalizePath($to)) {
            return ['reason' => self::LEGACY_SAME_AS_TARGET] + $result;
        }
        $owner = self::routeOwner((string) $storeId, $from);
        if ($owner !== null) {
            return ['reason' => $owner] + $result;
        }

        $existing = FrontRedirect::where('store_id', (string) $storeId)->where('from', $from)->first(['to', 'status', 'code']);
        if ($existing === null) {
            return ['reason' => self::LEGACY_OK, 'from' => $from, 'to' => $to];
        }
        // WHY: an existing rule from this path is the owner's (or an earlier line's) decision — a hand-made
        // target, a rule switched off on purpose; an import only confirms the very same 301, never rewrites it.
        $same = (int) $existing->status === 1 && (int) $existing->code === 301 && (string) $existing->to === $to;

        return $same
            ? ['reason' => self::LEGACY_ALREADY_REDIRECTED, 'from' => $from, 'to' => $to]
            : ['reason' => self::LEGACY_REDIRECT_EXISTS] + $result;
    }

    /**
     * @return bool Whether the redirect table has the `source` column (front database upgraded).
     */
    private static function hasSourceColumn(): bool
    {
        static $has = null;

        return $has ??= Schema::connection(GP247_DB_CONNECTION)->hasColumn(GP247_DB_PREFIX . 'front_redirects', 'source');
    }

    /**
     * @param string $path
     * @return bool A same-site path with no control character, backslash or dot segment.
     */
    private static function isSafeLegacyPath(string $path): bool
    {
        if ($path === '' || strlen($path) > self::MAX_PATH_LENGTH || !self::isLocalPath($path) || strpbrk($path, '?#') !== false) {
            return false;
        }
        $decoded = rawurldecode($path);
        if (preg_match('/[\x00-\x1F\x7F\\\\]/', $path . $decoded)) {
            return false;
        }
        foreach (explode('/', $decoded) as $segment) {
            if ($segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string     $type
     * @param string     $alias
     * @param int|string $storeId
     * @return string|null Content path in the store's default language (one target per old path).
     */
    private static function defaultLanguagePath(string $type, string $alias, $storeId): ?string
    {
        $paths = self::pathsFor($type, $alias);
        if ($paths === []) {
            return null;
        }
        $lang = (string) gp247_store_info('language', null, $storeId);

        return $paths[$lang] ?? $paths['_'] ?? reset($paths);
    }

    /**
     * Who already answers `$path` on this site. FrontRedirectMiddleware runs before the
     * route, so a redirect from such a path would take the page over.
     *
     * @param string $storeId
     * @param string $path Normalised path.
     * @return string|null LEGACY_OWNED_BY_CONTENT, LEGACY_RESERVED_ROUTE or null (free).
     */
    private static function routeOwner(string $storeId, string $path): ?string
    {
        try {
            $route = Route::getRoutes()->match(Request::create($path, 'GET'));
        } catch (MethodNotAllowedHttpException $e) {
            // A POST-only page (newsletter, checkout step): the redirect middleware runs for every method.
            return self::LEGACY_RESERVED_ROUTE;
        } catch (HttpExceptionInterface $e) {
            return null;
        }
        $name = (string) $route->getName();
        $contentRoutes = array_column(SeoContentMeta::types(), 'route');
        if ($name !== '' && in_array($name, $contentRoutes, true)) {
            // WHY: `{alias}.html` of CMS pages also catches every old `/x.html` address
            // (Magento); only a page that exists owns it. Shop/plugin detail routes have
            // their own prefix and are kept as content addresses.
            if ($name === 'front.page.detail') {
                return self::pageExists($storeId, (string) $route->parameter('alias')) ? self::LEGACY_OWNED_BY_CONTENT : null;
            }

            return self::LEGACY_OWNED_BY_CONTENT;
        }

        return self::LEGACY_RESERVED_ROUTE;
    }

    /**
     * @param string $storeId
     * @param string $alias
     * @return bool
     */
    private static function pageExists(string $storeId, string $alias): bool
    {
        return FrontPage::where('store_id', $storeId)->where('alias', rawurldecode($alias))->exists();
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
        self::writeRedirect((string) $storeId, FrontRedirect::normalizePath($from), $to, FrontRedirect::SOURCE_AUTO);
    }

    /**
     * Free `$to` (content wins), point redirects aimed at `$from` straight at `$to`,
     * then upsert `$from` ⇒ `$to` as an enabled 301.
     *
     * @param string $store
     * @param string $from   Normalised source path.
     * @param string $to     Target path.
     * @param string $source FrontRedirect::SOURCE_* or a registered source.
     * @return int Redirect id.
     */
    private static function writeRedirect(string $store, string $from, string $to, string $source): int
    {
        self::releasePath($store, $to);
        FrontRedirect::where('store_id', $store)->where('to', $from)->where('status', 1)->update(['to' => $to]);

        $existing = FrontRedirect::where('store_id', $store)->where('from', $from)->first();
        $values = ['to' => $to, 'code' => 301, 'status' => 1, 'source' => $source];
        if ($existing !== null) {
            $existing->update($values);

            return (int) $existing->getKey();
        }

        return (int) FrontRedirect::create($values + ['from' => $from, 'store_id' => $store])->getKey();
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
        // WHY the variants: findActive() also matches the normalised path with or without a trailing slash.
        $normal = FrontRedirect::normalizePath($path);
        $variants = array_values(array_unique([$path, $normal, $normal === '/' ? $normal : $normal . '/']));
        $hidden = FrontRedirect::where('store_id', $store)->whereIn('from', $variants)->where('status', 1)->pluck('id');
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
