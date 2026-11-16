<?php

use GP247\Front\Support\TemplateCustomizeMenu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Running sites: the "Customize theme" entry moves from admin::template/customize (front 3.10.0) to admin::theme, the
 * shared root of template settings pages, so the sidebar lights it on any of them (US-FADM-template-customize-entry).
 *
 * Changes the uri of the existing row — never a second row; inserts it when missing (a site coming straight from 3.9
 * already got the new address from the 2026_11_16 migration, then this is a no-op). Idempotent. down() moves it back.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-template-customize-entry
 * @aidlc-adr front-admin_template-settings-entry
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up()
    {
        if (Schema::connection(GP247_DB_CONNECTION)->hasTable(GP247_DB_PREFIX.'admin_menu')) {
            TemplateCustomizeMenu::ensure(DB::connection(GP247_DB_CONNECTION));
        }
    }

    /**
     * @return void
     */
    public function down()
    {
        if (Schema::connection(GP247_DB_CONNECTION)->hasTable(GP247_DB_PREFIX.'admin_menu')) {
            TemplateCustomizeMenu::moveBack(DB::connection(GP247_DB_CONNECTION));
        }
    }
};
