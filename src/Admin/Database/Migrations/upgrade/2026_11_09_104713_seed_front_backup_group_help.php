<?php

use GP247\Front\Support\BackupGroupHelp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Running sites: the descriptions of gp247/front's part of the backup groups, shown on the backup and
 * restore screens of the core. Fresh installs get them from DataFrontLanguageSeeder.
 * insertOrIgnore keeps any text a site owner already edited. Idempotent.
 *
 * Runs via gp247:front-update (--path upgrade/), never the create-tables migration.
 *
 * @aidlc-unit backup-engine
 * @aidlc-story US-backup-engine-data-groups
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up()
    {
        if (!Schema::connection(GP247_DB_CONNECTION)->hasTable(GP247_DB_PREFIX.'languages')) {
            return;
        }
        DB::connection(GP247_DB_CONNECTION)->table(GP247_DB_PREFIX.'languages')->insertOrIgnore(BackupGroupHelp::languageRows());
    }

    /**
     * @return void
     */
    public function down()
    {
        // Intentionally empty: texts are harmless and may have been edited by the site.
    }
};
