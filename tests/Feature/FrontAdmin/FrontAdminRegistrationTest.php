<?php

namespace GP247\Front\Tests\Feature\FrontAdmin;

use GP247\Core\Models\Languages;
use GP247\Front\Admin\Database\Seeders\DataFrontDefaultSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use GP247\Front\Tests\TestCase;

/**
 * Proves the front-admin Unit is plugged into the core admin shell after the
 * cutover (PA-1): the modern screens are served in-place at the legacy admin URLs
 * (the parallel front-admin/* routes are gone), the view namespace resolves, and
 * the menu/lang seeders are idempotent.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-006
 * @aidlc-adr ADR-006, ADR-007
 */
class FrontAdminRegistrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_front_admin_screens_are_served_at_legacy_urls(): void
    {
        // Cutover (PA-1): legacy URLs now resolve to the modern Livewire screens.
        $this->assertTrue(Route::has('admin_banner.index'));
        $this->assertTrue(Route::has('admin_banner.create'));
        $this->assertTrue(Route::has('admin_banner.edit'));
        $this->assertTrue(Route::has('admin_page.index'));
        $this->assertTrue(Route::has('admin_link.index'));
        $this->assertTrue(Route::has('admin_layout_block.index'));

        // The interim parallel routes are removed.
        $this->assertFalse(Route::has('gp247.front-admin.banner'));
    }

    public function test_component_view_namespace_is_registered(): void
    {
        $this->assertTrue(view()->exists('gp247-front-admin::banner-list'));
        $this->assertTrue(view()->exists('gp247-front-admin::banner-form'));
    }

    public function test_lang_seeder_seeds_en_and_vi_idempotently(): void
    {
        // FrontAdminLangSeeder was merged into DataFrontDefaultSeeder (2026-07-01).
        (new DataFrontDefaultSeeder())->run();
        (new DataFrontDefaultSeeder())->run();

        $this->assertSame(1, Languages::where('code', 'admin.banner.title')->where('location', 'en')->count());
        $this->assertSame(1, Languages::where('code', 'admin.banner.title')->where('location', 'vi')->count());
    }
}
