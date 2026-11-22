<?php

namespace GP247\Front\Tests\Feature\Seo;

use GP247\Core\Models\AdminConfig;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use GP247\Front\Tests\Feature\Seo\Support\InteractsWithFrontStore;
use GP247\Front\Tests\TestCase;

/**
 * Feature tests for the `front.seo_sitemap_providers` registry (US-PLG-007,
 * ADR seo_plugin-sitemap-extension) consumed by
 * `SeoController::pluginUrls()`. Uses fake callables (not the News plugin) to
 * test the generic mechanism in isolation: registration, alias-exclusion
 * filtering, and per-provider error isolation.
 *
 * @aidlc-unit seo
 * @aidlc-story US-PLG-007
 */
class PluginSitemapProviderTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithFrontStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFrontStore();
        // WHY: see SeoSettingsTest::setUp() — AdminConfig's static per-store cache
        // outlives DB transaction rollback within a single PHPUnit process, so a
        // seo.sitemap_exclude_aliases value written by another test earlier in
        // the same run would otherwise leak into this test's sitemap build.
        self::clearAdminConfigCache();
    }

    protected function tearDown(): void
    {
        $this->tearDownFrontStore();
        config(['gp247-config.front.seo_sitemap_providers' => []]);
        self::clearAdminConfigCache();
        parent::tearDown();
    }

    private static function clearAdminConfigCache(): void
    {
        $ref = new \ReflectionProperty(AdminConfig::class, 'getAllConfigOfStore');
        // WHY []: declared as an array and read via array_key_exists(); null makes later gp247_config() calls throw.
        $ref->setValue(null, []);
    }

    private function bustSitemapCache(): void
    {
        // Version bump invalidates root + all child-segment keys at once.
        \GP247\Front\Controllers\SeoController::bumpCacheVersion(config('app.storeId'));
    }

    public function test_sitemap_includes_urls_from_registered_provider(): void
    {
        config(['gp247-config.front.seo_sitemap_providers' => [
            fn ($storeId) => [
                ['alias' => 'zz-plugin-page', 'loc' => url('/zz-plugin-page'), 'changefreq' => 'weekly', 'priority' => '0.5'],
            ],
        ]]);
        $this->bustSitemapCache();

        $response = $this->get('/sitemap.xml');

        $response->assertSee('zz-plugin-page', escape: false);
    }

    public function test_sitemap_excludes_plugin_url_matching_exclude_alias_pattern(): void
    {
        AdminConfig::updateOrCreate(
            ['key' => 'seo.sitemap_exclude_aliases', 'store_id' => config('app.storeId')],
            ['value' => 'zz-excludetest-*', 'group' => 'seo', 'code' => 'seo_settings', 'sort' => 0],
        );
        self::clearAdminConfigCache();

        config(['gp247-config.front.seo_sitemap_providers' => [
            fn ($storeId) => [
                ['alias' => 'zz-excludetest-excluded', 'loc' => url('/zz-excludetest-excluded')],
            ],
        ]]);
        $this->bustSitemapCache();

        $response = $this->get('/sitemap.xml');

        $response->assertDontSee('zz-excludetest-excluded', escape: false);
    }

    public function test_sitemap_skips_entries_missing_loc(): void
    {
        config(['gp247-config.front.seo_sitemap_providers' => [
            fn ($storeId) => [
                ['alias' => 'zz-plugin-no-loc'],
            ],
        ]]);
        $this->bustSitemapCache();

        $response = $this->get('/sitemap.xml');

        $response->assertDontSee('zz-plugin-no-loc', escape: false);
    }

    public function test_one_broken_provider_does_not_break_sitemap_for_the_rest(): void
    {
        config(['gp247-config.front.seo_sitemap_providers' => [
            fn ($storeId) => throw new \RuntimeException('ZZ plugin boom'),
            fn ($storeId) => [
                ['alias' => 'zz-plugin-survivor', 'loc' => url('/zz-plugin-survivor')],
            ],
        ]]);
        $this->bustSitemapCache();

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertSee('zz-plugin-survivor', escape: false);
    }

    public function test_non_callable_entries_are_ignored(): void
    {
        config(['gp247-config.front.seo_sitemap_providers' => [
            'not-a-callable-string-that-does-not-exist-as-a-function',
        ]]);
        $this->bustSitemapCache();

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
    }

    public function test_sitemap_includes_urls_from_keyed_provider_by_default(): void
    {
        config(['gp247-config.front.seo_sitemap_providers' => [
            [
                'key' => 'ZzPlugin',
                'label' => 'ZZ Plugin',
                'callback' => fn ($storeId) => [
                    ['alias' => 'zz-keyed-plugin-page', 'loc' => url('/zz-keyed-plugin-page')],
                ],
            ],
        ]]);
        $this->bustSitemapCache();

        $response = $this->get('/sitemap.xml');

        $response->assertSee('zz-keyed-plugin-page', escape: false);
    }

    public function test_sitemap_excludes_urls_from_a_disabled_plugin(): void
    {
        AdminConfig::updateOrCreate(
            ['key' => 'seo.plugin_enabled.ZzPlugin', 'store_id' => config('app.storeId')],
            ['value' => '0', 'group' => 'seo', 'code' => 'seo_settings', 'sort' => 0],
        );
        self::clearAdminConfigCache();

        config(['gp247-config.front.seo_sitemap_providers' => [
            [
                'key' => 'ZzPlugin',
                'label' => 'ZZ Plugin',
                'callback' => fn ($storeId) => [
                    ['alias' => 'zz-disabled-plugin-page', 'loc' => url('/zz-disabled-plugin-page')],
                ],
            ],
        ]]);
        $this->bustSitemapCache();

        $response = $this->get('/sitemap.xml');

        $response->assertDontSee('zz-disabled-plugin-page', escape: false);
    }
}
