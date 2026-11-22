<?php

namespace GP247\Front\Tests\Feature\TrackingConsent;

use GP247\Front\Admin\Livewire\TrackingSettings;
use GP247\Front\Tracking\Application\TrackingHead;
use GP247\Front\Tracking\Application\TrackingSettingsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use GP247\Core\Testing\InteractsWithAdminUser;
use GP247\Front\Tests\Feature\TrackingConsent\Support\ConfiguresTracking;
use GP247\Front\Tests\TestCase;

/**
 * The "Cookie banner" part of the screen: the administrator picks the mode, the policy
 * page and the text per language; asking every visitor again raises the policy version;
 * the screen warns when the banner is off while code runs; values inherit per store.
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-banner
 * @aidlc-story US-tracking-consent-consent-gate
 * @aidlc-adr tracking-consent_config-storage-and-authority
 */
#[Group('tracking-consent')]
class TrackingConsentSettingsTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAdminUser;
    use ConfiguresTracking;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTracking();
    }

    /**
     * A published CMS page of a store (inserted directly: no model events).
     *
     * @param string          $alias
     * @param int|string|null $storeId Null = ROOT.
     * @return void
     */
    private function makePage(string $alias, $storeId = null): void
    {
        $id = gp247_generate_id();
        DB::connection(GP247_DB_CONNECTION)->table(GP247_DB_PREFIX . 'front_page')->insert([
            'id' => $id, 'alias' => $alias, 'status' => 1, 'store_id' => (string) ($storeId ?? TrackingSettingsService::rootId()),
        ]);
        DB::connection(GP247_DB_CONNECTION)->table(GP247_DB_PREFIX . 'front_page_description')->insert([
            'page_id' => $id, 'lang' => app()->getLocale(), 'name' => 'Cookie policy ' . $alias,
        ]);
    }

    /**
     * @return string A language code of the site.
     */
    private function language(): string
    {
        return (string) array_key_first(gp247_language_all()->all());
    }

    public function test_an_administrator_sets_the_mode_the_policy_page_and_the_banner_text(): void
    {
        $this->asUser(administrator: true);
        $this->makePage('cookie-policy-tc');
        $lang = $this->language();

        Livewire::test(TrackingSettings::class)
            ->assertSet('banner.mode', 'off')
            ->assertSeeHtml('data-testid="tracking-settings-policy-page"')
            ->assertSee('Cookie policy cookie-policy-tc')
            ->set('banner.mode', 'optin')
            ->set('banner.page', 'cookie-policy-tc')
            ->set('banner.text.' . $lang, '  We use cookies.  ')
            ->call('saveBanner')
            ->assertHasNoErrors();

        $this->resetTrackingCaches();
        $settings = TrackingSettingsService::forStore(TrackingSettingsService::rootId());
        $this->assertSame('optin', $settings->bannerMode);
        $this->assertSame('cookie-policy-tc', $settings->policyPage);
        $this->assertSame('We use cookies.', $settings->bannerTextFor($lang));
    }

    public function test_an_unknown_mode_a_page_of_another_store_and_a_too_long_text_are_refused(): void
    {
        $this->asUser(administrator: true);
        $other = $this->makeSubStore();
        $this->makePage('other-store-page', $other);
        $lang = $this->language();

        Livewire::test(TrackingSettings::class)
            ->set('banner.mode', 'always')
            ->set('banner.page', 'other-store-page')
            ->set('banner.text.' . $lang, str_repeat('x', 501))
            ->call('saveBanner')
            ->assertHasErrors(['banner.mode', 'banner.page', 'banner.text.' . $lang]);

        $this->resetTrackingCaches();
        $this->assertSame('off', TrackingSettingsService::forStore(TrackingSettingsService::rootId())->bannerMode);
    }

    public function test_asking_every_visitor_again_raises_the_policy_version_printed_for_the_loader(): void
    {
        $this->asUser(administrator: true);
        $this->setTracking('tracking_ga4_id', 'G-AGAIN1234');

        Livewire::test(TrackingSettings::class)
            ->call('askAgain')
            ->assertSeeHtml('data-testid="tracking-settings-policy-version"');

        $this->resetTrackingCaches();
        $this->assertSame('2', TrackingSettingsService::forStore(TrackingSettingsService::rootId())->consentVersion);
        $this->assertStringContainsString('"version":"2"', TrackingHead::build(TrackingSettingsService::rootId()));
    }

    public function test_the_screen_warns_when_the_banner_is_off_while_tracking_code_runs(): void
    {
        $this->asUser(administrator: true);

        Livewire::test(TrackingSettings::class)->assertDontSeeHtml('data-testid="tracking-settings-banner-off-warning"');

        $this->setTracking('tracking_pixel_id', '1234567890');
        Livewire::test(TrackingSettings::class)->assertSeeHtml('data-testid="tracking-settings-banner-off-warning"');

        $this->setTracking('tracking_banner_mode', 'optin');
        Livewire::test(TrackingSettings::class)->assertDontSeeHtml('data-testid="tracking-settings-banner-off-warning"');
    }

    public function test_an_admin_who_is_not_an_administrator_cannot_change_the_banner_or_ask_again(): void
    {
        $this->asUser(administrator: true);
        $component = Livewire::test(TrackingSettings::class);

        $this->asUser(viewAll: true, slugs: ['admin_tracking']);
        $this->assertDenied(fn () => $component->set('banner.mode', 'notice')->call('saveBanner'));
        $this->assertDenied(fn () => $component->call('askAgain'));

        $this->resetTrackingCaches();
        $settings = TrackingSettingsService::forStore(TrackingSettingsService::rootId());
        $this->assertSame('off', $settings->bannerMode);
        $this->assertSame(1, $settings->policyVersion);
    }

    public function test_a_store_follows_the_root_banner_until_it_changes_its_own(): void
    {
        $this->asUser(administrator: true);
        $store = $this->makeSubStore();
        $this->setTracking('tracking_banner_mode', 'optin');

        Livewire::test(TrackingSettings::class)
            ->set('storeId', $store)
            ->assertSet('banner.mode', 'optin')
            ->assertSet('bannerInherited.tracking_banner_mode', true)
            ->set('banner.mode', 'notice')
            ->call('saveBanner')
            ->assertHasNoErrors();

        $this->resetTrackingCaches();
        $this->assertSame('notice', TrackingSettingsService::forStore($store)->bannerMode);
        $this->assertSame('optin', TrackingSettingsService::forStore(TrackingSettingsService::rootId())->bannerMode);
        $this->assertSame(0, DB::connection(GP247_DB_CONNECTION)->table(GP247_DB_PREFIX . 'admin_config')
            ->where('store_id', $store)->where('key', 'tracking_banner_text')->count(), 'only the changed key is overridden');
    }

    public function test_asking_again_at_the_root_store_still_reaches_a_store_that_asked_again_on_its_own(): void
    {
        $store = $this->makeSubStore();

        $this->assertSame('1', TrackingSettingsService::forStore($store)->consentVersion);
        $this->assertSame('1.1', TrackingSettingsService::askAgain($store));
        $this->resetTrackingCaches();
        TrackingSettingsService::askAgain(TrackingSettingsService::rootId());
        $this->resetTrackingCaches();

        $this->assertSame('2.1', TrackingSettingsService::forStore($store)->consentVersion);
        $this->assertSame('2', TrackingSettingsService::forStore(TrackingSettingsService::rootId())->consentVersion);
    }

    public function test_a_store_never_links_the_root_store_policy_page_and_can_still_save_its_banner(): void
    {
        $this->asUser(administrator: true);
        $store = $this->makeSubStore();
        $this->makePage('root-cookie-policy');
        $this->setTracking('tracking_policy_page', 'root-cookie-policy');

        $this->assertSame('', TrackingSettingsService::forStore($store)->policyPage, 'a root page does not exist on the store domain');

        Livewire::test(TrackingSettings::class)
            ->set('storeId', $store)
            ->assertSet('banner.page', '')
            ->set('banner.mode', 'optin')
            ->call('saveBanner')
            ->assertHasNoErrors();

        $this->resetTrackingCaches();
        $this->assertSame('optin', TrackingSettingsService::forStore($store)->bannerMode);
    }

    public function test_a_policy_page_deleted_later_does_not_block_changing_the_mode(): void
    {
        $this->asUser(administrator: true);
        $this->setTracking('tracking_policy_page', 'page-gone');

        Livewire::test(TrackingSettings::class)
            ->set('banner.mode', 'notice')
            ->call('saveBanner')
            ->assertHasNoErrors();

        $this->resetTrackingCaches();
        $this->assertSame('notice', TrackingSettingsService::forStore(TrackingSettingsService::rootId())->bannerMode);
    }

    public function test_the_text_of_a_language_that_is_switched_off_is_kept_when_the_banner_is_saved(): void
    {
        $this->asUser(administrator: true);
        $this->setTracking('tracking_banner_text', json_encode(['zz' => 'Text of a language not active']));

        Livewire::test(TrackingSettings::class)
            ->set('banner.mode', 'optin')
            ->call('saveBanner')
            ->assertHasNoErrors();

        $this->resetTrackingCaches();
        $this->assertSame('Text of a language not active', TrackingSettingsService::forStore(TrackingSettingsService::rootId())->bannerTextFor('zz'));
    }
}
