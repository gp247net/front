<?php

namespace GP247\Front\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for URL redirect rules (301/302).
 *
 * Each row maps an incoming `from` path to a `to` destination with
 * a configurable HTTP code, scoped to a store.
 *
 * @property int    $id
 * @property string $from      Incoming URL path (e.g. /old-page.html)
 * @property string $to        Target URL path or absolute URL
 * @property int    $code      HTTP redirect code (301 or 302)
 * @property string $store_id  Store UUID
 * @property int    $status    1 = active, 0 = disabled
 * @property string $source    manual | auto | import
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-006
 */
class FrontRedirect extends Model
{
    protected $connection = GP247_DB_CONNECTION;

    protected $table = GP247_DB_PREFIX . 'front_redirects';

    protected $fillable = ['from', 'to', 'code', 'store_id', 'status', 'source'];

    /** Redirect typed on the "Redirect 301" screen. */
    public const SOURCE_MANUAL = 'manual';

    /** Redirect written by SeoUrlLifecycle on an alias change (US-SEO-alias-auto-redirect). */
    public const SOURCE_AUTO = 'auto';

    /** Redirect written for an address of a previous platform (US-SEO-legacy-path-redirect). */
    public const SOURCE_IMPORT = 'import';

    /** Source names a package or plugin may register (fits the `source` column). */
    public const SOURCE_PATTERN = '/^[a-z0-9_-]{1,10}$/';

    /**
     * Whether a package or plugin registered this source name in
     * `gp247-config.front.seo_redirect_sources`; the base's own sources never count.
     *
     * @param string $source
     * @return bool
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-plugin-redirect-source
     */
    public static function isRegisteredSource(string $source): bool
    {
        return preg_match(self::SOURCE_PATTERN, $source) === 1
            && !in_array($source, [self::SOURCE_MANUAL, self::SOURCE_AUTO, self::SOURCE_IMPORT], true)
            && is_array(config('gp247-config.front.seo_redirect_sources.' . $source));
    }

    /**
     * Label of a source on the "Redirect 301" screen: the base's own labels, the label a
     * package or plugin registered, or the raw name once that plugin is gone.
     *
     * @param string|null $source
     * @return string Empty for hand-made redirects (no badge).
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-plugin-redirect-source
     */
    public static function sourceLabel(?string $source): string
    {
        $source = (string) ($source ?? self::SOURCE_MANUAL);
        if ($source === '' || $source === self::SOURCE_MANUAL) {
            return '';
        }
        if ($source === self::SOURCE_AUTO || $source === self::SOURCE_IMPORT) {
            return gp247_language_render('admin.seo_redirect.source_' . $source);
        }
        $label = config('gp247-config.front.seo_redirect_sources.' . $source . '.label');

        return self::isRegisteredSource($source) && is_string($label) && $label !== ''
            ? gp247_language_render($label)
            : $source;
    }

    /**
     * Find an active redirect for the given path within the current store.
     *
     * Tries the path as received first (hand-made rules keep matching exactly as
     * before), then its normalised form and the normalised form with a trailing
     * slash — old platforms end paths with `/` and write `%xx` in either case.
     *
     * @param string $from  URL path to look up (e.g. /old-page.html)
     * @return self|null
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-006
     */
    public static function findActive(string $from): ?self
    {
        $normal = self::normalizePath($from);
        $candidates = array_values(array_unique(array_filter(
            [$from, $normal, $normal === '/' ? '' : $normal . '/'],
            static fn (string $path) => $path !== ''
        )));

        $rows = static::whereIn('from', $candidates)
            ->where('store_id', config('app.storeId'))
            ->where('status', 1)
            ->get();
        // WHY: matched through a variant only, a rule whose target is the requested page itself
        // (`/x.html/` → `/x.html` met by `/x.html`) would loop; such a row is not a hit then.
        $rows = $rows->reject(static fn (self $row) => $row->from !== $from && self::normalizePath((string) $row->to) === $normal);
        foreach ($candidates as $candidate) {
            $hit = $rows->first(static fn (self $row) => $row->from === $candidate);
            if ($hit !== null) {
                return $hit;
            }
        }

        // WHY: the column's collation may match regardless of letter case; keep
        // returning such a row as the exact-path lookup always did.
        return $rows->first();
    }

    /**
     * One stored form of a path: each segment decoded then re-encoded (`%xx` in
     * upper case, `あ` ⇒ `%E3%81%82`), empty segments dropped, no trailing slash
     * except for `/`. A value not starting with `/` is returned unchanged.
     *
     * @param string $path URL path.
     * @return string
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-006, US-SEO-legacy-path-redirect
     * @aidlc-adr ADR-seo-url-lifecycle
     */
    public static function normalizePath(string $path): string
    {
        if ($path === '' || $path[0] !== '/') {
            return $path;
        }
        $segments = array_filter(explode('/', $path), static fn (string $segment) => $segment !== '');

        return '/' . implode('/', array_map(static fn (string $segment) => rawurlencode(rawurldecode($segment)), $segments));
    }
}
