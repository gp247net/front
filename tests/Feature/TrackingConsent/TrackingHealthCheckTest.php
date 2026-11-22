<?php

namespace GP247\Front\Tests\Feature\TrackingConsent;

use GP247\Core\AdminShell\Infrastructure\AdminAlert;
use GP247\Core\AdminShell\Support\AdminAlertRegistry;
use GP247\Front\Admin\Livewire\TrackingSettings;
use GP247\Front\Models\FrontLayoutBlock;
use GP247\Front\Tracking\Application\TemplateSupport;
use GP247\Front\Tracking\Application\TrackingHealthCheck;
use GP247\Front\Tracking\Application\TrackingSettingsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use GP247\Core\Testing\InteractsWithAdminUser;
use GP247\Front\Tests\Feature\TrackingConsent\Support\ConfiguresTracking;
use GP247\Front\Tests\TestCase;

/**
 * The health check warns when tracking code runs without asking (banner off) or cannot
 * run at all (the template does not call meta-head), and tells about layout HTML blocks
 * that hold scripts outside the consent gate; it is part of the system alerts and of
 * gp247:doctor. The settings screen warns about a template without meta-head too.
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-health-check
 * @aidlc-story US-tracking-consent-template-hook
 * @aidlc-adr tracking-consent_head-injection-via-meta-head
 */
#[Group('tracking-consent')]
class TrackingHealthCheckTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAdminUser;
    use ConfiguresTracking;

    private string $probeDir = '';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTracking();
        TemplateSupport::forget();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        if ($this->probeDir !== '') {
            @unlink($this->probeDir . '/ProbeNoHead/layout.blade.php');
            @rmdir($this->probeDir . '/ProbeNoHead');
            @rmdir($this->probeDir);
        }
        // WHY: AdminStore keeps the probe template in static memos past the rolled-back transaction.
        $this->resetTrackingCaches();
        TemplateSupport::forget();
        parent::tearDown();
    }

    /**
     * A template on the view hint path whose layout does not call meta-head.
     *
     * @return string Template name.
     */
    private function templateWithoutMetaHead(string $head = ''): string
    {
        $this->probeDir = sys_get_temp_dir() . '/gp247-tpl-' . uniqid();
        @mkdir($this->probeDir . '/ProbeNoHead', 0777, true);
        file_put_contents($this->probeDir . '/ProbeNoHead/layout.blade.php', "<html><head>{$head}<title>{{ \$title ?? '' }}</title></head><body>@yield('main')</body></html>");
        View::addNamespace('GP247TemplatePath', $this->probeDir);
        config(['view.paths' => config('view.paths')]);

        return 'ProbeNoHead';
    }

    /**
     * @param string $template
     * @return void
     */
    private function useTemplate(string $template, ?string $storeId = null): void
    {
        \GP247\Core\Models\AdminStore::where('id', $storeId ?? TrackingSettingsService::rootId())->update(['template' => $template]);
        $this->resetTrackingCaches();
        TemplateSupport::forget();
    }

    /**
     * @return AdminAlert
     */
    private function evaluate(): AdminAlert
    {
        return (new TrackingHealthCheck())->evaluate();
    }

    public function test_a_store_without_tracking_code_is_fine(): void
    {
        $this->assertSame(AdminAlert::OK, $this->evaluate()->level);
    }

    public function test_tracking_code_with_the_banner_off_is_a_warning_and_asking_first_clears_it(): void
    {
        $this->setTracking('tracking_ga4_id', 'G-HEALTH12');

        $alert = $this->evaluate();
        $this->assertSame(AdminAlert::WARNING, $alert->level);
        $this->assertContains(gp247_language_render('admin.tracking.health_banner_off'), $alert->items);

        $this->setTracking('tracking_banner_mode', 'optin');
        $this->assertSame(AdminAlert::OK, $this->evaluate()->level);
    }

    public function test_a_template_that_does_not_call_meta_head_is_a_warning_when_code_is_set(): void
    {
        $this->assertTrue(TemplateSupport::callsMetaHead('GP247Front'));
        $template = $this->templateWithoutMetaHead();
        $this->assertFalse(TemplateSupport::callsMetaHead($template));

        $this->setTracking('tracking_ga4_id', 'G-HEALTH12');
        $this->setTracking('tracking_banner_mode', 'optin');
        $this->useTemplate($template);

        $alert = $this->evaluate();
        $this->assertSame(AdminAlert::WARNING, $alert->level);
        $this->assertContains(gp247_language_render('admin.tracking.health_template', ['template' => $template]), $alert->items);
    }

    public function test_a_layout_html_block_with_a_script_is_reported_as_running_outside_the_consent_gate(): void
    {
        FrontLayoutBlock::create([
            'name' => 'ZZ old GA', 'position' => 'header', 'page' => '*', 'type' => 'html',
            'text' => '<script async src="https://www.googletagmanager.com/gtag/js?id=G-OLD"></script>',
            'status' => 1, 'sort' => 0, 'store_id' => TrackingSettingsService::rootId(),
            'template' => (string) gp247_store_info('template', null, TrackingSettingsService::rootId()),
        ]);

        $alert = $this->evaluate();

        $this->assertSame(AdminAlert::INFO, $alert->level);
        $this->assertSame(gp247_language_render('admin.tracking.health_layout_script', ['count' => 1]), $alert->message);
    }

    public function test_the_check_is_part_of_the_system_alerts_and_gp247_doctor(): void
    {
        $this->assertContains(TrackingHealthCheck::class, AdminAlertRegistry::all());

        $this->artisan('gp247:doctor', ['--json' => true])->expectsOutputToContain(TrackingHealthCheck::KEY);
    }

    public function test_the_settings_screen_warns_when_the_template_does_not_call_meta_head(): void
    {
        $this->asUser(administrator: true);
        Livewire::test(TrackingSettings::class)->assertDontSeeHtml('data-testid="tracking-settings-template-warning"');

        $this->useTemplate($this->templateWithoutMetaHead());
        Livewire::test(TrackingSettings::class)->assertSeeHtml('data-testid="tracking-settings-template-warning"');
    }

    public function test_a_call_left_in_a_blade_comment_does_not_count(): void
    {
        $template = $this->templateWithoutMetaHead('{{-- <x-gp247-front::meta-head :title="$title" /> --}}');

        $this->assertFalse(TemplateSupport::callsMetaHead($template));
    }

    public function test_a_sub_store_with_its_own_template_is_checked_too(): void
    {
        $this->setTracking('tracking_ga4_id', 'G-HEALTH12');
        $this->setTracking('tracking_banner_mode', 'optin');
        $store = $this->makeSubStore();
        $template = $this->templateWithoutMetaHead();
        $this->useTemplate($template, $store);

        $alert = $this->evaluate();

        $this->assertSame(AdminAlert::WARNING, $alert->level, 'ROOT is fine, the sub-store inherits GA4 but cannot run it');
        $this->assertStringContainsString(gp247_language_render('admin.tracking.health_template', ['template' => $template]), implode("
", $alert->items));
    }

    public function test_a_script_block_of_a_template_no_longer_in_use_is_not_reported(): void
    {
        FrontLayoutBlock::create([
            'name' => 'ZZ old theme GA', 'position' => 'header', 'page' => '*', 'type' => 'html',
            'text' => '<script>oldTheme()</script>', 'status' => 1, 'sort' => 0,
            'store_id' => TrackingSettingsService::rootId(), 'template' => 'SomeOldTheme',
        ]);

        $this->assertSame(AdminAlert::OK, $this->evaluate()->level);
    }
}
