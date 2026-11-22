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
 * A site owner pastes a Google Search Console / Bing Webmaster verification code on the
 * "Meta & JSON-LD" screen and every page carries the matching meta tag.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-site-verification
 * @aidlc-adr ADR-seo-indexing-signals
 */
class SiteVerificationTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAdminUser;
    use InteractsWithFrontStore;

    /**
     * Activate the store and reset AdminConfig's per-store memo.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFrontStore();
        self::clearAdminConfigCache();
    }

    /**
     * Restore the store and the config memo.
     */
    protected function tearDown(): void
    {
        $this->tearDownFrontStore();
        self::clearAdminConfigCache();
        parent::tearDown();
    }

    /** Reset AdminConfig's static per-store memo so a fresh write is visible. */
    private static function clearAdminConfigCache(): void
    {
        (new \ReflectionProperty(AdminConfig::class, 'getAllConfigOfStore'))->setValue(null, []);
    }

    /**
     * Stored value of a verification key for the current store.
     *
     * @param string $key Config key.
     * @return string|null
     */
    private function stored(string $key): ?string
    {
        return AdminConfig::where('key', $key)->where('store_id', config('app.storeId'))->value('value');
    }

    public function test_a_google_code_saved_on_the_screen_appears_on_every_page(): void
    {
        $this->asUser(slugs: ['admin_seo_meta']);

        Livewire::test(SeoMetaSettings::class)
            ->set('verifyGoogle', 'ZZgoogle-Code_123')
            ->assertHasNoErrors();

        $this->assertSame('ZZgoogle-Code_123', $this->stored('seo.verify_google'));
        self::clearAdminConfigCache();
        $this->get('/')->assertOk()
            ->assertSee('<meta name="google-site-verification" content="ZZgoogle-Code_123">', escape: false);
    }

    public function test_pasting_the_whole_meta_tag_keeps_only_the_code(): void
    {
        $this->asUser(slugs: ['admin_seo_meta']);

        Livewire::test(SeoMetaSettings::class)
            ->set('verifyGoogle', '<meta name="google-site-verification" content="ZZpasted-Tag_9" />')
            ->assertHasNoErrors()
            ->assertSet('verifyGoogle', 'ZZpasted-Tag_9');

        $this->assertSame('ZZpasted-Tag_9', $this->stored('seo.verify_google'));
    }

    public function test_a_bing_code_saved_on_the_screen_appears_on_every_page(): void
    {
        $this->asUser(slugs: ['admin_seo_meta']);

        Livewire::test(SeoMetaSettings::class)->set('verifyBing', 'ZZBING0123456789');

        $this->assertSame('ZZBING0123456789', $this->stored('seo.verify_bing'));
        self::clearAdminConfigCache();
        $this->get('/')->assertOk()
            ->assertSee('<meta name="msvalidate.01" content="ZZBING0123456789">', escape: false);
    }

    public function test_a_code_with_markup_or_odd_characters_is_refused_and_not_saved(): void
    {
        $this->asUser(slugs: ['admin_seo_meta']);
        $before = $this->stored('seo.verify_google');

        Livewire::test(SeoMetaSettings::class)
            ->set('verifyGoogle', 'zz"><script>alert(1)</script>')
            ->assertHasErrors(['verifyGoogle']);

        $this->assertSame($before, $this->stored('seo.verify_google'));
    }

    public function test_a_code_longer_than_one_hundred_characters_is_refused(): void
    {
        $this->asUser(slugs: ['admin_seo_meta']);
        $before = $this->stored('seo.verify_bing');

        Livewire::test(SeoMetaSettings::class)
            ->set('verifyBing', str_repeat('a', 101))
            ->assertHasErrors(['verifyBing']);

        $this->assertSame($before, $this->stored('seo.verify_bing'));
    }

    public function test_clearing_the_code_removes_the_tag(): void
    {
        $this->asUser(slugs: ['admin_seo_meta']);

        Livewire::test(SeoMetaSettings::class)
            ->set('verifyGoogle', 'ZZtemp-Code')
            ->set('verifyGoogle', '');

        $this->assertSame('', $this->stored('seo.verify_google'));
        self::clearAdminConfigCache();
        $this->get('/')->assertOk()->assertDontSee('google-site-verification', escape: false);
    }

    public function test_a_stored_value_that_is_not_a_valid_code_is_never_printed(): void
    {
        AdminConfig::updateOrCreate(
            ['key' => 'seo.verify_google', 'store_id' => config('app.storeId')],
            ['value' => 'zz"onload="x', 'group' => 'seo', 'code' => 'seo_meta_settings', 'sort' => 0]
        );
        self::clearAdminConfigCache();

        $this->get('/')->assertOk()->assertDontSee('google-site-verification', escape: false);
    }

    public function test_a_user_without_the_seo_meta_permission_cannot_save_a_code(): void
    {
        $this->asUser(viewAll: true);

        $component = Livewire::test(SeoMetaSettings::class);

        $this->assertDenied(fn () => $component->set('verifyGoogle', 'ZZnope'));
        $this->assertDenied(fn () => $component->set('verifyBing', 'ZZnope'));
    }
}
