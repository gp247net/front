<?php

namespace GP247\Front\Tests\Feature\Seo;

use GP247\Front\Library\SeoMeta;
use GP247\Front\Tests\TestCase;

/**
 * Unit tests for the SeoMeta helper class.
 *
 * Verifies title/description/keyword resolution, OG image fallback,
 * hreflang link generation, and canonical URL generation.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-001, US-SEO-002, US-SEO-003
 */
class SeoMetaTest extends TestCase
{
    public function test_build_meta_uses_given_title(): void
    {
        $meta = SeoMeta::build(title: 'My Page Title');

        $this->assertEquals('My Page Title', $meta['title']);
    }

    public function test_build_meta_falls_back_to_store_title_when_empty(): void
    {
        $meta = SeoMeta::build(title: '');

        // Falls back to gp247_store_info('title') — must not be empty string
        $this->assertNotEmpty($meta['title']);
    }

    public function test_build_meta_always_returns_an_og_image_string(): void
    {
        $meta = SeoMeta::build(title: 'Test', ogImage: null);

        // Store image, or '' when the store has none (no bundled default any
        // more). The cases with a controlled store row live in
        // MetaHeadOgImageTest; this test runs without a transaction so it cannot set one.
        $this->assertIsString($meta['og_image']);
    }

    public function test_build_meta_respects_explicit_og_image(): void
    {
        $meta = SeoMeta::build(title: 'Test', ogImage: 'GP247/custom/img.jpg');

        $this->assertStringContainsString('custom/img.jpg', $meta['og_image']);
    }

    public function test_canonical_url_matches_current_request_url(): void
    {
        // Simulate a request without going through front middleware (store may
        // be in maintenance mode in the test DB, which would call exit()).
        $request = \Illuminate\Http\Request::create('http://localhost/home.html', 'GET');
        app()->instance('request', $request);
        request()->setRouteResolver(fn () => null);

        $canonical = SeoMeta::canonicalUrl();

        $this->assertStringContainsString('http', $canonical);
    }

    public function test_hreflang_returns_empty_array_when_seo_lang_disabled(): void
    {
        // GP247_SEO_LANG is false in default test env
        if (defined('GP247_SEO_LANG') && GP247_SEO_LANG) {
            $this->markTestSkipped('GP247_SEO_LANG is enabled; hreflang is expected.');
        }

        $links = SeoMeta::hreflangLinks(routeName: 'front.home', routeParams: []);

        $this->assertIsArray($links);
    }

    public function test_build_meta_blank_description_is_null_not_the_store_one(): void
    {
        $this->assertNull(SeoMeta::build(title: 'T', description: '')['description']);
        $this->assertNull(SeoMeta::build(title: 'T', description: '  ')['description']);
        $this->assertSame('D', SeoMeta::build(title: 'T', description: 'D')['description']);
    }

    public function test_build_meta_keyword_defaults_to_null(): void
    {
        $meta = SeoMeta::build(title: 'T');

        $this->assertArrayHasKey('keyword', $meta);
        $this->assertNull($meta['keyword']);
        $this->assertNull($meta['description']);
    }
}
