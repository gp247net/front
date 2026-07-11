<?php

namespace GP247\Front\Controllers;

use GP247\Front\Models\FrontPage;
use GP247\Front\Models\FrontPageStore;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Handles SEO-specific public endpoints: sitemap.xml and robots.txt.
 *
 * Both responses are generated on-demand and cached in the Laravel cache
 * (keyed by store_id) to avoid repeated DB queries on shared hosts
 * without requiring a cron job (US-SEO-004, MC-007). `robots.txt` content, the
 * sitemap product/category inclusion toggles, and a wildcard alias exclusion
 * list are admin-managed from the "SEO" admin screen
 * (`GP247\Front\Admin\Livewire\SeoSettings`), which also offers a manual
 * cache-rebuild action (modifications `20260711T114155`, `20260711T122929`).
 * Sitemap URLs contributed by plugins (e.g. News) are picked up via the
 * `front.seo_sitemap_providers` config registry — see
 * {@see pluginUrls()} and ADR `seo_plugin-sitemap-extension`
 * (US-PLG-007, modification `20260711T132909`).
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-004
 */
class SeoController extends RootFrontController
{
    /** Cache TTL in seconds (6 hours). */
    private const CACHE_TTL = 21600;

    /**
     * Serve the XML sitemap for the current store.
     *
     * Includes active front pages (CMS) and, when the shop package is
     * installed, active products and categories. Multilingual URLs are
     * added when GP247_SEO_LANG is enabled. Results are cached.
     *
     * @return \Illuminate\Http\Response
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-004
     */
    public function sitemap(): Response
    {
        $storeId  = config('app.storeId');
        $cacheKey = 'gp247_sitemap_' . $storeId;

        $xml = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($storeId) {
            return $this->buildSitemapXml($storeId);
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
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
            $content = "User-agent: *\nDisallow: /admin/\nDisallow: /gp247-admin/\n";
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
     * Build the XML sitemap string for all active URLs in the given store.
     *
     * @param  mixed $storeId  Store UUID.
     * @return string          Valid sitemap XML.
     */
    private function buildSitemapXml($storeId): string
    {
        $urls = $this->collectUrls($storeId);

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';

        if (defined('GP247_SEO_LANG') && GP247_SEO_LANG) {
            $xml .= "\n  xmlns:xhtml=\"http://www.w3.org/1999/xhtml\"";
        }

        $xml .= ">\n";

        foreach ($urls as $urlData) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . e($urlData['loc']) . "</loc>\n";

            if (!empty($urlData['lastmod'])) {
                $xml .= '    <lastmod>' . e($urlData['lastmod']) . "</lastmod>\n";
            }

            if (!empty($urlData['changefreq'])) {
                $xml .= '    <changefreq>' . $urlData['changefreq'] . "</changefreq>\n";
            }

            if (!empty($urlData['priority'])) {
                $xml .= '    <priority>' . $urlData['priority'] . "</priority>\n";
            }

            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Collect all sitemap URL entries for the given store.
     *
     * @param  mixed $storeId
     * @return array<int, array{loc:string, lastmod?:string, changefreq?:string, priority?:string}>
     */
    private function collectUrls($storeId): array
    {
        $urls = [];

        // Home page
        $urls[] = ['loc' => url('/'), 'changefreq' => 'daily', 'priority' => '1.0'];

        // Front CMS pages
        $urls = array_merge($urls, $this->frontPageUrls($storeId));

        // Shop pages (products + categories) — only when shop package is active,
        // and each type can be toggled off individually from the admin SEO
        // settings screen (US-SEO-004). Default '1' (included) when unset keeps
        // pre-existing sites behaving exactly as before this toggle existed.
        if (class_exists(\GP247\Shop\Models\ShopProduct::class)) {
            if (gp247_config('seo.sitemap_include_products', $storeId, '1') != '0') {
                $urls = array_merge($urls, $this->shopProductUrls($storeId));
            }
            if (gp247_config('seo.sitemap_include_categories', $storeId, '1') != '0') {
                $urls = array_merge($urls, $this->shopCategoryUrls($storeId));
            }
        }

        // Plugin-contributed pages (US-PLG-007, ADR seo_plugin-sitemap-extension)
        // — front never hardcodes a plugin's name; it only reads whatever
        // callables plugins registered for themselves.
        $urls = array_merge($urls, $this->pluginUrls($storeId));

        return $urls;
    }

    /**
     * Collect sitemap entries contributed by plugins via the
     * `front.seo_sitemap_providers` config registry. Each plugin appends
     * `{key, label, callback}` to this array from its own `Provider.php`
     * (same runtime-append idiom already used there for `layout_page`), gated
     * by its own `gp247_extension_check_active()` check — so a disabled
     * plugin is never registered in the first place. `key` also lets the
     * admin toggle a whole plugin's sitemap contribution off independently
     * (`seo.plugin_enabled.<key>`, default enabled — modification
     * `20260711T135121`, {@see \GP247\Front\Admin\Livewire\SeoSettings}).
     *
     * Each callable is invoked in isolation (try/catch) so a bug in one
     * plugin cannot break sitemap.xml for the rest of the site (RISK-OPS-006).
     *
     * @param  mixed $storeId
     * @return array
     */
    private function pluginUrls($storeId): array
    {
        $providers = (array) config('gp247-config.front.seo_sitemap_providers', []);
        $patterns  = $this->excludedAliasPatterns($storeId);

        $entries = [];
        foreach ($providers as $provider) {
            $key      = is_array($provider) ? ($provider['key'] ?? null) : null;
            $callable = is_array($provider) ? ($provider['callback'] ?? null) : $provider;

            if ($key !== null && gp247_config('seo.plugin_enabled.' . $key, $storeId, '1') == '0') {
                continue;
            }

            if (!is_callable($callable)) {
                continue;
            }

            try {
                $pluginEntries = (array) call_user_func($callable, $storeId);
            } catch (\Throwable $e) {
                gp247_report('#GP247::seo_sitemap_provider:: ' . $e->getMessage() . ' - Line: ' . $e->getLine() . ' - File: ' . $e->getFile());

                continue;
            }

            foreach ($pluginEntries as $entry) {
                if (empty($entry['loc']) || $this->isAliasExcluded((string) ($entry['alias'] ?? ''), $patterns)) {
                    continue;
                }
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * Collect sitemap entries for active front CMS pages.
     *
     * @param  mixed $storeId
     * @return array
     */
    private function frontPageUrls($storeId): array
    {
        $tablePageStore = (new FrontPageStore)->getTable();
        $tablePage      = (new FrontPage)->getTable();

        $pages = FrontPage::leftJoin($tablePageStore, $tablePageStore . '.page_id', $tablePage . '.id')
            ->where($tablePageStore . '.store_id', $storeId)
            ->where($tablePage . '.status', 1)
            ->select($tablePage . '.alias', $tablePage . '.updated_at')
            ->get();

        $patterns = $this->excludedAliasPatterns($storeId);
        $entries = [];
        foreach ($pages as $page) {
            if ($this->isAliasExcluded($page->alias, $patterns)) {
                continue;
            }
            $entries[] = [
                'loc'        => gp247_route_front('front.page.detail', ['alias' => $page->alias]),
                'lastmod'    => $page->updated_at?->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority'   => '0.8',
            ];
        }

        return $entries;
    }

    /**
     * Collect sitemap entries for active shop products.
     *
     * @param  mixed $storeId
     * @return array
     */
    private function shopProductUrls($storeId): array
    {
        $model      = new \GP247\Shop\Models\ShopProduct;
        $storeModel = new \GP247\Shop\Models\ShopProductStore;
        $storeAdm   = new \GP247\Core\Models\AdminStore;

        $products = $model
            ->join($storeModel->getTable(), $storeModel->getTable() . '.product_id', $model->getTable() . '.id')
            ->join($storeAdm->getTable(), $storeAdm->getTable() . '.id', $storeModel->getTable() . '.store_id')
            ->where($storeModel->getTable() . '.store_id', $storeId)
            ->where($storeAdm->getTable() . '.status', 1)
            ->where($model->getTable() . '.status', 1)
            ->select($model->getTable() . '.alias', $model->getTable() . '.updated_at')
            ->get();

        $patterns = $this->excludedAliasPatterns($storeId);
        $entries = [];
        foreach ($products as $product) {
            if ($this->isAliasExcluded($product->alias, $patterns)) {
                continue;
            }
            $entries[] = [
                'loc'        => gp247_route_front('product.detail', ['alias' => $product->alias]),
                'lastmod'    => $product->updated_at?->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority'   => '0.7',
            ];
        }

        return $entries;
    }

    /**
     * Collect sitemap entries for active shop categories.
     *
     * @param  mixed $storeId
     * @return array
     */
    private function shopCategoryUrls($storeId): array
    {
        $model      = new \GP247\Shop\Models\ShopCategory;
        $storeModel = new \GP247\Shop\Models\ShopCategoryStore;

        $categories = $model
            ->join($storeModel->getTable(), $storeModel->getTable() . '.category_id', $model->getTable() . '.id')
            ->where($storeModel->getTable() . '.store_id', $storeId)
            ->where($model->getTable() . '.status', 1)
            ->select($model->getTable() . '.alias', $model->getTable() . '.updated_at')
            ->get();

        $patterns = $this->excludedAliasPatterns($storeId);
        $entries = [];
        foreach ($categories as $cat) {
            if ($this->isAliasExcluded($cat->alias, $patterns)) {
                continue;
            }
            $entries[] = [
                'loc'        => gp247_route_front('category.detail', ['alias' => $cat->alias]),
                'lastmod'    => $cat->updated_at?->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority'   => '0.6',
            ];
        }

        return $entries;
    }

    /**
     * Admin-configured alias exclusion patterns (US-SEO-004, modification
     * `20260711T122929`): one wildcard pattern per line in
     * `seo.sitemap_exclude_aliases`, applied to page/product/category aliases
     * alike. Home page has no alias so it is never affected.
     *
     * @param  mixed $storeId
     * @return string[]
     */
    private function excludedAliasPatterns($storeId): array
    {
        $raw = (string) gp247_config('seo.sitemap_exclude_aliases', $storeId, '');

        return array_values(array_filter(array_map('trim', explode("\n", $raw)), fn (string $p) => $p !== ''));
    }

    /**
     * @param  string   $alias
     * @param  string[] $patterns
     * @return bool
     */
    private function isAliasExcluded(string $alias, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $alias)) {
                return true;
            }
        }

        return false;
    }
}
