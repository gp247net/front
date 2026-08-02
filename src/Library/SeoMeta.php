<?php

namespace GP247\Front\Library;

/**
 * SEO meta builder — centralises title/description/OG/hreflang/canonical/JSON-LD
 * generation for front (CMS) and shop pages.
 *
 * All output methods return raw PHP values; Blade views are responsible for
 * escaping when rendering into HTML attributes. JSON-LD methods apply
 * JSON_HEX_TAG | JSON_HEX_AMP to prevent XSS inside <script> blocks.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-001, US-SEO-002, US-SEO-003, US-SEO-005
 */
class SeoMeta
{
    /**
     * Build the core meta array for a page.
     *
     * @param string      $title       Page title; falls back to store title when empty.
     * @param string|null $description Page description; falls back to store description.
     * @param string|null $keyword     Meta keywords; falls back to store keywords.
     * @param string|null $ogImage     OG image path; falls back to store og_image.
     * @return array{title:string, description:string, keyword:string, og_image:string}
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-001
     */
    public static function build(
        string $title = '',
        ?string $description = null,
        ?string $keyword = null,
        ?string $ogImage = null
    ): array {
        return [
            'title'       => $title !== '' ? $title : (string) gp247_store_info('name'),
            'description' => $description ?? (string) gp247_store_info('description'),
            'keyword'     => $keyword     ?? (string) gp247_store_info('keyword'),
            'og_image'    => self::resolveOgImage($ogImage),
        ];
    }

    /**
     * Return the canonical URL for the current request (no query string).
     *
     * @return string
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-002
     */
    public static function canonicalUrl(): string
    {
        return request()->url();
    }

    /**
     * Generate hreflang link data for multilingual pages.
     *
     * Returns an empty array when GP247_SEO_LANG is disabled, so callers
     * can safely iterate without checking the constant themselves.
     *
     * @param string $routeName   Named route (e.g. 'front.page.detail').
     * @param array  $routeParams Base route parameters without 'lang'.
     * @return array<string, string>  Locale → absolute URL map.
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-003
     */
    public static function hreflangLinks(string $routeName, array $routeParams = []): array
    {
        if (!defined('GP247_SEO_LANG') || !GP247_SEO_LANG) {
            return [];
        }

        // WHY getListActive() + $lang->code: AdminLanguage has no getAll()
        // method and no `language` column — the model keys active languages by
        // their `code` column (fixed in modification 20260802T080856; the old
        // getAll()/`$lang->language` form threw and was never reached because
        // no controller wired this helper yet — US-SEO-003 <head> wiring).
        $languages = \GP247\Core\Models\AdminLanguage::getListActive();
        $links     = [];

        foreach ($languages as $lang) {
            $params              = array_merge($routeParams, ['lang' => $lang->code]);
            $links[$lang->code]  = gp247_route_front($routeName, $params);
        }

        return $links;
    }

    /**
     * Build a schema.org/Organization JSON-LD string.
     *
     * @param string      $name    Organisation / store name.
     * @param string      $url     Canonical site URL.
     * @param string|null $logoUrl Absolute URL to the logo image (optional).
     * @return string  JSON-LD encoded string safe for inline <script> use.
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-005
     */
    public static function buildOrganizationJsonLd(
        string $name,
        string $url,
        ?string $logoUrl
    ): string {
        $data = [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => $name,
            'url'      => $url,
        ];

        if ($logoUrl !== null && $logoUrl !== '') {
            $data['logo'] = $logoUrl;
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
    }

    /**
     * Build a schema.org/BreadcrumbList JSON-LD string.
     *
     * @param array $items  Each item: ['name' => string, 'url' => string|null]
     * @return string|null  JSON-LD string, or null when $items is empty.
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-005
     */
    public static function buildBreadcrumbJsonLd(array $items): ?string
    {
        if (empty($items)) {
            return null;
        }

        $listElements = [];
        foreach ($items as $position => $item) {
            $element = [
                '@type'    => 'ListItem',
                'position' => $position + 1,
                'name'     => $item['name'],
            ];
            if (!empty($item['url'])) {
                $element['item'] = $item['url'];
            }
            $listElements[] = $element;
        }

        $data = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $listElements,
        ];

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
    }

    /**
     * Build a schema.org/Product JSON-LD string.
     *
     * @param string      $name         Product name.
     * @param string      $url          Canonical product URL.
     * @param string      $price        Numeric price string (e.g. '19.99').
     * @param string      $currency     ISO 4217 currency code (e.g. 'USD').
     * @param string      $availability Schema.org availability value ('InStock' / 'OutOfStock').
     * @param string|null $imageUrl     Absolute URL to the product image.
     * @param string|null $description  Short product description.
     * @return string  JSON-LD encoded string.
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-005
     */
    public static function buildProductJsonLd(
        string $name,
        string $url,
        string $price,
        string $currency,
        string $availability,
        ?string $imageUrl,
        ?string $description
    ): string {
        $data = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $name,
            'url'         => $url,
            'description' => $description ?? '',
            'offers'      => [
                '@type'         => 'Offer',
                'price'         => $price,
                'priceCurrency' => $currency,
                'availability'  => 'https://schema.org/' . $availability,
                'url'           => $url,
            ],
        ];

        if ($imageUrl !== null && $imageUrl !== '') {
            $data['image'] = $imageUrl;
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
    }

    /**
     * Build a schema.org/Article JSON-LD string.
     *
     * Used for article-style content (e.g. plugin news/blog detail pages) —
     * unlike Product, there is no price/stock/availability.
     *
     * @param string      $headline      Article title.
     * @param string      $url           Canonical article URL.
     * @param string|null $imageUrl      Absolute URL to the cover image (optional).
     * @param string|null $datePublished ISO 8601 publish date (optional).
     * @param string|null $dateModified  ISO 8601 last-modified date (optional).
     * @param string|null $description   Short article description.
     * @return string  JSON-LD encoded string.
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-005
     */
    public static function buildArticleJsonLd(
        string $headline,
        string $url,
        ?string $imageUrl,
        ?string $datePublished,
        ?string $dateModified,
        ?string $description
    ): string {
        $data = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Article',
            'headline'    => $headline,
            'url'         => $url,
            'description' => $description ?? '',
        ];

        // RISK-OPS-007: only add optional fields when present — a stale/empty
        // value in the JSON-LD script is worse than omitting the property.
        if ($imageUrl !== null && $imageUrl !== '') {
            $data['image'] = $imageUrl;
        }
        if ($datePublished !== null && $datePublished !== '') {
            $data['datePublished'] = $datePublished;
        }
        if ($dateModified !== null && $dateModified !== '') {
            $data['dateModified'] = $dateModified;
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
    }

    /**
     * Whether JSON-LD output (Organization site-wide + any `@push('jsonld')`
     * per-page entry) is enabled for the given store. Backs the master
     * toggle on the "Cấu hình SEO" admin screen (`seo.jsonld_enabled`,
     * default enabled — matches pre-existing behaviour when unset).
     *
     * @param mixed $storeId Store id; defaults to the current request's store.
     * @return bool
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-005
     */
    public static function jsonldEnabled($storeId = null): bool
    {
        $storeId = $storeId ?? config('app.storeId');

        return gp247_config('seo.jsonld_enabled', $storeId, '1') != '0';
    }

    /**
     * Resolve the OG image path: use the given value if non-empty,
     * otherwise fall back to the store's default og_image.
     *
     * @param string|null $ogImage  Raw path from content model.
     * @return string  Absolute or root-relative URL via gp247_file().
     */
    private static function resolveOgImage(?string $ogImage): string
    {
        $path = ($ogImage !== null && $ogImage !== '')
            ? $ogImage
            : gp247_store_info(key: 'og_image', default: 'GP247/Core/images/org.jpg');

        return (string) gp247_file($path);
    }
}
