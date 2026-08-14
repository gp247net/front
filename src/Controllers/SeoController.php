<?php

namespace GP247\Front\Controllers;

use GP247\Front\Library\SitemapBuilder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Handles SEO-specific public endpoints: sitemap.xml (+ child segments) and
 * robots.txt.
 *
 * Sitemap generation is delegated to {@see SitemapBuilder}, which produces
 * multilingual URLs and auto-paginates into a `<sitemapindex>` + child
 * sitemaps once the store exceeds the 50k-URL protocol limit (US-SEO-003,
 * US-SEO-004, ADR seo_multilingual-sitemap). Responses are generated
 * on-demand and cached (keyed by store_id and a rebuildable version counter)
 * to avoid repeated DB queries on shared hosts without a cron job (MC-007).
 *
 * `robots.txt` content is admin-managed from "Meta & JSON-LD"
 * (`GP247\Front\Admin\Livewire\SeoMetaSettings`); the sitemap inclusion
 * toggles, wildcard alias exclusion list, per-plugin toggles and the manual
 * cache-rebuild action live on "Sitemap.xml"
 * (`GP247\Front\Admin\Livewire\SeoSitemapSettings`). Sitemap URLs contributed
 * by plugins are picked up via the `front.seo_sitemap_providers` registry
 * (ADR seo_plugin-sitemap-extension).
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-004
 */
class SeoController extends RootFrontController
{
    /** Cache TTL in seconds (6 hours). */
    private const CACHE_TTL = 21600;

    /** Cache key holding the current rebuildable sitemap version per store. */
    private const VERSION_KEY_PREFIX = 'gp247_sitemap_ver_';

    /**
     * Serve the XML sitemap for the current store: a flat `<urlset>` for small
     * stores, or a `<sitemapindex>` when paginated (decided by SitemapBuilder).
     *
     * @return \Illuminate\Http\Response
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-004
     */
    public function sitemap(): Response
    {
        $storeId  = config('app.storeId');
        $cacheKey = 'gp247_sitemap_' . $storeId . '_v' . self::cacheVersion($storeId);

        $xml = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($storeId) {
            return (new SitemapBuilder($storeId))->renderRoot();
        });

        return $this->xmlResponse($xml);
    }

    /**
     * Serve a child sitemap `/sitemap-{segment}.xml`.
     *
     * @param  string $segment  Segment identifier "{type}[-{lang}]-{page}".
     * @return \Illuminate\Http\Response
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-004
     */
    public function sitemapSegment(string $segment): Response
    {
        $storeId  = config('app.storeId');
        $cacheKey = 'gp247_sitemap_seg_' . $storeId . '_' . $segment . '_v' . self::cacheVersion($storeId);

        $xml = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($storeId, $segment) {
            return (new SitemapBuilder($storeId))->renderSegment($segment);
        });

        // A null render means the segment was invalid — do not cache a 404 body.
        if ($xml === null) {
            Cache::forget($cacheKey);
            abort(404);
        }

        return $this->xmlResponse($xml);
    }

    /**
     * Serve the human-readable XSL stylesheet referenced by the sitemaps'
     * `<?xml-stylesheet?>` processing instruction. Static asset shipped with
     * the package; purely cosmetic (browsers render sitemaps as an HTML table,
     * crawlers ignore it).
     *
     * @return \Illuminate\Http\Response
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-004
     * @aidlc-adr seo_multilingual-sitemap
     */
    public function sitemapStylesheet(): Response
    {
        $path = dirname(__DIR__) . '/Resources/sitemap.xsl';
        $xsl  = is_file($path) ? (string) file_get_contents($path) : '';

        return response($xsl, 200, [
            'Content-Type'  => 'text/xsl; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Serve robots.txt for the current store.
     *
     * Content is read from AdminConfig key 'seo.robots_txt'; when absent a
     * sensible default is returned. A Sitemap: directive is always appended.
     *
     * @return \Illuminate\Http\Response
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-004
     */
    public function robots(): Response
    {
        $storeId = config('app.storeId');
        $custom  = gp247_config('seo.robots_txt', $storeId);

        if ($custom) {
            $content = $custom;
        } else {
            $content = self::defaultRobots();
        }

        // Always append the Sitemap directive so crawlers can discover it.
        $sitemapUrl = url('/sitemap.xml');
        if (!str_contains($content, 'Sitemap:')) {
            $content .= "\nSitemap: {$sitemapUrl}\n";
        }

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    /**
     * Default robots.txt body used when no custom value has been saved.
     *
     * WHY the admin Disallow is built from config, not hardcoded: the admin
     * path prefix is configurable (`GP247_ADMIN_PREFIX`, default `gp247_admin`).
     * A hardcoded literal (the old `/gp247-admin/`) both used the wrong
     * separator and failed to track a site that customized the prefix — leaving
     * the real admin path crawlable while blocking a path that does not exist.
     * The `SeoMetaSettings` screen reuses this so its editor prefill matches.
     *
     * @return string robots.txt body (no trailing Sitemap directive).
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-004
     */
    public static function defaultRobots(): string
    {
        $adminPrefix = trim((string) config('gp247-config.env.GP247_ADMIN_PREFIX'), '/');

        return "User-agent: *\nDisallow: /admin/\nDisallow: /{$adminPrefix}/\n";
    }

    /**
     * Current sitemap cache version for a store. Bumping this (on admin
     * rebuild) invalidates the root document and every child segment at once
     * without having to enumerate the dynamic segment list.
     *
     * @param  mixed $storeId
     * @return int
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-004
     */
    public static function cacheVersion($storeId): int
    {
        return (int) Cache::get(self::VERSION_KEY_PREFIX . $storeId, 1);
    }

    /**
     * Invalidate all cached sitemap documents for a store by bumping its
     * version counter. Called by the "rebuild sitemap" admin action.
     *
     * @param  mixed $storeId
     * @return void
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-004
     */
    public static function bumpCacheVersion($storeId): void
    {
        Cache::forever(self::VERSION_KEY_PREFIX . $storeId, self::cacheVersion($storeId) + 1);
    }

    /**
     * Wrap an XML string in an application/xml HTTP response.
     *
     * @param  string $xml
     * @return \Illuminate\Http\Response
     */
    private function xmlResponse(string $xml): Response
    {
        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
