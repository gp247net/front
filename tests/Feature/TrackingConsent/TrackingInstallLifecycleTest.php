<?php

namespace GP247\Front\Tests\Feature\TrackingConsent;

use GP247\Core\Models\AdminConfig;
use GP247\Front\Admin\Database\Seeders\DataFrontLanguageSeeder;
use GP247\Front\Tracking\Application\TrackingSettingsService;
use GP247\Front\Tracking\Infrastructure\TrackingAssets;
use GP247\Front\Tracking\Infrastructure\TrackingInstaller;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use GP247\Front\Tests\Feature\TrackingConsent\Support\ConfiguresTracking;
use GP247\Front\Tests\TestCase;

/**
 * A fresh install and a site running gp247:update get the same tables, menu entry,
 * settings (nothing set, switch on, banner off) and labels in en / vi / ja; running the
 * update again changes nothing and keeps what the owner set; the loader reaches public/.
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-install-lifecycle
 * @aidlc-adr tracking-consent_config-storage-and-authority
 */
#[Group('tracking-consent')]
class TrackingInstallLifecycleTest extends TestCase
{
    use DatabaseTransactions;
    use ConfiguresTracking;

    private const MIGRATION = 'vendor/gp247/front/src/Admin/Database/Migrations/upgrade/2026_11_19_170000_install_front_tracking.php';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTracking();
    }

    public function test_running_the_update_twice_gives_both_tables_one_menu_entry_and_the_default_settings(): void
    {
        AdminConfig::where('group', TrackingSettingsService::GROUP)->delete();
        DB::connection(GP247_DB_CONNECTION)->table(GP247_DB_PREFIX . 'admin_menu')->where('uri', TrackingInstaller::MENU_URI)->delete();

        $migration = require base_path(self::MIGRATION);
        $migration->up();
        $migration->up();
        $this->reopenTransactionAfterDdl();

        $this->assertTrue(Schema::connection(GP247_DB_CONNECTION)->hasColumns(GP247_DB_PREFIX . 'front_tracking_snippet', ['store_id', 'region', 'category', 'label', 'content', 'sort', 'status']));
        $this->assertTrue(Schema::connection(GP247_DB_CONNECTION)->hasColumns(GP247_DB_PREFIX . 'front_tracking_revision', ['store_id', 'admin_user_id', 'before', 'after', 'created_at']));
        $this->assertSame(1, DB::connection(GP247_DB_CONNECTION)->table(GP247_DB_PREFIX . 'admin_menu')->where('uri', TrackingInstaller::MENU_URI)->count());

        $this->resetTrackingCaches();
        $settings = TrackingSettingsService::forStore(TrackingSettingsService::rootId());
        $this->assertTrue($settings->enabled);
        $this->assertFalse($settings->hasAnyService());
        $this->assertSame('off', $settings->bannerMode);
        $this->assertSame(count(TrackingSettingsService::DEFAULTS), AdminConfig::where('group', TrackingSettingsService::GROUP)->count());
    }

    public function test_running_the_update_again_keeps_what_the_owner_set(): void
    {
        $this->setTracking('tracking_ga4_id', 'G-KEEP1234');
        $this->setTracking('tracking_enabled', '0');

        (require base_path(self::MIGRATION))->up();
        $this->resetTrackingCaches();

        $settings = TrackingSettingsService::forStore(TrackingSettingsService::rootId());
        $this->assertSame('G-KEEP1234', $settings->ga4Id);
        $this->assertFalse($settings->enabled);
    }

    public function test_a_site_that_has_not_run_the_update_yet_shows_its_pages_without_touching_the_tracking_tables(): void
    {
        AdminConfig::where('group', TrackingSettingsService::GROUP)->delete();
        $this->resetTrackingCaches();
        $table = GP247_DB_PREFIX . TrackingInstaller::TABLE_SNIPPET;

        DB::connection(GP247_DB_CONNECTION)->enableQueryLog();
        DB::connection(GP247_DB_CONNECTION)->flushQueryLog();
        $html = \GP247\Front\Tracking\Application\TrackingHead::build(TrackingSettingsService::rootId());

        $this->assertSame('', $html);
        $queries = array_filter(DB::connection(GP247_DB_CONNECTION)->getQueryLog(), static fn (array $q) => str_contains($q['query'], $table));
        $this->assertSame([], array_values($queries));
    }

    public function test_a_fresh_install_seeds_the_same_menu_row_as_the_update(): void
    {
        $seeder = file_get_contents(base_path('vendor/gp247/front/src/Admin/Database/Seeders/DataFrontDefaultSeeder.php'));
        $tables = file_get_contents(base_path('vendor/gp247/front/src/Admin/Database/Migrations/00_00_00_create_tables_front.php'));

        $this->assertStringContainsString('TrackingInstaller::menuRow((int) $idBlockSeo)', $seeder);
        $this->assertStringContainsString('TrackingInstaller::seedConfig()', $seeder);
        $this->assertStringContainsString('TrackingInstaller::createTables()', $tables);
        $this->assertSame('admin.menu_titles.tracking', TrackingInstaller::menuRow(1)['title']);
    }

    public function test_every_new_label_ships_in_the_front_language_seeder_in_english_vietnamese_and_japanese(): void
    {
        $rows = (new DataFrontLanguageSeeder())->dataLang();
        $byCode = [];
        foreach ($rows as $row) {
            if (str_starts_with($row['code'], 'admin.tracking.') || $row['code'] === 'admin.menu_titles.tracking') {
                $byCode[$row['code']][$row['location']] = $row['text'];
            }
        }

        $this->assertGreaterThan(50, count($byCode));
        foreach ($byCode as $code => $texts) {
            $locales = array_keys($texts);
            sort($locales);
            $this->assertSame(['en', 'ja', 'vi'], $locales, "{$code} needs en / vi / ja");
            foreach ($texts as $text) {
                $this->assertNotSame('', trim($text), "{$code} has an empty text");
            }
        }
        $missing = array_diff(array_keys(TrackingSettingsService::DEFAULTS), array_map(static fn (string $c) => substr($c, strlen('admin.tracking.config.')), array_filter(array_keys($byCode), static fn (string $c) => str_starts_with($c, 'admin.tracking.config.'))));
        $this->assertSame([], array_values($missing), 'every setting row has its label');
    }

    public function test_the_consent_loader_reaches_a_public_folder_and_is_refreshed_after_an_update(): void
    {
        $target = sys_get_temp_dir() . '/gp247-tracking-' . uniqid();

        $this->assertTrue(TrackingAssets::sync($target));
        $this->assertFileEquals(TrackingAssets::sourceDir() . '/' . TrackingAssets::LOADER, $target . '/' . TrackingAssets::LOADER);
        $this->assertFalse(TrackingAssets::sync($target), 'nothing to do while the build is unchanged');

        file_put_contents($target . '/.gp247-assets', 'older-build');
        $this->assertTrue(TrackingAssets::sync($target), 'a new package build is copied again');

        @unlink($target . '/' . TrackingAssets::LOADER);
        @unlink($target . '/.gp247-assets');
        @rmdir($target);
    }

    public function test_the_consent_loader_stays_small(): void
    {
        $source = (string) file_get_contents(TrackingAssets::sourceDir() . '/' . TrackingAssets::LOADER);

        // NFR-PERF-tracking-render-impact: banner + loader ≤ 15 KB compressed (checked together in
        // TrackingBannerTest); the loader alone (TC-3 added the e-commerce events) stays under 9 KB.
        $this->assertLessThan(9 * 1024, strlen(gzencode($source, 9)));
    }
}
