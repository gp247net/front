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
 * without requiring a cron job (US-SEO-004, MC-007).
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

        // Shop pages (products + categories) — only when shop package is active
        if (class_exists(\GP247\Shop\Models\ShopProduct::class)) {
            $urls = array_merge($urls, $this->shopProductUrls($storeId));
            $urls = array_merge($urls, $this->shopCategoryUrls($storeId));
        }

        return $urls;
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

        $entries = [];
        foreach ($pages as $page) {
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

        $entries = [];
        foreach ($products as $product) {
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

        $entries = [];
        foreach ($categories as $cat) {
            $entries[] = [
                'loc'        => gp247_route_front('category.detail', ['alias' => $cat->alias]),
                'lastmod'    => $cat->updated_at?->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority'   => '0.6',
            ];
        }

        return $entries;
    }
}
