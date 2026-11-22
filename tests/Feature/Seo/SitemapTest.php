<?php

namespace GP247\Front\Tests\Feature\Seo;

use GP247\Front\Models\FrontPage;
use GP247\Front\Models\FrontPageDescription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use GP247\Front\Tests\Feature\Seo\Support\InteractsWithFrontStore;
use GP247\Front\Tests\TestCase;

/**
 * Feature tests for GET /sitemap.xml.
 *
 * Verifies that the sitemap is well-formed XML, contains front page URLs,
 * and responds with the correct content-type.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-004
 */
class SitemapTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithFrontStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFrontStore();
    }

    protected function tearDown(): void
    {
        $this->tearDownFrontStore();
        parent::tearDown();
    }

    public function test_sitemap_returns_xml_status_200(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
    }

    public function test_sitemap_has_urlset_root_element(): void
    {
        $response = $this->get('/sitemap.xml');

        $content = $response->getContent();
        $this->assertStringContainsString('<urlset', $content);
        $this->assertStringContainsString('sitemaps.org/schemas/sitemap', $content);
    }

    public function test_sitemap_contains_active_front_page(): void
    {
        $storeId = config('app.storeId');
        // WHY: pages own a single store_id column since the 1-1 normalization (front_page_store was dropped).
        $page = FrontPage::create(['alias' => 'zz-seo-sitemap-test', 'status' => 1, 'store_id' => $storeId]);
        FrontPageDescription::create([
            'page_id' => $page->id, 'lang' => 'en',
            'name' => 'ZZ SEO Sitemap Page', 'description' => '', 'keyword' => '',
        ]);

        // Bust sitemap cache so the new page is included (version bump — the
        // sitemap now spans many keys, invalidated together via the version
        // counter).
        \GP247\Front\Controllers\SeoController::bumpCacheVersion($storeId);

        $response = $this->get('/sitemap.xml');

        $response->assertSee('zz-seo-sitemap-test', escape: false);
    }

    public function test_sitemap_excludes_inactive_page(): void
    {
        $storeId = config('app.storeId');
        $page = FrontPage::create(['alias' => 'zz-seo-inactive-page', 'status' => 0, 'store_id' => $storeId]);

        \GP247\Front\Controllers\SeoController::bumpCacheVersion($storeId);

        $response = $this->get('/sitemap.xml');

        $response->assertDontSee('zz-seo-inactive-page', escape: false);
    }

    public function test_child_segment_returns_urlset_xml(): void
    {
        // The child-segment route (used when a store is paginated) resolves
        // independently of whether the root is currently flat or an index. The
        // valid page-type segment carries a language token only in multilingual
        // mode, so derive it from the config.
        $langs = (defined('GP247_SEO_LANG') && GP247_SEO_LANG)
            ? \GP247\Core\Models\AdminLanguage::getListActive()->keys()->all()
            : [];
        $segment = count($langs) > 1 ? 'pages-' . $langs[0] . '-1' : 'pages-1';

        $response = $this->get('/sitemap-' . $segment . '.xml');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
        $this->assertStringContainsString('<urlset', $response->getContent());
    }

    public function test_child_segment_invalid_type_returns_404(): void
    {
        $this->get('/sitemap-bogus-9.xml')->assertStatus(404);
    }

    public function test_child_segment_invalid_page_returns_404(): void
    {
        // Page 0 is out of range (pages are 1-indexed).
        $this->get('/sitemap-pages-0.xml')->assertStatus(404);
    }

    public function test_sitemap_references_css_stylesheet_not_xslt(): void
    {
        // Browsers are dropping XSLT; the readable view now comes from a CSS
        // stylesheet applied to the XML itself.
        \GP247\Front\Controllers\SeoController::bumpCacheVersion(config('app.storeId'));

        $content = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString('<?xml-stylesheet type="text/css" href="/sitemap.css"?>', $content);
        $this->assertStringNotContainsString('sitemap.xsl', $content);
    }

    public function test_sitemap_index_references_css_stylesheet(): void
    {
        // A sitemap index only appears past 50k URLs, so render it directly.
        $builder = new \GP247\Front\Library\SitemapBuilder(config('app.storeId'));
        $renderIndex = new \ReflectionMethod($builder, 'renderIndex');

        $xml = $renderIndex->invoke($builder);

        $this->assertStringContainsString('<?xml-stylesheet type="text/css" href="/sitemap.css"?>', $xml);
        $this->assertStringContainsString('<sitemapindex', $xml);
    }

    public function test_sitemap_stylesheet_served_as_css(): void
    {
        $response = $this->get('/sitemap.css');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/css', $response->headers->get('Content-Type'));
        $this->assertStringContainsString(
            '@namespace s url("http://www.sitemaps.org/schemas/sitemap/0.9")',
            $response->getContent()
        );
    }

    public function test_legacy_xsl_stylesheet_route_is_gone(): void
    {
        $this->get('/sitemap.xsl')->assertStatus(404);
    }

    public function test_sitemap_cached_in_previous_format_is_not_served(): void
    {
        // A sitemap cached before the update still points at /sitemap.xsl.
        // The format version in the cache key must make the site rebuild it
        // instead of serving the stale document until the 6h TTL expires.
        $storeId = config('app.storeId');
        $legacyKey = 'gp247_sitemap_' . $storeId . '_v'
            . \GP247\Front\Controllers\SeoController::cacheVersion($storeId);
        \Illuminate\Support\Facades\Cache::put(
            $legacyKey,
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>',
            3600
        );

        $content = $this->get('/sitemap.xml')->getContent();

        $this->assertStringNotContainsString('sitemap.xsl', $content);
        $this->assertStringContainsString('href="/sitemap.css"', $content);
    }

    public function test_home_uses_bare_root_url_without_language_prefix(): void
    {
        // front.home is in GP247_ROUTE_EXCLUDE_LANGUAGE — it has no /{lang}
        // variant, so the sitemap must list the bare root once and never emit
        // /en or /vi home URLs (which would 404). Regression for the
        // multilingual home fix.
        \GP247\Front\Controllers\SeoController::bumpCacheVersion(config('app.storeId'));

        $content = $this->get('/sitemap.xml')->getContent();
        $root = rtrim(url('/'), '/');

        $this->assertStringContainsString('<loc>' . $root . '</loc>', $content);
        $this->assertStringNotContainsString('<loc>' . $root . '/en</loc>', $content);
        $this->assertStringNotContainsString('<loc>' . $root . '/vi</loc>', $content);
    }
}
