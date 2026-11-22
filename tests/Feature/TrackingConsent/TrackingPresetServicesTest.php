<?php

namespace GP247\Front\Tests\Feature\TrackingConsent;

use GP247\Front\Admin\Livewire\TrackingSettings;
use GP247\Front\Tracking\Application\TrackingSettingsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use GP247\Core\Testing\InteractsWithAdminUser;
use GP247\Front\Tests\Feature\Seo\Support\InteractsWithFrontStore;
use GP247\Front\Tests\Feature\TrackingConsent\Support\ConfiguresTracking;
use GP247\Front\Tests\TestCase;

/**
 * The site owner pastes a GA4, GTM or Meta Pixel id; malformed ids are refused on the field,
 * a valid id puts the standard code on every storefront page — dormant, in its cookie group —
 * and never on admin pages.
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-preset-services
 * @aidlc-adr tracking-consent_deferred-script-activation, tracking-consent_head-injection-via-meta-head
 */
#[Group('tracking-consent')]
class TrackingPresetServicesTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAdminUser;
    use InteractsWithFrontStore;
    use ConfiguresTracking;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFrontStore();
        $this->setUpTracking();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->tearDownFrontStore();
        parent::tearDown();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function malformedIds(): array
    {
        return [
            'GA4 in the old Universal Analytics form' => ['tracking_ga4_id', 'UA-123456-1'],
            'GTM without its prefix' => ['tracking_gtm_id', '5XK2P7Q'],
            'Pixel with letters' => ['tracking_pixel_id', '12345abc'],
            'GA4 carrying markup' => ['tracking_ga4_id', 'G-AB12"><script>alert(1)</script>'],
            'GA4 sample text of the field' => ['tracking_ga4_id', 'G-XXXXXXXXXX'],
            'GTM sample text of the field' => ['tracking_gtm_id', 'gtm-xxxxxxx'],
        ];
    }

    #[DataProvider('malformedIds')]
    public function test_a_malformed_id_shows_an_error_on_its_field_and_nothing_is_saved(string $key, string $value): void
    {
        $this->asUser(administrator: true);

        Livewire::test(TrackingSettings::class)
            ->set('form.' . $key, $value)
            ->call('save')
            ->assertHasErrors(['form.' . $key]);

        $this->resetTrackingCaches();
        $settings = TrackingSettingsService::screenValues(TrackingSettingsService::rootId());
        $this->assertSame('', $settings['values'][$key]);
    }

    public function test_a_valid_ga4_id_puts_dormant_ga4_code_and_the_loader_on_every_storefront_page(): void
    {
        $this->asUser(administrator: true);
        Livewire::test(TrackingSettings::class)
            ->set('form.tracking_ga4_id', ' g-shop1234 ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('form.tracking_ga4_id', 'G-SHOP1234');
        $this->resetTrackingCaches();

        foreach (['/', gp247_route_front('cart')] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('id="gp247-tracking-config"', escape: false)
                ->assertSee('data-gp247-loader', escape: false)
                ->assertSee('data-gp247-consent="analytics" data-gp247-region="head" data-gp247-service="ga4"', escape: false);
        }
    }

    public function test_pixel_waits_for_marketing_and_gtm_for_the_group_the_owner_chose(): void
    {
        $this->setTracking('tracking_pixel_id', '1234567890');
        $this->setTracking('tracking_gtm_id', 'GTM-ABCD123');

        $this->get('/')->assertOk()
            ->assertSee('data-gp247-consent="marketing" data-gp247-region="head" data-gp247-service="pixel"', escape: false)
            ->assertSee('data-gp247-consent="marketing" data-gp247-region="head" data-gp247-service="gtm"', escape: false);

        $this->setTracking('tracking_gtm_category', 'analytics');
        $this->get('/')->assertOk()
            ->assertSee('data-gp247-consent="analytics" data-gp247-region="head" data-gp247-service="gtm"', escape: false);
    }

    public function test_the_server_never_prints_a_running_script_of_a_tracking_service(): void
    {
        $this->setTracking('tracking_ga4_id', 'G-SHOP1234');
        $this->setTracking('tracking_gtm_id', 'GTM-ABCD123');
        $this->setTracking('tracking_pixel_id', '1234567890');

        foreach (['off', 'notice', 'optin'] as $mode) {
            $this->setTracking('tracking_banner_mode', $mode);
            $html = $this->get('/')->assertOk()->getContent();

            // The service code exists only JSON-escaped inside the dormant tags (no `<`, no
            // running <script>); the loader turns it into live elements in the browser.
            $this->assertStringNotContainsString('<script async src="https://www.googletagmanager.com', $html, "mode {$mode}");
            $this->assertStringNotContainsString("gtag('config'", $html, "mode {$mode}");
            $this->assertStringNotContainsString("<script>(function(w,d,s,l,i)", $html, "mode {$mode}");
            $this->assertStringNotContainsString("<script>!function(f,b,e,v,n,t,s)", $html, "mode {$mode}");
            $dormant = trim(json_encode('<script async src="https://www.googletagmanager.com/gtag/js?id=G-SHOP1234"', JSON_HEX_TAG | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES), '"');
            $this->assertStringContainsString($dormant, $html, 'the GA4 code is there, JSON-escaped');
            $this->assertStringContainsString('"mode":"' . $mode . '"', $html);
        }
    }

    public function test_admin_pages_never_carry_tracking_code(): void
    {
        $this->setTracking('tracking_ga4_id', 'G-SHOP1234');
        $this->addSnippetRow('<script>window.adminLeak=1</script>');
        $admin = $this->signInAdmin();

        $html = $this->actingAs($admin, 'admin')->get(gp247_route_admin('admin_tracking.index'))->getContent();

        $this->assertStringNotContainsString('gp247-tracking-config', $html);
        $this->assertStringNotContainsString('data-gp247-consent="', $html, 'no dormant code (the hidden footer settings link is not code)');
    }

    public function test_leaving_every_field_empty_adds_nothing_to_the_storefront(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('gp247-tracking-config', $html);
        $this->assertStringNotContainsString('gp247-consent.js', $html);
        $this->assertStringNotContainsString('data-gp247-consent="', $html, 'no dormant code (the hidden footer settings link is not code)');
    }
}
