<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index on `updated_at` of the tables behind the front admin API lists, used by the
 * changed-since mode (`updated_since`, keyset paging). Idempotent and add-only: a table or
 * index already there is left as it is. On large tables MySQL / MariaDB build it online
 * (InnoDB); run it out of peak hours on a very large shop.
 *
 * @aidlc-unit api-sync
 * @aidlc-story US-api-sync-changed-since-filter
 * @aidlc-adr api-sync_keyset-changed-since
 */
return new class extends Migration
{
    /** @var string[] Unprefixed table names. */
    private const TABLES = ['front_banner', 'front_page'];

    /**
     * @return void
     */
    public function up()
    {
        $schema = Schema::connection(GP247_DB_CONNECTION);
        foreach (self::TABLES as $name) {
            $table = GP247_DB_PREFIX . $name;
            if ($schema->hasTable($table) && $schema->hasColumn($table, 'updated_at') && !$schema->hasIndex($table, ['updated_at'])) {
                $schema->table($table, fn (Blueprint $blueprint) => $blueprint->index('updated_at'));
            }
        }
    }

    /**
     * Drops only the index added here (no data is touched).
     *
     * @return void
     */
    public function down()
    {
        $schema = Schema::connection(GP247_DB_CONNECTION);
        foreach (self::TABLES as $name) {
            $table = GP247_DB_PREFIX . $name;
            if ($schema->hasTable($table) && $schema->hasIndex($table, ['updated_at'])) {
                $schema->table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex(['updated_at']));
            }
        }
    }
};
