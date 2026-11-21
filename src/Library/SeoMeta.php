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
 * Indexing signals (robots, canonical, hreflang, site verification) are decided
 * here from the current route and three runtime-append registries, so every
 * template layout gets them without passing new props (ADR-seo-indexing-signals,
 * modification 20261116T050835).
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-001, US-SEO-002, US-SEO-003, US-SEO-005, US-SEO-robots-meta, US-SEO-site-verification
 * @aidlc-adr ADR-014, ADR-seo-indexing-signals
 */
class SeoMeta
{
    /** The only robots value this class ever decides on its own. */
    public const ROBOTS_NOINDEX = 'noindex, follow';

    /** Query parameters that never make a URL a "filtered" variant. */
    private const NEVER_FILTER_PARAMS = ['page', 'lang'];

    /** Shape of a search-engine verification token (Google / Bing). */
    private const VERIFICATION_TOKEN_PATTERN = '/^[A-Za-z0-9_-]{1,100}$/';

    /** admin_config keys of the verification tokens, by search engine. */
    public const VERIFICATION_KEYS = [
        'google' => 'seo.verify_google',
        'bing'   => 'seo.verify_bing',
    ];

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
     * Return the canonical URL for the current request: the URL without its
     * query string, plus `?page=N` when N is a whole number above 1, so every
     * page of a listing is its own canonical instead of pointing at page 1.
     *
     * @return string
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-002
     */
    public static function canonicalUrl(): string
    {
        $url  = request()->url();
        $page = request()->query('page');

        if (is_string($page) && ctype_digit($page) && (int) $page > 1) {
            return $url . '?page=' . (int) $page;
        }

        return $url;
    }

    /**
     * Whether the request is the content's own URL: no registered filter/sort
     * parameter and not a later page. A per-content canonical applies only here
     * (ADR-seo-content-meta-table).
     *
     * @return bool
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-content-index-control
     */
    public static function isPlainFirstPage(): bool
    {
        return !self::hasFilterParameter() && self::canonicalUrl() === request()->url();
    }

    /**
     * Decide the robots directive of the current request: `noindex, follow` when
     * the route is registered as private (`seo_noindex_routes`) or the query
     * carries a registered filter/sort parameter (`seo_noindex_query_params`);
     * null otherwise, meaning "print no robots tag".
     *
     * @return string|null
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-robots-meta, US-SEO-002
     */
    public static function robotsFor(): ?string
    {
        $route     = request()->route();
        $routeName = is_object($route) ? (string) $route->getName() : '';

        if ($routeName !== '' && self::routeMatchesAny($routeName, (array) config('gp247-config.front.seo_noindex_routes', []))) {
            return self::ROBOTS_NOINDEX;
        }

        return self::hasFilterParameter() ? self::ROBOTS_NOINDEX : null;
    }

    /**
     * og:type of the current route from `gp247-config.front.seo_og_types`
     * (`['<route name or prefix*>' => '<og type>']`), e.g. News registers
     * `'news.content' => 'article'`. Only plain lowercase types are accepted.
     *
     * @return string|null Null when no entry matches (the caller then uses `website`).
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-001
     */
    public static function ogTypeFor(): ?string
    {
        $route     = request()->route();
        $routeName = is_object($route) ? (string) $route->getName() : '';
        if ($routeName === '') {
            return null;
        }

        foreach ((array) config('gp247-config.front.seo_og_types', []) as $pattern => $type) {
            if (!is_string($pattern) || !is_string($type) || preg_match('/^[a-z][a-z.]{0,30}$/', $type) !== 1) {
                continue;
            }
            if (self::routeMatchesAny($routeName, [$pattern])) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Whether a route name matches one of the registered patterns: exact name,
     * or a prefix when the pattern ends with `*`.
     *
     * @param string            $routeName Current route name.
     * @param array<int, mixed> $patterns  Registry entries.
     * @return bool
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-robots-meta
     */
    private static function routeMatchesAny(string $routeName, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (!is_string($pattern) || trim($pattern) === '') {
                continue;
            }
            $pattern = trim($pattern);
            // WHY: a lone "*" would noindex the whole site — ignored and reported
            // once per process (RISK-OPS-seo-noindex-registry-overreach).
            if (trim($pattern, '*') === '') {
                self::warnCatchAllPattern();
                continue;
            }
            if (str_ends_with($pattern, '*')
                ? str_starts_with($routeName, rtrim($pattern, '*'))
                : $routeName === $pattern) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the query string carries a non-empty registered filter/sort parameter.
     *
     * @return bool
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-robots-meta, US-SEO-002
     */
    private static function hasFilterParameter(): bool
    {
        $params = array_diff(
            array_filter((array) config('gp247-config.front.seo_noindex_query_params', []), 'is_string'),
            self::NEVER_FILTER_PARAMS
        );

        foreach ($params as $param) {
            $value = request()->query($param);
            if ($value !== null && $value !== '' && $value !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * Report the ignored catch-all registry entry once per process.
     *
     * @return void
     */
    private static function warnCatchAllPattern(): void
    {
        static $warned = false;
        if (!$warned) {
            $warned = true;
            \Illuminate\Support\Facades\Log::warning('[gp247 seo] A lone "*" entry in a gp247-config.front SEO route registry (seo_noindex_routes / seo_og_types) was ignored: it would apply to every page of the site.');
        }
    }

    /**
     * Verification tokens of the store that are safe to print, by search engine.
     * A stored value that is not a plain token (hand-edited row, older data) is
     * treated as unset.
     *
     * @param mixed $storeId Store id; defaults to the current request's store.
     * @return array<string, string> e.g. ['google' => 'abc', 'bing' => 'XYZ'].
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-site-verification
     */
    public static function verificationCodes($storeId = null): array
    {
        $storeId = $storeId ?? config('app.storeId');
        $codes   = [];

        foreach (self::VERIFICATION_KEYS as $engine => $key) {
            $token = trim((string) gp247_config($key, $storeId, ''));
            if (self::isVerificationToken($token)) {
                $codes[$engine] = $token;
            }
        }

        return $codes;
    }

    /**
     * Turn what an admin pasted into a bare token: the `content` attribute when a
     * whole `<meta …>` tag was pasted, otherwise the trimmed input.
     *
     * @param string $raw Input as typed or pasted.
     * @return string The candidate token (still to be validated).
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-site-verification
     */
    public static function normalizeVerificationInput(string $raw): string
    {
        $raw = trim($raw);
        if (preg_match('/content\s*=\s*["\']([^"\']*)["\']/i', $raw, $match)) {
            return trim($match[1]);
        }

        return $raw;
    }

    /**
     * Whether a value has the shape of a verification token.
     *
     * @param string $token
     * @return bool
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-site-verification
     */
    public static function isVerificationToken(string $token): bool
    {
        return preg_match(self::VERIFICATION_TOKEN_PATTERN, $token) === 1;
    }

    /**
     * hreflang links of the route being rendered: same route and parameters,
     * `lang` swapped for each active language. Empty when the language prefix
     * is off, the route has no `{lang}` segment, or fewer than two languages
     * are active.
     *
     * @return array<string, string> Locale → absolute URL map.
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-003
     */
    public static function currentRouteHreflangLinks(): array
    {
        $route = request()->route();
        if (!is_object($route) || (string) $route->getName() === '' || !in_array('lang', $route->parameterNames(), true)) {
            return [];
        }

        // WHY scalars only: an implicitly bound model cannot be fed back into route().
        $params = array_filter(
            \Illuminate\Support\Arr::except($route->parameters(), ['lang']),
            fn ($value) => is_scalar($value)
        );

        $links = self::hreflangLinks($route->getName(), $params);

        return count($links) > 1 ? $links : [];
    }

    /**
     * The x-default URL of a hreflang map: the store's default language when it
     * is in the map, else the first entry.
     *
     * @param array<string, string> $links Locale → URL.
     * @return string|null Null when the map is empty.
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-003
     */
    public static function hreflangDefault(array $links): ?string
    {
        if ($links === []) {
            return null;
        }
        $default = (string) gp247_store_info('language');

        return $links[$default] ?? reset($links);
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
     * @return string  JSON-LD encoded string safe for inline <script> use, with the fields
     *                 of the registered Organization extenders (SeoJsonLdExtenders).
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-005, US-SEO-jsonld-extenders
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
        $data = SeoJsonLdExtenders::extend(SeoJsonLdExtenders::ORGANIZATION, $data, [
            'store_id' => (string) config('app.storeId'),
            'url' => $url,
        ]);

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
     * @param string|null $sku             Shop's stock-keeping unit.
     * @param string|null $brand           Brand name.
     * @param string|null $gtin12          UPC (12 digits); anything else is dropped.
     * @param string|null $gtin13          EAN/JAN (13 digits); anything else is dropped.
     * @param string|null $mpn             Manufacturer part number.
     * @param string|null $priceValidUntil Y-m-d end of the price shown (running promotion).
     * @param array|null  $rating          AggregateRating from {@see productRating()}.
     * @return string  JSON-LD encoded string; empty optional fields are omitted. Fields of
     *                 the registered Product / Offer extenders are added while a product is
     *                 the displayed content (SeoJsonLdExtenders).
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-005, US-SEO-jsonld-extenders
     */
    public static function buildProductJsonLd(
        string $name,
        string $url,
        string $price,
        string $currency,
        string $availability,
        ?string $imageUrl,
        ?string $description,
        ?string $sku = null,
        ?string $brand = null,
        ?string $gtin12 = null,
        ?string $gtin13 = null,
        ?string $mpn = null,
        ?string $priceValidUntil = null,
        ?array $rating = null
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

        $data = array_merge($data, self::productIdentifiers($sku, $brand, $gtin12, $gtin13, $mpn));

        if ($priceValidUntil !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $priceValidUntil) === 1) {
            $data['offers']['priceValidUntil'] = $priceValidUntil;
        }
        if (!empty($rating)) {
            $data['aggregateRating'] = $rating;
        }
        $context = SeoJsonLdExtenders::productContext($price, $currency, $url);
        if ($context !== null) {
            $data = SeoJsonLdExtenders::extend(SeoJsonLdExtenders::PRODUCT, $data, $context);
            $data['offers'] = SeoJsonLdExtenders::extend(SeoJsonLdExtenders::OFFER, $data['offers'], $context);
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
    }

    /**
     * The identifier part of a Product: only the values that are present and,
     * for GTINs, made of exactly the right number of digits — a wrong GTIN is
     * worse for merchant listings than none.
     *
     * @param string|null $sku
     * @param string|null $brand
     * @param string|null $gtin12
     * @param string|null $gtin13
     * @param string|null $mpn
     * @return array<string, mixed>
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-005
     */
    private static function productIdentifiers(?string $sku, ?string $brand, ?string $gtin12, ?string $gtin13, ?string $mpn): array
    {
        $fields = [];
        if (trim((string) $sku) !== '') {
            $fields['sku'] = trim($sku);
        }
        if (trim((string) $brand) !== '') {
            $fields['brand'] = ['@type' => 'Brand', 'name' => trim($brand)];
        }
        foreach (['gtin12' => [$gtin12, 12], 'gtin13' => [$gtin13, 13]] as $key => [$code, $length]) {
            $code = trim((string) $code);
            if (strlen($code) === $length && ctype_digit($code)) {
                $fields[$key] = $code;
            }
        }
        if (trim((string) $mpn) !== '') {
            $fields['mpn'] = trim($mpn);
        }

        return $fields;
    }

    /**
     * Aggregate rating of a product, asked from the resolvers plugins register in
     * `gp247-config.front.seo_product_rating_resolvers` — each a callable
     * `(string $productId, $storeId): ?array{ratingValue, reviewCount, bestRating?}`.
     * The first well-formed answer with at least one review wins; a resolver that
     * throws is reported and skipped so a broken plugin cannot take the product
     * page down (RISK-OPS-seo-plugin-resolver-failure).
     *
     * @param string $productId
     * @param mixed  $storeId   Defaults to the current request's store.
     * @return array<string, mixed>|null schema.org AggregateRating, or null.
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-005
     */
    public static function productRating(string $productId, $storeId = null): ?array
    {
        if ($productId === '') {
            return null;
        }
        $storeId = $storeId ?? config('app.storeId');

        foreach ((array) config('gp247-config.front.seo_product_rating_resolvers', []) as $resolver) {
            if (!is_callable($resolver)) {
                continue;
            }
            try {
                $rating = self::normalizeRating(call_user_func($resolver, $productId, $storeId));
            } catch (\Throwable $e) {
                report($e);
                continue;
            }
            if ($rating !== null) {
                return $rating;
            }
        }

        return null;
    }

    /**
     * Validate a resolver's answer and shape it as schema.org AggregateRating.
     *
     * @param mixed $answer
     * @return array<string, mixed>|null Null when malformed or without reviews.
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-005
     */
    private static function normalizeRating($answer): ?array
    {
        if (!is_array($answer) || !is_numeric($answer['ratingValue'] ?? null) || !is_numeric($answer['reviewCount'] ?? null)) {
            return null;
        }
        $count = (int) $answer['reviewCount'];
        if ($count < 1) {
            return null;
        }

        $rating = [
            '@type'       => 'AggregateRating',
            'ratingValue' => $answer['ratingValue'] + 0,
            'reviewCount' => $count,
        ];
        if (is_numeric($answer['bestRating'] ?? null)) {
            $rating['bestRating'] = $answer['bestRating'] + 0;
        }

        return $rating;
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
     * @param string|null $description   Short article description; empty = omitted.
     * @param string|array|null $author  Author name (becomes a Person) or a schema.org node.
     * @param array|null  $publisher     schema.org Organization node of the publisher.
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
        ?string $description,
        string|array|null $author = null,
        ?array $publisher = null
    ): string {
        $data = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Article',
            'headline'    => $headline,
            'url'         => $url,
        ];
        if (trim((string) $description) !== '') {
            $data['description'] = $description;
        }
        if (is_string($author) && trim($author) !== '') {
            $data['author'] = ['@type' => 'Person', 'name' => trim($author)];
        } elseif (is_array($author) && $author !== []) {
            $data['author'] = $author;
        }
        if (!empty($publisher)) {
            $data['publisher'] = $publisher;
        }

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
     * Resolve the OG image URL: the given value if non-empty, otherwise the
     * store's og_image, otherwise no image at all. There is deliberately no
     * bundled default picture — GP247's sample image standing in for the shop's
     * own brand was removed (modification 20261114T133728).
     *
     * @param string|null $ogImage  Raw path from content model.
     * @return string  URL via gp247_file(), or '' when neither the page nor the store has an image.
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-001
     */
    private static function resolveOgImage(?string $ogImage): string
    {
        $path = trim((string) $ogImage);
        if ($path === '') {
            $path = trim((string) gp247_store_info(key: 'og_image'));
        }

        // WHY: gp247_file('') is the site root URL, which is not an image.
        return $path === '' ? '' : (string) gp247_file($path);
    }
}
