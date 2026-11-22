<?php

namespace GP247\Front\Tests\Feature\FrontAdmin;

use GP247\Front\Admin\Database\Seeders\DataFrontLanguageSeeder;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use GP247\Front\Tests\TestCase;

/**
 * The layout-block screen shows real words — not language codes — under the
 * content field: a help line for HTML blocks and a notice when no view block is
 * available. New installs get them from the front language seeder, installed
 * sites through gp247:front-update.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-004
 */
class LayoutBlockHelpLabelTest extends TestCase
{
    use DatabaseTransactions;

    private const CODES = ['admin.layout_block.text_help', 'admin.layout_block.view_empty'];
    private const MIGRATION = 'vendor/gp247/front/src/Admin/Database/Migrations/upgrade/2026_10_22_230000_seed_layout_block_help_labels.php';

    /**
     * @return ConnectionInterface
     */
    private function db(): ConnectionInterface
    {
        return DB::connection(GP247_DB_CONNECTION);
    }

    public function test_new_install_ships_both_labels_in_every_default_language(): void
    {
        $rows = (new DataFrontLanguageSeeder())->dataLang();
        foreach (self::CODES as $code) {
            $locations = array_column(array_filter($rows, fn ($r) => $r['code'] === $code), 'location');
            sort($locations);
            $this->assertSame(['en', 'ja', 'vi'], $locations, $code);
        }
    }

    public function test_installed_site_receives_the_labels_without_losing_edits(): void
    {
        $table = GP247_DB_PREFIX.'languages';
        $this->db()->table($table)->whereIn('code', self::CODES)->delete();
        $this->db()->table($table)->insert([
            'code' => self::CODES[0], 'text' => 'owner text', 'position' => 'admin.layout_block', 'location' => 'en',
        ]);

        $migration = require base_path(self::MIGRATION);
        $migration->up();
        $migration->up();

        $this->assertSame(6, $this->db()->table($table)->whereIn('code', self::CODES)->count());
        $this->assertSame('owner text', $this->db()->table($table)
            ->where('code', self::CODES[0])->where('location', 'en')->value('text'));
    }

    public function test_the_screen_uses_the_view_empty_notice_for_an_empty_block_list(): void
    {
        $blade = (string) file_get_contents(base_path('vendor/gp247/front/src/Views/admin/layout-block-manager.blade.php'));

        $this->assertStringContainsString("gp247_language_render('admin.layout_block.view_empty')", $blade);
        $this->assertStringContainsString(":help=\"gp247_language_render('admin.layout_block.text_help')\"", $blade);
    }
}
