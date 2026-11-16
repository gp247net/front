<?php

use GP247\Front\Support\TemplateCustomizeMenu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Running sites: add the fixed "Customize theme" entry to the Extension group of the admin menu
 * (US-FADM-template-customize-entry). Fresh installs get it from DataFrontDefaultSeeder; its label comes from
 * DataFrontLanguageSeeder, which gp247:front-update runs.
 *
 * Only inserts, found by uri — idempotent. down() removes the entry.
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
            TemplateCustomizeMenu::remove(DB::connection(GP247_DB_CONNECTION));
        }
    }
};
