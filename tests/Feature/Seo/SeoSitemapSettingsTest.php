<?php

namespace GP247\Front\Tests\Feature\Seo;

use GP247\Core\Models\AdminConfig;
use GP247\Front\Admin\Livewire\SeoSitemapSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use GP247\Core\Testing\InteractsWithAdminUser;
use GP247\Front\Tests\Feature\Seo\Support\InteractsWithFrontStore;
use GP247\Front\Tests\TestCase;

/**
 * E2E for the "Sitemap.xml" admin screen (US-SEO-004): product/category
 * toggles, per-plugin toggles, alias exclusion list, and manual rebuild.
 * Split out of the former combined SeoSettingsTest alongside SeoMetaSettingsTest — each screen now has its
 * own RBAC permission (`admin_seo_sitemap`).
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-004
 */
class SeoSitemapSettingsTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAdminUser;
    use InteractsWithFrontStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFrontStore();
        // WHY: see SeoMetaSettingsTest — AdminConfig's static per-store cache
        // outlives DB transaction rollback within the same PHPUnit process.
        self::clearAdminConfigCache();
    }

    protected function tearDown(): void
    {
        $this->tearDownFrontStore();
        config(['gp247-config.front.seo_sitemap_providers' => []]);
        self::clearAdminConfigCache();
        parent::tearDown();
    }

    /** Reset AdminConfig's static per-request cache so a fresh write is visible immediately. */
    private static function clearAdminConfigCache(): void
    {
        $ref = new \ReflectionProperty(AdminConfig::class, 'getAllConfigOfStore');
        // WHY []: declared as an array and read via array_key_exists(); null makes later gp247_config() calls throw.
        $ref->setValue(null, []);
    }

    public function test_loads_default_sitemap_toggles_for_authorized_user(): void
    {
        $this->asUser(slugs: ['admin_seo_sitemap']);

        Livewire::test(SeoSitemapSettings::class)
            ->assertSet('includeProducts', true)
            ->assertSet('includeCategories', true)
            ->assertSee(gp247_language_render('admin.seo.sitemap_title'));
    }

    public function test_toggles_sitemap_inclusion_flags(): void
    {
        $this->asUser(slugs: ['admin_seo_sitemap']);
        $storeId = config('app.storeId');

        Livewire::test(SeoSitemapSettings::class)
            ->set('includeProducts', false)
            ->set('includeCategories', false);

        $this->assertSame('0', AdminConfig::where('key', 'seo.sitemap_include_products')->where('store_id', $storeId)->value('value'));
        $this->assertSame('0', AdminConfig::where('key', 'seo.sitemap_include_categories')->where('store_id', $storeId)->value('value'));
    }

    public function test_rebuild_sitemap_invalidates_cache_via_version_bump(): void
    {
        $this->asUser(slugs: ['admin_seo_sitemap']);
        $storeId = config('app.storeId');

        // WHY version bump instead of Cache::forget of a single key: a paginated
        // sitemap spans a dynamic set of keys (root index + one per child
        // segment); one version increment invalidates them all at once. Seed a stale
        // document at the CURRENT versioned key and confirm the rebuild no longer serves it.
        $before = \GP247\Front\Controllers\SeoController::cacheVersion($storeId);
        Cache::put('gp247_sitemap_' . $storeId . '_v' . $before, '<urlset>stale</urlset>', 3600);

        Livewire::test(SeoSitemapSettings::class)->call('rebuildSitemap');

        $this->assertSame($before + 1, \GP247\Front\Controllers\SeoController::cacheVersion($storeId));
        $this->get('/sitemap.xml')->assertDontSee('stale', escape: false);
    }

    public function test_updates_exclude_aliases_and_reflects_in_sitemap_filtering(): void
    {
        $this->asUser(slugs: ['admin_seo_sitemap']);
        $storeId = config('app.storeId');

        Livewire::test(SeoSitemapSettings::class)->set('excludeAliases', "old-*\nzz-exact-alias");

        $this->assertSame(
            "old-*\nzz-exact-alias",
            AdminConfig::where('key', 'seo.sitemap_exclude_aliases')->where('store_id', $storeId)->value('value'),
        );
    }

    public function test_rejects_exclude_aliases_over_max_length(): void
    {
        $this->asUser(slugs: ['admin_seo_sitemap']);
        $storeId = config('app.storeId');
        // WHY: see test_rejects_robots_txt_over_max_length() (SeoMetaSettingsTest)
        // — assert "unchanged", not "still null", since a real site may already
        // have a saved seo.sitemap_exclude_aliases row.
        $before = AdminConfig::where('key', 'seo.sitemap_exclude_aliases')->where('store_id', $storeId)->value('value');

        Livewire::test(SeoSitemapSettings::class)->set('excludeAliases', str_repeat('a', 501));

        $this->assertSame(
            $before,
            AdminConfig::where('key', 'seo.sitemap_exclude_aliases')->where('store_id', $storeId)->value('value'),
        );
    }

    public function test_lists_registered_plugins_with_default_enabled_state(): void
    {
        config(['gp247-config.front.seo_sitemap_providers' => [
            ['key' => 'ZzPlugin', 'label' => 'ZZ Plugin', 'callback' => fn ($storeId) => []],
        ]]);
        $this->asUser(slugs: ['admin_seo_sitemap']);

        Livewire::test(SeoSitemapSettings::class)
            ->assertSet('pluginEnabled.ZzPlugin', true)
            ->assertSee('ZZ Plugin');
    }

    public function test_toggling_a_plugin_off_persists_and_reflects_in_sitemap(): void
    {
        config(['gp247-config.front.seo_sitemap_providers' => [
            ['key' => 'ZzPlugin', 'label' => 'ZZ Plugin', 'callback' => fn ($storeId) => [
                ['alias' => 'zz-toggle-plugin-page', 'loc' => url('/zz-toggle-plugin-page')],
            ]],
        ]]);
        $this->asUser(slugs: ['admin_seo_sitemap']);
        $storeId = config('app.storeId');

        Livewire::test(SeoSitemapSettings::class)->set('pluginEnabled.ZzPlugin', false);

        $this->assertSame('0', AdminConfig::where('key', 'seo.plugin_enabled.ZzPlugin')->where('store_id', $storeId)->value('value'));

        self::clearAdminConfigCache();
        \GP247\Front\Controllers\SeoController::bumpCacheVersion($storeId);
        $response = $this->get('/sitemap.xml');
        $response->assertDontSee('zz-toggle-plugin-page', escape: false);
    }

    public function test_no_plugins_card_rendered_when_registry_is_empty(): void
    {
        config(['gp247-config.front.seo_sitemap_providers' => []]);
        $this->asUser(slugs: ['admin_seo_sitemap']);

        Livewire::test(SeoSitemapSettings::class)
            ->assertDontSee(gp247_language_render('admin.seo.plugins_title'));
    }

    public function test_update_denied_for_unauthorized_user(): void
    {
        $this->asUser(viewAll: true);

        $component = Livewire::test(SeoSitemapSettings::class);

        $this->assertDenied(fn () => $component->set('includeProducts', false));
        $this->assertDenied(fn () => $component->set('excludeAliases', 'nope'));
    }
}
