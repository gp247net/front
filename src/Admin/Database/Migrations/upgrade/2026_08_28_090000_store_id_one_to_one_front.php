<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent upgrade for existing GP247 installs: standardize front (CMS) store
 * ownership to a single 1-1 scalar `store_id` column and retire the N-N store
 * pivots (modification 20260828T090911).
 *
 * WHY this lives in a dedicated upgrade/ subdirectory, not the Migrations root:
 * gp247:front-install runs the create-tables migration by explicit --path, which
 * DROPS and recreates every front table. A fresh install already gets `store_id`
 * from that file, so this migration is only for sites installed before this
 * change and must NOT lose their store assignments. It is wired to the new
 * gp247:front-update command (migrate --path=.../upgrade --force).
 *
 * Backfill rule (per ADR multi-store_one-to-one-store-ownership): a record owned
 * by exactly one store keeps that store; a record shared across N>1 stores
 * collapses to the ROOT store (the column default already lands every row on
 * ROOT, so only the single-store rows need an explicit override). The pivot
 * tables are then dropped. down() only restores the columns — it deliberately
 * does NOT rebuild the many-to-many data (documented, accepted trade-off).
 *
 * @aidlc-unit compat-foundation
 * @aidlc-story US-CMP-store-one-to-one
 * @aidlc-adr multi-store_one-to-one-store-ownership
 */
return new class extends Migration
{
    /**
     * Front tables that gain a scalar `store_id` (owner) column. The taxonomy
     * tables (banner_type, link_group) never had a store pivot — they only need
     * the column (default ROOT); banner/link/page additionally backfill from
     * their retired pivot.
     *
     * @var string[]
     */
    private array $ownedTables = [
        'front_banner',
        'front_banner_type',
        'front_link',
        'front_link_group',
        'front_page',
    ];

    /**
     * Retired store pivots keyed by the owning entity table, with the pivot's
     * foreign-key column pointing back at that entity.
     *
     * @var array<string, array{pivot: string, fk: string}>
     */
    private array $pivots = [
        'front_banner' => ['pivot' => 'front_banner_store', 'fk' => 'banner_id'],
        'front_link'   => ['pivot' => 'front_link_store', 'fk' => 'link_id'],
        'front_page'   => ['pivot' => 'front_page_store', 'fk' => 'page_id'],
    ];

    /**
     * Add the `store_id` column where missing, backfill from the pivots, then
     * drop the pivots. Every step is guarded so re-running is a no-op.
     *
     * @return void
     */
    public function up()
    {
        $schema = Schema::connection(GP247_DB_CONNECTION);

        foreach ($this->ownedTables as $table) {
            $fullName = GP247_DB_PREFIX.$table;
            if (!$schema->hasTable($fullName)) {
                // WHY: site may not have this front table yet (partial install);
                // skip rather than fail (NFR-MAINT-001).
                continue;
            }

            $schema->table($fullName, function (Blueprint $blueprint) use ($schema, $fullName) {
                if (!$schema->hasColumn($fullName, 'store_id')) {
                    // Store ownership: each record belongs to exactly one store (1-1).
                    // default(1) lands every existing row on the ROOT store; the
                    // single-store backfill below overrides only where warranted.
                    $blueprint->uuid('store_id')->default(1)->index();
                }
            });
        }

        foreach ($this->pivots as $entityTable => $meta) {
            $this->backfillFromPivot($schema, $entityTable, $meta['pivot'], $meta['fk']);
        }

        // Retire the pivots once ownership has been collapsed onto the entity.
        foreach ($this->pivots as $meta) {
            $schema->dropIfExists(GP247_DB_PREFIX.$meta['pivot']);
        }
    }

    /**
     * Collapse a many-to-many store pivot onto the entity's scalar `store_id`:
     * an entity present in exactly one store adopts that store; entities in
     * several stores are left on the ROOT default.
     *
     * The pivot/entity table names come from the trusted GP247_DB_PREFIX
     * constant (never user input); all values are bound by the query builder.
     * No string-concatenated user data reaches SQL (security.md).
     *
     * @param Builder $schema      Schema builder for the front connection.
     * @param string  $entityTable Unprefixed owning-entity table (e.g. front_banner).
     * @param string  $pivotTable  Unprefixed pivot table (e.g. front_banner_store).
     * @param string  $fk          Pivot column referencing the entity (e.g. banner_id).
     * @return void
     */
    private function backfillFromPivot(Builder $schema, string $entityTable, string $pivotTable, string $fk): void
    {
        $pivotFull = GP247_DB_PREFIX.$pivotTable;
        $entityFull = GP247_DB_PREFIX.$entityTable;

        if (!$schema->hasTable($pivotFull) || !$schema->hasColumn($entityFull, 'store_id')) {
            return;
        }

        $db = DB::connection(GP247_DB_CONNECTION);

        // Entities owned by exactly one store: entity id => that single store id.
        // MIN() is the lone value once COUNT(DISTINCT store_id) = 1.
        $assignments = $db->table($pivotFull)
            ->select($fk, $db->raw('MIN(store_id) as owner_store_id'))
            ->groupBy($fk)
            ->havingRaw('COUNT(DISTINCT store_id) = 1')
            ->pluck('owner_store_id', $fk);

        if ($assignments->isEmpty()) {
            return;
        }

        // Group entity ids by owning store so each store is one batched UPDATE.
        $byStore = [];
        foreach ($assignments as $entityId => $storeId) {
            $byStore[(string) $storeId][] = $entityId;
        }

        foreach ($byStore as $storeId => $entityIds) {
            foreach (array_chunk($entityIds, 500) as $chunk) {
                $db->table($entityFull)
                    ->whereIn('id', $chunk)
                    ->update(['store_id' => $storeId]);
            }
        }
    }

    /**
     * Restore the `store_id` columns dropped by a rollback. The pivots and their
     * many-to-many data are intentionally NOT recreated — that information is
     * lost the moment ownership collapses (documented, accepted trade-off).
     *
     * @return void
     */
    public function down()
    {
        $schema = Schema::connection(GP247_DB_CONNECTION);

        foreach ($this->ownedTables as $table) {
            $fullName = GP247_DB_PREFIX.$table;
            if (!$schema->hasTable($fullName)) {
                continue;
            }
            $schema->table($fullName, function (Blueprint $blueprint) use ($schema, $fullName) {
                if ($schema->hasColumn($fullName, 'store_id')) {
                    $blueprint->dropColumn('store_id');
                }
            });
        }
    }
};
