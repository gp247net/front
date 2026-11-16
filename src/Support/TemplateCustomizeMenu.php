<?php

namespace GP247\Front\Support;

use Illuminate\Database\ConnectionInterface;

/**
 * The fixed "Customize theme" admin menu entry, in the Extension group right under the Template manager. It opens the
 * settings of the template the working store uses (AdminTemplateController::customize), so templates never add menu
 * entries of their own.
 *
 * WHY found by uri, no fixed id: core, gp247/shop and plugins insert menu rows with auto-increment ids, so a fixed id
 * may be taken on a running site. One helper serves the install seeder and the upgrade migration (same pattern as
 * core's SystemAlertsMenu).
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-template-customize-entry
 * @aidlc-adr front-admin_template-settings-entry
 */
final class TemplateCustomizeMenu
{
    public const URI = 'admin::template/customize';

    private const PARENT_KEY = 'ADMIN_EXTENSION';

    /**
     * Insert the entry when no row has its uri yet. Idempotent.
     *
     * @param ConnectionInterface $db Connection holding the admin tables.
     * @return void
     */
    public static function ensure(ConnectionInterface $db): void
    {
        $table = GP247_DB_PREFIX.'admin_menu';
        if ($db->table($table)->where('uri', self::URI)->exists()) {
            return;
        }

        $parent = (int) $db->table($table)->where('key', self::PARENT_KEY)->value('id');
        $db->table($table)->insert([
            'parent_id' => $parent,
            // WHY 0: the menu sorts descending — Plugins (2), Template manager (1), then this entry.
            'sort' => 0,
            'title' => 'admin.menu_titles.template_customize',
            'icon' => 'fas fa-palette',
            'uri' => self::URI,
            'key' => null,
            'type' => 0,
        ]);
    }

    /**
     * Remove the entry (migration rollback).
     *
     * @param ConnectionInterface $db Connection holding the admin tables.
     * @return void
     */
    public static function remove(ConnectionInterface $db): void
    {
        $db->table(GP247_DB_PREFIX.'admin_menu')->where('uri', self::URI)->delete();
    }
}
