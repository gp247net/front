<?php

use GP247\Front\Admin\Database\Seeders\DataFrontLanguageSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent upgrade for installed sites: add the Japanese (ja) front strings.
 *
 * gp247:front-update only runs upgrade/ migrations (it does not re-seed), so new
 * default-locale rows reach installed sites here. insertOrIgnore keeps text a
 * site owner already edited; nothing happens while the language table is still
 * empty (fresh installs get the rows from DataFrontLanguageSeeder).
 *
 * @aidlc-unit compat-foundation
 * @aidlc-story US-CMP-japanese-default-locale
 * @aidlc-adr compat-foundation_locale-data-files
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up()
    {
        $db = DB::connection(GP247_DB_CONNECTION);
        $table = GP247_DB_PREFIX.'languages';
        if (!$db->table($table)->exists()) {
            return;
        }
        $rows = array_values(array_filter(
            (new DataFrontLanguageSeeder())->dataLang(),
            fn ($row) => $row['location'] === 'ja'
        ));
        foreach (array_chunk($rows, 500) as $chunk) {
            $db->table($table)->insertOrIgnore($chunk);
        }
    }

    /**
     * Japanese strings are kept on rollback: they may hold the owner's edits and
     * are inert without the ja language.
     *
     * @return void
     */
    public function down()
    {
    }
};
