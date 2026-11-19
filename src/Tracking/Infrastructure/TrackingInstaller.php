<?php

namespace GP247\Front\Tracking\Infrastructure;

use GP247\Front\Tracking\Application\TrackingSettingsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One source for what the "Tracking code & cookies" screen needs in the database: the
 * custom code and revision tables, the default settings on the ROOT store and the menu
 * entry. Called by the create-tables migration and DataFrontDefaultSeeder (fresh
 * install) and by the upgrade migration (sites already installed), so both paths write
 * the same thing. Every step is re-runnable and never deletes or overwrites a value.
 * Labels ship in DataFrontLanguageSeeder (policy of modification 20261114T223618).
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-install-lifecycle
 * @aidlc-adr tracking-consent_config-storage-and-authority
 */
final class TrackingInstaller
{
    public const TABLE_SNIPPET = 'front_tracking_snippet';

    public const TABLE_REVISION = 'front_tracking_revision';

    public const MENU_URI = 'admin::tracking';

    /**
     * Create the two tables when missing.
     *
     * @return void
     */
    public static function createTables(): void
    {
        $schema = Schema::connection(GP247_DB_CONNECTION);

        if (!$schema->hasTable(GP247_DB_PREFIX . self::TABLE_SNIPPET)) {
            $schema->create(GP247_DB_PREFIX . self::TABLE_SNIPPET, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('store_id');
                $table->string('region', 20);
                $table->string('category', 20);
                $table->string('label', 100)->default('');
                $table->mediumText('content');
                $table->integer('sort')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
                $table->index(['store_id', 'status']);
            });
        }

        if (!$schema->hasTable(GP247_DB_PREFIX . self::TABLE_REVISION)) {
            $schema->create(GP247_DB_PREFIX . self::TABLE_REVISION, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('store_id');
                $table->string('admin_user_id', 36)->nullable();
                $table->string('admin_user_name', 100)->nullable();
                $table->mediumText('before');
                $table->mediumText('after');
                $table->timestamp('created_at')->nullable();
                $table->index(['store_id', 'id']);
            });
        }
    }

    /**
     * Default settings on the ROOT store; rows already there keep their value
     * (unique key, store_id).
     *
     * @return void
     */
    public static function seedConfig(): void
    {
        $root = (string) (defined('GP247_STORE_ID_ROOT') ? GP247_STORE_ID_ROOT : 1);
        $rows = [];
        $sort = 0;
        foreach (TrackingSettingsService::DEFAULTS as $key => $value) {
            $rows[] = [
                'group' => TrackingSettingsService::GROUP,
                'code' => TrackingSettingsService::CODE,
                'key' => $key,
                'value' => (string) $value,
                'sort' => $sort++,
                'detail' => 'admin.tracking.config.' . $key,
                'store_id' => $root,
            ];
        }

        DB::connection(GP247_DB_CONNECTION)->table(GP247_DB_PREFIX . 'admin_config')->insertOrIgnore($rows);
    }

    /**
     * Add the menu entry under the SEO group when no row has its uri yet. No permission
     * is seeded: the screen is for administrators only and checks that itself.
     *
     * @return void
     */
    public static function seedAdmin(): void
    {
        $db = DB::connection(GP247_DB_CONNECTION);
        $menu = GP247_DB_PREFIX . 'admin_menu';
        if ($db->table($menu)->where('uri', self::MENU_URI)->exists()) {
            return;
        }
        $parent = $db->table($menu)->where('key', 'ADMIN_SEO')->value('id');
        if ($parent) {
            $db->table($menu)->insert(self::menuRow((int) $parent));
        }
    }

    /**
     * The menu row, shared with DataFrontDefaultSeeder so both install paths write the same row.
     *
     * @param int $parentId Id of the ADMIN_SEO group.
     * @return array<string, mixed>
     */
    public static function menuRow(int $parentId): array
    {
        return [
            'parent_id' => $parentId,
            'sort' => 6,
            'title' => 'admin.menu_titles.tracking',
            'icon' => 'fas fa-chart-line',
            'uri' => self::MENU_URI,
            'key' => null,
            'type' => 0,
        ];
    }

    /**
     * Drop both tables (fresh front reinstall only).
     *
     * WARNING: loses every custom code block and its revisions.
     *
     * @return void
     */
    public static function dropTables(): void
    {
        $schema = Schema::connection(GP247_DB_CONNECTION);
        $schema->dropIfExists(GP247_DB_PREFIX . self::TABLE_SNIPPET);
        $schema->dropIfExists(GP247_DB_PREFIX . self::TABLE_REVISION);
    }
}
