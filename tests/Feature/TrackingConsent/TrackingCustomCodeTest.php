<?php

namespace GP247\Front\Tests\Feature\TrackingConsent;

use GP247\Front\Admin\Livewire\TrackingSettings;
use GP247\Front\Models\FrontTrackingRevision;
use GP247\Front\Models\FrontTrackingSnippet;
use GP247\Front\Tracking\Application\SnippetService;
use GP247\Front\Tracking\Application\TrackingHead;
use GP247\Front\Tracking\Application\TrackingSettingsService;
use GP247\Front\Tracking\Domain\CodeSnippet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use GP247\Core\Testing\InteractsWithAdminUser;
use GP247\Front\Tests\Feature\Seo\Support\InteractsWithFrontStore;
use GP247\Front\Tests\Feature\TrackingConsent\Support\ConfiguresTracking;
use GP247\Front\Tests\TestCase;

/**
 * Custom code blocks: kept exactly as pasted, printed dormant in their region and cookie
 * group, every save keeps a before / after revision (newest 30), and the switch removes
 * every code at once without deleting it.
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-custom-code
 * @aidlc-adr tracking-consent_config-storage-and-authority, tracking-consent_deferred-script-activation
 */
#[Group('tracking-consent')]
class TrackingCustomCodeTest extends TestCase
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
     * The JSON body of the dormant tag carrying a given attribute set, decoded.
     *
     * @param string $html
     * @param string $attributes
     * @return array<int, string>
     */
    private function dormantBodies(string $html, string $attributes): array
    {
        preg_match_all('#<script type="text/plain" ' . preg_quote($attributes, '#') . '>([^<]*)</script>#', $html, $m);

        return array_map(static fn (string $json) => json_decode($json, true), $m[1]);
    }

    public function test_a_saved_block_is_kept_verbatim_and_printed_dormant_in_its_region_and_group(): void
    {
        $code = "<!-- Clarity -->\n<script>(function(c,l,a){c[a]=c[a]||function(){};})(window,document,'clarity');</script>";
        $this->asUser(administrator: true);

        Livewire::test(TrackingSettings::class)
            ->call('addSnippet')
            ->set('snippets.0.label', 'Clarity')
            ->set('snippets.0.region', 'body_close')
            ->set('snippets.0.category', 'analytics')
            ->set('snippets.0.content', $code)
            ->call('saveSnippets')
            ->assertHasNoErrors();

        $this->assertSame($code, FrontTrackingSnippet::where('store_id', TrackingSettingsService::rootId())->value('content'));
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertSame([$code], $this->dormantBodies($html, 'data-gp247-consent="analytics" data-gp247-region="body_close"'));
    }

    public function test_code_containing_a_closing_script_tag_cannot_break_out_of_its_dormant_tag(): void
    {
        $code = '<script>var x = "</script><script>window.breakout=1</script>";</script>';
        $this->addSnippetRow($code);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>window.breakout', $html, 'the code only exists JSON-escaped, never as a tag');
        $this->assertSame([$code], $this->dormantBodies($html, 'data-gp247-consent="marketing" data-gp247-region="head"'));
    }

    public function test_blade_syntax_in_custom_code_is_printed_as_text_and_never_executed(): void
    {
        $code = '<script>var t = "{{ 7*7 }}"; /* @php echo "BLADE-RAN"; @endphp */</script>';
        $this->addSnippetRow($code);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'BLADE-RAN'), 'only the JSON copy of the code, no output of an executed @php');
        $this->assertStringContainsString('{{ 7*7 }}', $html);
        $this->assertSame([$code], $this->dormantBodies($html, 'data-gp247-consent="marketing" data-gp247-region="head"'));
    }

    public function test_an_invalid_block_shows_its_error_and_nothing_is_saved(): void
    {
        $this->asUser(administrator: true);

        Livewire::test(TrackingSettings::class)
            ->call('addSnippet')
            ->set('snippets.0.content', '   ')
            ->call('saveSnippets')
            ->assertHasErrors(['snippets.0.content']);

        $this->assertSame(0, $this->trackingRows('front_tracking_snippet'));
        $this->assertSame(0, FrontTrackingRevision::count());
    }

    public function test_code_pasted_without_any_html_tag_is_refused_so_it_never_shows_as_text_on_the_storefront(): void
    {
        $this->asUser(administrator: true);

        Livewire::test(TrackingSettings::class)
            ->call('addSnippet')
            ->set('snippets.0.region', 'body_open')
            ->set('snippets.0.content', "window.dataLayer = window.dataLayer || [];\ngtag('config', 'AW-123');")
            ->call('saveSnippets')
            ->assertHasErrors(['snippets.0.content'])
            ->assertSee(gp247_language_render('admin.tracking.error_content_no_tag'));

        $this->assertSame(0, $this->trackingRows('front_tracking_snippet'));
        $this->assertSame(0, FrontTrackingRevision::count());
    }

    public function test_a_stored_block_without_tags_still_shows_on_the_screen_so_the_owner_can_fix_it(): void
    {
        $this->addSnippetRow('11111111111111', 'body_open');
        $this->asUser(administrator: true);

        Livewire::test(TrackingSettings::class)
            ->assertSet('snippets.0.content', '11111111111111')
            ->call('saveSnippets')
            ->assertHasErrors(['snippets.0.content']);

        $this->assertSame(1, $this->trackingRows('front_tracking_snippet'));
    }

    public function test_values_forged_in_the_browser_are_refused_without_breaking_the_screen(): void
    {
        $this->asUser(administrator: true);

        Livewire::test(TrackingSettings::class)
            ->set('snippets', [['region' => ['head'], 'category' => 'marketing', 'content' => ['<script>x()</script>']], 'not-a-row'])
            ->call('saveSnippets')
            ->assertHasErrors(['snippets.0.region', 'snippets.0.content', 'snippets.1.content'])
            ->set('form.tracking_ga4_id', ['G-ARRAY123'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(0, $this->trackingRows('front_tracking_snippet'));
        $this->resetTrackingCaches();
        $this->assertSame('', TrackingSettingsService::forStore(TrackingSettingsService::rootId())->ga4Id);
    }

    public function test_every_save_keeps_the_code_before_and_after_with_who_saved(): void
    {
        $admin = $this->signInAdmin();
        $component = Livewire::test(TrackingSettings::class)
            ->call('addSnippet')
            ->set('snippets.0.content', '<script>v1()</script>')
            ->call('saveSnippets');
        $component->set('snippets.0.content', '<script>v2()</script>')->call('saveSnippets');

        $revision = FrontTrackingRevision::where('store_id', TrackingSettingsService::rootId())->orderByDesc('id')->first();
        $this->assertSame('<script>v1()</script>', json_decode($revision->before, true)[0]['content']);
        $this->assertSame('<script>v2()</script>', json_decode($revision->after, true)[0]['content']);
        $this->assertSame($admin->username, $revision->admin_user_name);
        $component->assertSeeHtml('data-testid="tracking-settings-revision-' . $revision->id . '"');

        $count = FrontTrackingRevision::count();
        $component->call('saveSnippets');
        $this->assertSame($count, FrontTrackingRevision::count(), 'saving the same list again is not a change');
    }

    public function test_only_the_newest_thirty_revisions_of_a_store_are_kept(): void
    {
        $root = TrackingSettingsService::rootId();
        for ($i = 1; $i <= SnippetService::KEEP_REVISIONS + 3; $i++) {
            SnippetService::save($root, [CodeSnippet::fromInput(['region' => 'head', 'content' => "<script>v{$i}()</script>"])]);
        }

        $this->assertSame(SnippetService::KEEP_REVISIONS, FrontTrackingRevision::where('store_id', $root)->count());
        $newest = FrontTrackingRevision::where('store_id', $root)->orderByDesc('id')->first();
        $this->assertStringContainsString('v33()', $newest->after);
    }

    public function test_switching_tracking_off_removes_every_code_on_the_next_page_and_switching_on_brings_it_back(): void
    {
        $this->setTracking('tracking_ga4_id', 'G-SHOP1234');
        $this->addSnippetRow('<script>kept()</script>');
        $this->asUser(administrator: true);
        $component = Livewire::test(TrackingSettings::class);

        $component->set('enabled', false)->call('saveEnabled');
        $this->resetTrackingCaches();
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('data-gp247-consent="', $html, 'no dormant code (the hidden footer settings link is not code)');
        $this->assertStringNotContainsString('gp247-tracking-config', $html);
        $this->assertSame(1, $this->trackingRows('front_tracking_snippet'), 'the code is kept');

        $component->set('enabled', true)->call('saveEnabled');
        $this->resetTrackingCaches();
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('data-gp247-service="ga4"', $html);
        $this->assertStringContainsString('kept()', $html);
    }

    public function test_switching_tracking_off_on_the_root_store_also_stops_a_store_with_its_own_switch_on(): void
    {
        $store = $this->makeSubStore();
        $this->setTracking('tracking_ga4_id', 'G-SHOP1234');
        $this->setTracking('tracking_enabled', '1', $store);
        $this->setTracking('tracking_enabled', '0');

        $this->assertFalse(TrackingSettingsService::forStore($store)->enabled);
        $this->assertSame('', TrackingHead::build($store));
    }

    public function test_a_store_runs_the_root_blocks_until_it_switches_on_its_own_blocks(): void
    {
        $store = $this->makeSubStore();
        $this->addSnippetRow('<script>rootCode()</script>');
        $this->addSnippetRow('<script>storeCode()</script>', 'head', 'marketing', $store);

        $this->assertStringContainsString('rootCode()', TrackingHead::build($store));
        $this->assertStringNotContainsString('storeCode()', TrackingHead::build($store));

        $this->setTracking('tracking_snippet_own', '1', $store);
        $this->assertStringContainsString('storeCode()', TrackingHead::build($store));
        $this->assertStringNotContainsString('rootCode()', TrackingHead::build($store));
    }

    public function test_saving_again_refreshes_the_storefront_after_the_blocks_changed_behind_the_screen(): void
    {
        $root = TrackingSettingsService::rootId();
        $this->addSnippetRow('<script>restoredAway()</script>');
        $this->assertStringContainsString('restoredAway()', TrackingHead::build($root));

        // A database restore or a front reinstall empties the table without a save.
        FrontTrackingSnippet::where('store_id', $root)->delete();
        $this->assertStringContainsString('restoredAway()', TrackingHead::build($root), 'still cached');

        $this->asUser(administrator: true);
        Livewire::test(TrackingSettings::class)->assertSet('snippets', [])->call('saveSnippets');
        $this->assertStringNotContainsString('restoredAway()', TrackingHead::build($root));
    }

    public function test_a_broken_stored_block_is_skipped_and_the_rest_still_runs_and_the_screen_opens(): void
    {
        $this->setTracking('tracking_ga4_id', 'G-SHOP1234');
        $this->addSnippetRow('<script>goodCode()</script>');
        $this->addSnippetRow('<script>badCode()</script>', 'footer', 'necessary');
        $root = TrackingSettingsService::rootId();

        $html = TrackingHead::build($root);
        $this->assertStringContainsString('goodCode()', $html);
        $this->assertStringContainsString('data-gp247-service="ga4"', $html);
        $this->assertStringNotContainsString('badCode()', $html);

        $this->asUser(administrator: true);
        Livewire::test(TrackingSettings::class)->assertCount('snippets', 1);
    }

    public function test_saving_an_id_or_a_switch_keeps_code_that_is_pasted_but_not_saved_yet(): void
    {
        $this->asUser(administrator: true);

        Livewire::test(TrackingSettings::class)
            ->call('addSnippet')
            ->set('snippets.0.content', '<script>notSavedYet()</script>')
            ->set('form.tracking_ga4_id', 'G-SHOP1234')
            ->call('save')
            ->set('enabled', false)
            ->call('saveEnabled')
            ->assertSet('snippets.0.content', '<script>notSavedYet()</script>');
    }

    public function test_the_screen_warns_that_custom_code_runs_in_every_visitor_browser(): void
    {
        $this->asUser(administrator: true);

        Livewire::test(TrackingSettings::class)
            ->assertSeeHtml('data-testid="tracking-settings-custom-warning"')
            ->assertSee(gp247_language_render('admin.tracking.custom_warning'));
    }

    public function test_after_a_save_the_next_page_shows_the_new_code_and_a_warm_cache_reads_no_code_table(): void
    {
        $this->addSnippetRow('<script>oldCode()</script>');
        $root = TrackingSettingsService::rootId();
        $this->assertStringContainsString('oldCode()', TrackingHead::build($root));

        SnippetService::save($root, [CodeSnippet::fromInput(['region' => 'head', 'content' => '<script>newCode()</script>'])]);
        $this->assertStringContainsString('newCode()', TrackingHead::build($root));

        $table = GP247_DB_PREFIX . 'front_tracking_snippet';
        DB::connection(GP247_DB_CONNECTION)->enableQueryLog();
        DB::connection(GP247_DB_CONNECTION)->flushQueryLog();
        TrackingHead::build($root);
        $queries = array_filter(DB::connection(GP247_DB_CONNECTION)->getQueryLog(), static fn (array $q) => str_contains($q['query'], $table));
        $this->assertSame([], array_values($queries));
    }
}
