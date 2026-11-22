<?php

namespace GP247\Front\Tests\Feature\Seo;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use GP247\Front\Tests\TestCase;

/**
 * An existing site gets the 404 log table, the redirect source column and the
 * "404 log" menu entry through gp247:update; running it again changes nothing
 * (NFR-MAINT-breaking-change-migration).
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-not-found-log, US-SEO-alias-auto-redirect
 * @aidlc-adr ADR-seo-url-lifecycle
 */
class SeoUrlLifecycleUpgradeMigrationTest extends TestCase
{
    use DatabaseTransactions;

    private const MIGRATION = 'vendor/gp247/front/src/Admin/Database/Migrations/upgrade/2026_11_17_174925_install_front_seo_url_lifecycle.php';

    public function test_running_the_upgrade_twice_keeps_one_menu_entry_and_the_schema(): void
    {
        $migration = require base_path(self::MIGRATION);
        $migration->up();
        $migration->up();

        $this->assertTrue(Schema::hasTable(GP247_DB_PREFIX . 'front_not_found_log'));
        $this->assertTrue(Schema::hasColumns(GP247_DB_PREFIX . 'front_not_found_log', ['store_id', 'path', 'path_hash', 'hits', 'referer', 'first_seen_at', 'last_seen_at']));
        $this->assertTrue(Schema::hasColumn(GP247_DB_PREFIX . 'front_redirects', 'source'));
        $this->assertSame(1, DB::table(GP247_DB_PREFIX . 'admin_menu')->where('uri', 'admin::seo_not_found')->count());
    }
}
