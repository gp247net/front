<?php

namespace GP247\Front\Tests\Feature\TrackingConsent;

use GP247\Front\TemplateComponents\ConsentBanner;
use GP247\Front\Tracking\Application\TrackingHead;
use GP247\Front\Tracking\Application\TrackingSettingsService;
use GP247\Front\Tracking\Infrastructure\TrackingAssets;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Group;
use GP247\Front\Tests\Feature\Seo\Support\InteractsWithFrontStore;
use GP247\Front\Tests\Feature\TrackingConsent\Support\ConfiguresTracking;
use GP247\Front\Tests\TestCase;

/**
 * What a storefront page carries for the cookie banner: nothing while the banner is off,
 * otherwise an inert banner with three equal buttons and a customise panel, the owner's
 * text in the page language (escaped) or the default one, the policy link, a hidden
 * "Cookie settings" link in the GP247Front footer, and a cookie the browser can read.
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-banner
 * @aidlc-story US-tracking-consent-consent-gate
 * @aidlc-adr tracking-consent_deferred-script-activation
 */
#[Group('tracking-consent')]
class TrackingBannerTest extends TestCase
{
    use DatabaseTransactions;
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
        $this->setTracking('tracking_ga4_id', 'G-BANNER123');
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
     * @return string Tracking head of the root store.
     */
    private function trackingHead(): string
    {
        return TrackingHead::build(TrackingSettingsService::rootId());
    }

    public function test_with_the_banner_off_the_page_has_no_banner(): void
    {
        $html = $this->trackingHead();

        $this->assertStringContainsString('data-gp247-service="ga4"', $html);
        $this->assertStringNotContainsString('gp247-consent-banner', $html);
        $this->assertStringContainsString('"mode":"off"', $html);
    }

    public function test_in_ask_first_mode_the_page_carries_an_inert_banner_with_three_equal_buttons_and_a_customise_panel(): void
    {
        $this->setTracking('tracking_banner_mode', 'optin');

        $html = $this->trackingHead();

        $this->assertStringContainsString('<template id="gp247-consent-banner">', $html);
        $this->assertStringContainsString('"mode":"optin"', $html);
        foreach (['data-gp247-consent-accept', 'data-gp247-consent-reject', 'data-gp247-consent-customize', 'data-gp247-consent-panel', 'data-gp247-consent-category="analytics"', 'data-gp247-consent-category="marketing"', 'data-gp247-consent-save'] as $hook) {
            $this->assertStringContainsString($hook, $html);
        }
        preg_match('/<template id="gp247-consent-banner">(.*)<\/template>/s', $html, $banner);
        preg_match_all('/<button[^>]*class="([^"]*)"[^>]*data-gp247-consent-(accept|reject|customize) data-testid/', $banner[1], $buttons);
        $this->assertCount(3, $buttons[0]);
        $this->assertCount(1, array_unique($buttons[1]), 'the three buttons share one style');
        $this->assertStringContainsString('data-gp247-service="ga4"', $html, 'the code itself stays dormant');
    }

    public function test_notice_mode_prints_the_banner_too(): void
    {
        $this->setTracking('tracking_banner_mode', 'notice');

        $this->assertStringContainsString('<template id="gp247-consent-banner">', $this->trackingHead());
    }

    public function test_the_banner_shows_the_owner_text_of_the_page_language_escaped_or_the_default_text(): void
    {
        $this->setTracking('tracking_banner_mode', 'optin');
        $this->setTracking('tracking_banner_text', json_encode(['en' => '<b>Our</b> cookies & you']));

        app()->setLocale('en');
        $english = $this->trackingHead();
        $this->assertStringContainsString('&lt;b&gt;Our&lt;/b&gt; cookies &amp; you', $english);
        $this->assertStringNotContainsString('<b>Our</b>', $english);

        app()->setLocale('vi');
        $this->assertStringContainsString(e(gp247_language_render('front.consent.message')), $this->trackingHead());
    }

    public function test_the_banner_links_the_chosen_policy_page(): void
    {
        $this->setTracking('tracking_banner_mode', 'optin');
        $this->setTracking('tracking_policy_page', 'cookie-policy');

        $html = $this->trackingHead();

        $this->assertMatchesRegularExpression('/data-testid="tracking-consent-policy"|href="[^"]*cookie-policy[^"]*"/', $html);
        $this->assertStringContainsString('cookie-policy', $html);
    }

    public function test_a_store_without_any_tracking_code_shows_no_banner(): void
    {
        $this->setTracking('tracking_ga4_id', '');
        $this->setTracking('tracking_banner_mode', 'optin');

        $this->assertSame('', $this->trackingHead());
    }

    public function test_a_template_restyles_the_banner_with_its_own_view(): void
    {
        $dir = sys_get_temp_dir() . '/gp247-banner-' . uniqid();
        @mkdir($dir . '/ProbeTheme/gp247_components', 0777, true);
        file_put_contents($dir . '/ProbeTheme/gp247_components/consent-banner.blade.php', '<div data-probe-banner>{{ $message }}</div>');
        View::addNamespace('GP247TemplatePath', $dir);

        $component = new class ('Probe message') extends ConsentBanner {
            protected function activeTemplateName(): string
            {
                return 'ProbeTheme';
            }
        };

        $this->assertSame('<div data-probe-banner>Probe message</div>', trim(Blade::renderComponent($component)));

        @unlink($dir . '/ProbeTheme/gp247_components/consent-banner.blade.php');
    }

    public function test_the_gp247front_footer_has_a_cookie_settings_link_hidden_until_the_loader_shows_it(): void
    {
        $this->setTracking('tracking_banner_mode', 'optin');

        $this->get('/')
            ->assertOk()
            ->assertSee('data-gp247-consent-open data-testid="tracking-consent-footer-settings" hidden', escape: false)
            ->assertSee('<template id="gp247-consent-banner">', escape: false);
    }

    public function test_the_consent_cookie_is_left_unencrypted_so_the_browser_choice_is_readable(): void
    {
        $this->assertTrue(app(EncryptCookies::class)->isDisabled(TrackingHead::CONSENT_COOKIE));
    }

    public function test_the_loader_and_the_banner_stay_under_15_kb_compressed(): void
    {
        $this->setTracking('tracking_banner_mode', 'optin');
        preg_match('/<template id="gp247-consent-banner">.*<\/template>/s', $this->trackingHead(), $banner);
        $loader = (string) file_get_contents(TrackingAssets::sourceDir() . '/' . TrackingAssets::LOADER);

        // NFR-PERF-tracking-render-impact: banner + loader added by GP247 ≤ 15 KB compressed.
        $this->assertLessThan(15 * 1024, strlen(gzencode($loader . $banner[0], 9)));
    }
}
