<?php

namespace GP247\Front\Library;

use GP247\Core\Models\AdminLanguage;
use GP247\Front\Models\FrontPage;
use GP247\Front\Models\FrontPageStore;

/**
 * Builds the XML sitemap for a store: multilingual URLs, automatic
 * `<sitemapindex>` pagination past the 50k-URL protocol limit, and chunked
 * DB reads to bound memory on large (e-commerce) catalogs.
 *
 * `SeoController` only orchestrates HTTP + caching; all collection/expansion
 * logic lives here (coding-style: keep controllers thin, files < 800 lines).
 *
 * When GP247_SEO_LANG is enabled and more than one language is active, each
 * content URL is emitted once per active language (`/vi/…`, `/en/…`) with
 * `<xhtml:link rel="alternate" hreflang>` cross-links plus `x-default`
 * (Google multilingual sitemap format). Otherwise a single URL per entry is
 * emitted, preserving pre-existing single-language behaviour.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-003, US-SEO-004
 * @aidlc-adr seo_multilingual-sitemap
 */
class SitemapBuilder
{
    /** Sitemap protocol hard limit: max <url> entries per file. */
    public const MAX_URLS = 50000;

    /** Content types that carry language-expandable route URLs. */
    private const LANG_TYPES = ['pages', 'products', 'categories'];

    /**
     * Processing instruction attaching the human-readable XSL stylesheet.
     * Root-relative so it resolves from any sitemap URL and stays correct
     * across stores/domains (crawlers ignore this PI, US-SEO-004).
     */
    private const STYLESHEET_PI = '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>' . "\n";

    /** @var mixed Store UUID this builder renders for. */
    private $storeId;

    /**
     * @var string[]|null Active language codes when multilingual, else null.
     *                    Lazily resolved so a single build reuses one query.
     */
    private ?array $langs = null;

    /** @var array<int, array>|null Memoised plugin-contributed entries. */
    private ?array $pluginEntries = null;

    /**
     * @param mixed $storeId Store UUID (as resolved from config('app.storeId')).
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-004
     */
    public function __construct($storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * Render `/sitemap.xml`: a flat `<urlset>` when the store's total URL
     * count (already multiplied by the active-language count) fits in one
     * file, otherwise a `<sitemapindex>` listing the child sitemap segments.
     *
     * @return string Sitemap XML.
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-004
     */
    public function renderRoot(): string
    {
        if ($this->totalUrlCount() <= self::MAX_URLS) {
            return $this->renderUrlset($this->collectAllUrls());
        }

        return $this->renderIndex();
    }

    /**
     * Render a child sitemap `/sitemap-{segment}.xml`.
     *
     * @param string $segment Identifier of the form "{type}[-{lang}]-{page}".
     * @return string|null    Sitemap XML, or null when the segment is invalid
     *                        (caller should return HTTP 404).
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-004
     */
    public function renderSegment(string $segment): ?string
    {
        $parsed = $this->parseSegment($segment);
        if ($parsed === null) {
            return null;
        }

        [$type, $lang, $page] = $parsed;
        $offset = ($page - 1) * self::MAX_URLS;

        // WHY: plugin entries are opaque absolute URLs (each plugin owns its
        // own language handling) — they are sliced in memory, not lang-expanded.
        if ($type === 'plugin') {
            $entries = array_slice($this->pluginUrls(), $offset, self::MAX_URLS);

            return $this->renderUrlset($this->passthroughUrls($entries));
        }

        $rawEntries = $this->typeEntries($type, $offset, self::MAX_URLS);
        $targetLangs = $lang !== '' ? [$lang] : $this->languageCodes();

        return $this->renderUrlset($this->expandEntries($rawEntries, $targetLangs));
    }

    /**
     * Total `<url>` count for the store (language multiplier applied).
     * Uses COUNT queries, never loads rows into memory.
     *
     * @return int
     */
    private function totalUrlCount(): int
    {
        $langCount = max(1, count($this->languageCodes()));
        $total = 0;
        foreach (self::LANG_TYPES as $type) {
            $total += $this->typeCount($type) * $langCount;
        }
        // Plugin URLs are already absolute (one language each), not multiplied.
        $total += count($this->pluginUrls());

        return $total;
    }

    /**
     * Collect every URL entry for the flat (single-file) sitemap.
     *
     * @return array<int, array> Expanded <url> entries.
     */
    private function collectAllUrls(): array
    {
        $langs = $this->languageCodes();
        $urls = [];
        foreach (self::LANG_TYPES as $type) {
            $urls = array_merge($urls, $this->expandEntries($this->typeEntries($type, 0, PHP_INT_MAX), $langs));
        }
        $urls = array_merge($urls, $this->passthroughUrls($this->pluginUrls()));

        return $urls;
    }

    /**
     * Render the `<sitemapindex>` listing all child segment URLs.
     *
     * @return string
     */
    private function renderIndex(): string
    {
        $langs = $this->languageCodes();
        $langKeys = $langs !== [] ? $langs : [''];

        $segments = [];
        foreach (self::LANG_TYPES as $type) {
            $count = $this->typeCount($type);
            foreach ($langKeys as $lang) {
                foreach ($this->pageRange($count) as $page) {
                    $segments[] = $this->segmentName($type, $lang, $page);
                }
            }
        }
        foreach ($this->pageRange(count($this->pluginUrls())) as $page) {
            $segments[] = $this->segmentName('plugin', '', $page);
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= self::STYLESHEET_PI;
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($segments as $segment) {
            $loc = url('/sitemap-' . $segment . '.xml');
            $xml .= "  <sitemap>\n    <loc>" . e($loc) . "</loc>\n  </sitemap>\n";
        }
        $xml .= '</sitemapindex>';

        return $xml;
    }

    /**
     * Page numbers (1..P) needed to hold $count entries at MAX_URLS each.
     * Always returns at least page 1 so an empty type still yields a valid
     * (empty) child sitemap rather than a dangling index reference.
     *
     * @param int $count
     * @return int[]
     */
    private function pageRange(int $count): array
    {
        $pages = (int) max(1, ceil($count / self::MAX_URLS));

        return range(1, $pages);
    }

    /**
     * Build the segment identifier "{type}[-{lang}]-{page}".
     *
     * @param string $type
     * @param string $lang Empty for single-language / plugin segments.
     * @param int    $page
     * @return string
     */
    private function segmentName(string $type, string $lang, int $page): string
    {
        return $lang !== ''
            ? $type . '-' . $lang . '-' . $page
            : $type . '-' . $page;
    }

    /**
     * Parse and validate a segment identifier.
     *
     * @param string $segment
     * @return array{0:string,1:string,2:int}|null [type, lang, page] or null when invalid.
     */
    private function parseSegment(string $segment): ?array
    {
        $parts = explode('-', $segment);
        if (count($parts) < 2) {
            return null;
        }

        $type = array_shift($parts);
        $page = (int) array_pop($parts);
        // Middle tokens (may contain '-', e.g. "pt-BR") rejoin into the lang.
        $lang = implode('-', $parts);

        if ($page < 1) {
            return null;
        }

        if ($type === 'plugin') {
            return $lang === '' ? ['plugin', '', $page] : null;
        }

        if (!in_array($type, self::LANG_TYPES, true)) {
            return null;
        }

        $langs = $this->languageCodes();
        if ($langs === []) {
            return $lang === '' ? [$type, '', $page] : null;
        }

        return in_array($lang, $langs, true) ? [$type, $lang, $page] : null;
    }

    /**
     * Active language codes when multilingual output applies (GP247_SEO_LANG
     * on AND more than one active language); empty array otherwise.
     *
     * @return string[]
     */
    private function languageCodes(): array
    {
        if ($this->langs === null) {
            if (!defined('GP247_SEO_LANG') || !GP247_SEO_LANG) {
                $this->langs = [];
            } else {
                $codes = AdminLanguage::getListActive()->keys()->all();
                // A single active language needs no per-language duplication.
                $this->langs = count($codes) > 1 ? array_map('strval', $codes) : [];
            }
        }

        return $this->langs;
    }

    /**
     * Site default language code used for the `x-default` alternate. Falls
     * back to the first active language when the configured locale is not
     * among the active set.
     *
     * @return string
     */
    private function defaultLang(): string
    {
        $langs = $this->languageCodes();
        $configured = (string) config('app.locale');

        return in_array($configured, $langs, true) ? $configured : ($langs[0] ?? $configured);
    }

    /**
     * Expand raw route entries into `<url>` structures, one per target
     * language, attaching hreflang alternates when multilingual.
     *
     * @param array<int, array> $entries     Raw entries (route_name/params/alias/meta).
     * @param string[]          $targetLangs Languages to emit `<loc>` for (empty ⇒ single URL).
     * @return array<int, array>
     */
    private function expandEntries(array $entries, array $targetLangs): array
    {
        $allLangs = $this->languageCodes();
        $default  = $allLangs !== [] ? $this->defaultLang() : null;
        $urls = [];

        foreach ($entries as $entry) {
            // Language-agnostic URLs (e.g. the home page) are emitted exactly
            // once with no hreflang alternates.
            if ($allLangs === [] || !empty($entry['no_lang'])) {
                // In a paginated multilingual store the same entry is collected
                // in every language's page-1 slice; emit it only in the default
                // language's slice so it is not duplicated across segments.
                if (!empty($entry['no_lang']) && $allLangs !== [] && !in_array($default, $targetLangs, true)) {
                    continue;
                }
                $urls[] = $this->urlEntry($this->routeUrl($entry, null), $entry, []);
                continue;
            }

            $alternates = $this->alternatesFor($entry, $allLangs);
            foreach ($targetLangs as $code) {
                $urls[] = $this->urlEntry($this->routeUrl($entry, $code), $entry, $alternates);
            }
        }

        return $urls;
    }

    /**
     * Build the hreflang → href alternate map (all languages + x-default).
     *
     * @param array    $entry
     * @param string[] $allLangs
     * @return array<int, array{0:string,1:string}> List of [hreflang, href].
     */
    private function alternatesFor(array $entry, array $allLangs): array
    {
        $alternates = [];
        foreach ($allLangs as $code) {
            $alternates[] = [$code, $this->routeUrl($entry, $code)];
        }
        $alternates[] = ['x-default', $this->routeUrl($entry, $this->defaultLang())];

        return $alternates;
    }

    /**
     * Resolve the absolute URL for an entry in a given language.
     *
     * WHY the home special-case: route `front.home` is in
     * GP247_ROUTE_EXCLUDE_LANGUAGE, so gp247_route_front() never prefixes it
     * with a language — the locale prefix must be built manually.
     *
     * @param array       $entry
     * @param string|null $lang Language code, or null for no language prefix.
     * @return string
     */
    private function routeUrl(array $entry, ?string $lang): string
    {
        if (($entry['route_name'] ?? null) === 'front.home') {
            return $lang !== null ? url($lang . '/') : url('/');
        }

        $params = $entry['params'] ?? [];
        if ($lang !== null) {
            $params['lang'] = $lang;
        }

        return gp247_route_front($entry['route_name'], $params);
    }

    /**
     * Assemble a single `<url>` structure from a resolved loc + entry meta.
     *
     * @param string                                 $loc
     * @param array                                  $entry
     * @param array<int, array{0:string,1:string}>   $alternates
     * @return array
     */
    private function urlEntry(string $loc, array $entry, array $alternates): array
    {
        return [
            'loc'        => $loc,
            'lastmod'    => $entry['lastmod'] ?? null,
            'changefreq' => $entry['changefreq'] ?? null,
            'priority'   => $entry['priority'] ?? null,
            'alternates' => $alternates,
        ];
    }

    /**
     * Pass plugin-contributed entries (already absolute URLs) through as
     * single-language `<url>` structures without alternates.
     *
     * @param array<int, array> $entries
     * @return array<int, array>
     */
    private function passthroughUrls(array $entries): array
    {
        $urls = [];
        foreach ($entries as $entry) {
            if (empty($entry['loc'])) {
                continue;
            }
            $urls[] = [
                'loc'        => $entry['loc'],
                'lastmod'    => $entry['lastmod'] ?? null,
                'changefreq' => $entry['changefreq'] ?? null,
                'priority'   => $entry['priority'] ?? null,
                'alternates' => [],
            ];
        }

        return $urls;
    }

    /**
     * Render a `<urlset>` document from expanded `<url>` structures.
     *
     * @param array<int, array> $urls
     * @return string
     */
    private function renderUrlset(array $urls): string
    {
        $hasAlternates = $this->languageCodes() !== [];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= self::STYLESHEET_PI;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        if ($hasAlternates) {
            $xml .= "\n  xmlns:xhtml=\"http://www.w3.org/1999/xhtml\"";
        }
        $xml .= ">\n";

        foreach ($urls as $url) {
            $xml .= $this->renderUrl($url);
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Render one `<url>` block including any hreflang alternates.
     *
     * @param array $url
     * @return string
     */
    private function renderUrl(array $url): string
    {
        $xml = "  <url>\n";
        $xml .= '    <loc>' . e($url['loc']) . "</loc>\n";

        if (!empty($url['lastmod'])) {
            $xml .= '    <lastmod>' . e($url['lastmod']) . "</lastmod>\n";
        }
        if (!empty($url['changefreq'])) {
            $xml .= '    <changefreq>' . $url['changefreq'] . "</changefreq>\n";
        }
        if (!empty($url['priority'])) {
            $xml .= '    <priority>' . $url['priority'] . "</priority>\n";
        }
        foreach ($url['alternates'] as [$hreflang, $href]) {
            $xml .= '    <xhtml:link rel="alternate" hreflang="' . e($hreflang) . '" href="' . e($href) . "\"/>\n";
        }

        $xml .= "  </url>\n";

        return $xml;
    }

    /**
     * Count rows for a language-expandable content type (COUNT only).
     *
     * @param string $type One of self::LANG_TYPES.
     * @return int
     */
    private function typeCount(string $type): int
    {
        switch ($type) {
            case 'pages':
                // +1 for the home page (a synthetic entry, see typeEntries()).
                return 1 + $this->pageQuery()->count();
            case 'products':
                $q = $this->productQuery();

                return $q === null ? 0 : $q->count();
            case 'categories':
                $q = $this->categoryQuery();

                return $q === null ? 0 : $q->count();
            default:
                return 0;
        }
    }

    /**
     * Fetch a slice of raw entries for a content type (chunked DB read).
     *
     * @param string $type   One of self::LANG_TYPES.
     * @param int    $offset
     * @param int    $limit
     * @return array<int, array>
     */
    private function typeEntries(string $type, int $offset, int $limit): array
    {
        switch ($type) {
            case 'pages':
                return $this->pageEntries($offset, $limit);
            case 'products':
                return $this->productEntries($offset, $limit);
            case 'categories':
                return $this->categoryEntries($offset, $limit);
            default:
                return [];
        }
    }

    /**
     * Base query for active front CMS pages in this store.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function pageQuery()
    {
        $tablePageStore = (new FrontPageStore)->getTable();
        $tablePage      = (new FrontPage)->getTable();

        return FrontPage::leftJoin($tablePageStore, $tablePageStore . '.page_id', $tablePage . '.id')
            ->where($tablePageStore . '.store_id', $this->storeId)
            ->where($tablePage . '.status', 1)
            ->orderBy($tablePage . '.id')
            ->select($tablePage . '.alias', $tablePage . '.updated_at');
    }

    /**
     * Raw entries for front CMS pages, including the home page on page 1.
     *
     * @param int $offset
     * @param int $limit
     * @return array<int, array>
     */
    private function pageEntries(int $offset, int $limit): array
    {
        $entries = [];

        // The synthetic home entry occupies the very first slot of type
        // "pages"; it shifts the DB offset/limit by one on the first page.
        if ($offset === 0) {
            $entries[] = [
                'route_name' => 'front.home',
                'params'     => [],
                'alias'      => '',
                'changefreq' => 'daily',
                'priority'   => '1.0',
                // WHY: front.home is in GP247_ROUTE_EXCLUDE_LANGUAGE — it has no
                // /{lang} variant (/en, /vi would 404). Emit the bare root URL
                // once, not one per language.
                'no_lang'    => true,
            ];
            $limit -= 1;
            $dbOffset = 0;
        } else {
            $dbOffset = $offset - 1;
        }

        if ($limit <= 0) {
            return $entries;
        }

        $patterns = $this->excludedAliasPatterns();
        $rows = $this->pageQuery()->skip($dbOffset)->take($limit)->get();
        foreach ($rows as $page) {
            if ($this->isAliasExcluded($page->alias, $patterns)) {
                continue;
            }
            $entries[] = [
                'route_name' => 'front.page.detail',
                'params'     => ['alias' => $page->alias],
                'alias'      => $page->alias,
                'lastmod'    => $page->updated_at?->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority'   => '0.8',
            ];
        }

        return $entries;
    }

    /**
     * Base query for active shop products in this store, or null when the shop
     * package is not installed or the admin disabled products in the sitemap.
     *
     * @return \Illuminate\Database\Eloquent\Builder|null
     */
    private function productQuery()
    {
        if (!class_exists(\GP247\Shop\Models\ShopProduct::class)) {
            return null;
        }
        if (gp247_config('seo.sitemap_include_products', $this->storeId, '1') == '0') {
            return null;
        }

        $model      = new \GP247\Shop\Models\ShopProduct;
        $storeModel = new \GP247\Shop\Models\ShopProductStore;
        $storeAdm   = new \GP247\Core\Models\AdminStore;

        return $model
            ->join($storeModel->getTable(), $storeModel->getTable() . '.product_id', $model->getTable() . '.id')
            ->join($storeAdm->getTable(), $storeAdm->getTable() . '.id', $storeModel->getTable() . '.store_id')
            ->where($storeModel->getTable() . '.store_id', $this->storeId)
            ->where($storeAdm->getTable() . '.status', 1)
            ->where($model->getTable() . '.status', 1)
            ->orderBy($model->getTable() . '.id')
            ->select($model->getTable() . '.alias', $model->getTable() . '.updated_at');
    }

    /**
     * Raw entries for active shop products (chunked slice).
     *
     * @param int $offset
     * @param int $limit
     * @return array<int, array>
     */
    private function productEntries(int $offset, int $limit): array
    {
        $query = $this->productQuery();
        if ($query === null) {
            return [];
        }

        $patterns = $this->excludedAliasPatterns();
        $entries = [];
        foreach ($query->skip($offset)->take($limit)->get() as $product) {
            if ($this->isAliasExcluded($product->alias, $patterns)) {
                continue;
            }
            $entries[] = [
                'route_name' => 'product.detail',
                'params'     => ['alias' => $product->alias],
                'alias'      => $product->alias,
                'lastmod'    => $product->updated_at?->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority'   => '0.7',
            ];
        }

        return $entries;
    }

    /**
     * Base query for active shop categories in this store, or null when the
     * shop package is absent or categories are disabled in the sitemap.
     *
     * @return \Illuminate\Database\Eloquent\Builder|null
     */
    private function categoryQuery()
    {
        if (!class_exists(\GP247\Shop\Models\ShopCategory::class)) {
            return null;
        }
        if (gp247_config('seo.sitemap_include_categories', $this->storeId, '1') == '0') {
            return null;
        }

        $model      = new \GP247\Shop\Models\ShopCategory;
        $storeModel = new \GP247\Shop\Models\ShopCategoryStore;

        return $model
            ->join($storeModel->getTable(), $storeModel->getTable() . '.category_id', $model->getTable() . '.id')
            ->where($storeModel->getTable() . '.store_id', $this->storeId)
            ->where($model->getTable() . '.status', 1)
            ->orderBy($model->getTable() . '.id')
            ->select($model->getTable() . '.alias', $model->getTable() . '.updated_at');
    }

    /**
     * Raw entries for active shop categories (chunked slice).
     *
     * @param int $offset
     * @param int $limit
     * @return array<int, array>
     */
    private function categoryEntries(int $offset, int $limit): array
    {
        $query = $this->categoryQuery();
        if ($query === null) {
            return [];
        }

        $patterns = $this->excludedAliasPatterns();
        $entries = [];
        foreach ($query->skip($offset)->take($limit)->get() as $cat) {
            if ($this->isAliasExcluded($cat->alias, $patterns)) {
                continue;
            }
            $entries[] = [
                'route_name' => 'category.detail',
                'params'     => ['alias' => $cat->alias],
                'alias'      => $cat->alias,
                'lastmod'    => $cat->updated_at?->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority'   => '0.6',
            ];
        }

        return $entries;
    }

    /**
     * Collect plugin-contributed sitemap entries from the
     * `front.seo_sitemap_providers` registry (US-PLG-007, ADR
     * seo_plugin-sitemap-extension). Each callable is invoked in isolation so
     * one buggy plugin cannot break the whole sitemap (RISK-OPS-006). Results
     * are memoised for the lifetime of this builder.
     *
     * @return array<int, array> Entries with an absolute `loc`.
     */
    private function pluginUrls(): array
    {
        if ($this->pluginEntries !== null) {
            return $this->pluginEntries;
        }

        $providers = (array) config('gp247-config.front.seo_sitemap_providers', []);
        $patterns  = $this->excludedAliasPatterns();

        $entries = [];
        foreach ($providers as $provider) {
            $key      = is_array($provider) ? ($provider['key'] ?? null) : null;
            $callable = is_array($provider) ? ($provider['callback'] ?? null) : $provider;

            if ($key !== null && gp247_config('seo.plugin_enabled.' . $key, $this->storeId, '1') == '0') {
                continue;
            }
            if (!is_callable($callable)) {
                continue;
            }

            try {
                $pluginEntries = (array) call_user_func($callable, $this->storeId);
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

        return $this->pluginEntries = $entries;
    }

    /**
     * Admin-configured alias exclusion patterns (`seo.sitemap_exclude_aliases`,
     * one wildcard pattern per line), applied to page/product/category aliases.
     *
     * @return string[]
     */
    private function excludedAliasPatterns(): array
    {
        $raw = (string) gp247_config('seo.sitemap_exclude_aliases', $this->storeId, '');

        return array_values(array_filter(array_map('trim', explode("\n", $raw)), fn (string $p) => $p !== ''));
    }

    /**
     * Whether an alias matches any admin exclusion pattern.
     *
     * @param string   $alias
     * @param string[] $patterns
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
