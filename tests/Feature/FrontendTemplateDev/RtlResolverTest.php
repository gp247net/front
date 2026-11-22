<?php

namespace GP247\Front\Tests\Feature\FrontendTemplateDev;

use GP247\Core\Models\AdminLanguage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ReflectionClass;
use GP247\Front\Tests\TestCase;

/**
 * Verifies gp247front_is_rtl() (app/GP247/Templates/GP247Front/function.php)
 * resolves RTL strictly from the active locale's admin_language.rtl flag.
 *
 * Provider.php only requires function.php when gp247_extension_check_active()
 * sees an admin_config row marking the GP247Front extension enabled — and
 * that check runs once at service-provider boot, before this test's
 * DatabaseTransactions even starts. Seeding that row from inside a test
 * method would be too late, and persisting it for real in the dev DB would
 * mean enabling the template outside the approved install/cutover gate. So
 * this test requires function.php directly (same guarded require the
 * Provider itself uses) to exercise the helper's logic in isolation,
 * independent of the extension-activation wiring.
 *
 * AdminLanguage::getListActive() memoizes its result in a private static
 * property for the life of the PHP process (not reset between test methods
 * by DatabaseTransactions, which only rolls back the DB). Each test here
 * resets that cache in setUp()/tearDown() via Reflection so seeded rows are
 * always visible and no state leaks into tests that run later in the suite.
 *
 * @aidlc-unit frontend-template-dev
 * @aidlc-story US-TPL-009
 * @aidlc-adr ADR-014
 */
class RtlResolverTest extends TestCase
{
    use DatabaseTransactions;

    private string $originalLocale;

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('gp247front_is_rtl')) {
            require_once app_path('GP247/Templates/GP247Front/function.php');
        }

        $this->originalLocale = app()->getLocale();
        $this->resetAdminLanguageStaticCache();
    }

    protected function tearDown(): void
    {
        app()->setLocale($this->originalLocale);
        $this->resetAdminLanguageStaticCache();

        parent::tearDown();
    }

    private function resetAdminLanguageStaticCache(): void
    {
        $reflection = new ReflectionClass(AdminLanguage::class);

        foreach (['getListAll', 'getListActive', 'getArrayLanguages', 'getCodeActive'] as $property) {
            $staticProperty = $reflection->getProperty($property);
            $staticProperty->setAccessible(true);
            $staticProperty->setValue(null, null);
        }
    }

    private function seedLanguage(string $code, int $rtl, int $status = 1): AdminLanguage
    {
        return AdminLanguage::create([
            'name' => $code, 'code' => $code, 'icon' => '', 'status' => $status, 'rtl' => $rtl, 'sort' => 99,
        ]);
    }

    public function test_returns_true_when_active_locale_language_is_flagged_rtl(): void
    {
        $this->seedLanguage('zzrtl-on', rtl: 1);
        app()->setLocale('zzrtl-on');

        $this->assertTrue(gp247front_is_rtl());
    }

    public function test_returns_false_when_active_locale_language_is_not_flagged_rtl(): void
    {
        $this->seedLanguage('zzrtl-off', rtl: 0);
        app()->setLocale('zzrtl-off');

        $this->assertFalse(gp247front_is_rtl());
    }

    public function test_returns_false_when_active_locale_has_no_matching_language_row(): void
    {
        app()->setLocale('zzrtl-missing');

        $this->assertFalse(gp247front_is_rtl());
    }

    public function test_ignores_inactive_language_row_for_active_locale(): void
    {
        // status=0 languages are excluded from getListActive(); the resolver
        // must fall back to false rather than reading a disabled row's rtl flag.
        $this->seedLanguage('zzrtl-inactive', rtl: 1, status: 0);
        app()->setLocale('zzrtl-inactive');

        $this->assertFalse(gp247front_is_rtl());
    }
}
