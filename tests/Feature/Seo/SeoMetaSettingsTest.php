<?php

namespace GP247\Front\Tests\Feature\Seo;

use GP247\Core\Models\AdminConfig;
use GP247\Front\Admin\Livewire\SeoMetaSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use GP247\Core\Testing\InteractsWithAdminUser;
use GP247\Front\Tests\Feature\Seo\Support\InteractsWithFrontStore;
use GP247\Front\Tests\TestCase;

/**
 * E2E for the "Meta & JSON-LD" admin screen (US-SEO-004/005): robots.txt
 * editing and the master JSON-LD on/off toggle. Split out of the former
 * combined SeoSettingsTest alongside
 * SeoSitemapSettingsTest — each screen now has its own RBAC permission
 * (`admin_seo_meta`).
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-004, US-SEO-005
 */
class SeoMetaSettingsTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAdminUser;
    use InteractsWithFrontStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFrontStore();
        // WHY: AdminConfig::getAllConfigOfStore caches per-store config in a static
        // property that outlives DB transaction rollback within the same PHPUnit
        // process — another test class (e.g. RobotsTest) writing+reading seo.robots_txt
        // earlier in the same run would otherwise leak a stale value into mount() here.
        self::clearAdminConfigCache();
    }

    protected function tearDown(): void
    {
        $this->tearDownFrontStore();
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

    public function test_loads_default_robots_txt_and_jsonld_enabled_for_authorized_user(): void
    {
        $this->asUser(slugs: ['admin_seo_meta']);
        // WHY: the default blocks the configured admin prefix (GP247_ADMIN_PREFIX,
        // default `gp247_admin`), not the old hardcoded `/gp247-admin/` that
        // pointed at a path which does not exist (gp247/front 3aa0718).
        $adminPrefix = trim((string) config('gp247-config.env.GP247_ADMIN_PREFIX'), '/');

        Livewire::test(SeoMetaSettings::class)
            ->assertSet('robotsTxt', "User-agent: *\nDisallow: /admin/\nDisallow: /{$adminPrefix}/\n")
            ->assertSet('jsonldEnabled', true)
            ->assertSee(gp247_language_render('admin.seo.robots_txt'));
    }

    public function test_updates_robots_txt_and_reflects_on_public_endpoint(): void
    {
        $this->asUser(slugs: ['admin_seo_meta']);
        $storeId = config('app.storeId');

        Livewire::test(SeoMetaSettings::class)->set('robotsTxt', "User-agent: *\n# ZZ custom robots");

        $this->assertSame(
            "User-agent: *\n# ZZ custom robots",
            AdminConfig::where('key', 'seo.robots_txt')->where('store_id', $storeId)->value('value'),
        );

        self::clearAdminConfigCache();
        $response = $this->get('/robots.txt');
        $response->assertSee('# ZZ custom robots', escape: false);
    }

    public function test_rejects_robots_txt_over_max_length(): void
    {
        $this->asUser(slugs: ['admin_seo_meta']);
        $storeId = config('app.storeId');
        // WHY: assert "unchanged", not "still null" — a real site may already
        // have a saved seo.robots_txt row, and this test must not assume a
        // pristine DB to be a valid regression check.
        $before = AdminConfig::where('key', 'seo.robots_txt')->where('store_id', $storeId)->value('value');

        Livewire::test(SeoMetaSettings::class)->set('robotsTxt', str_repeat('a', 501));

        $this->assertSame(
            $before,
            AdminConfig::where('key', 'seo.robots_txt')->where('store_id', $storeId)->value('value'),
        );
    }

    public function test_toggling_jsonld_off_persists_and_gates_output(): void
    {
        $this->asUser(slugs: ['admin_seo_meta']);
        $storeId = config('app.storeId');

        Livewire::test(SeoMetaSettings::class)->set('jsonldEnabled', false);

        $this->assertSame('0', AdminConfig::where('key', 'seo.jsonld_enabled')->where('store_id', $storeId)->value('value'));

        self::clearAdminConfigCache();
        $response = $this->get('/');
        $response->assertDontSee('application/ld+json', escape: false);
    }

    public function test_update_denied_for_unauthorized_user(): void
    {
        $this->asUser(viewAll: true);

        $component = Livewire::test(SeoMetaSettings::class);

        $this->assertDenied(fn () => $component->set('robotsTxt', 'nope'));
        $this->assertDenied(fn () => $component->set('jsonldEnabled', false));
    }
}
