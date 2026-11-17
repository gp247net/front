<?php

namespace GP247\Front\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Schema and admin rows of the URL lifecycle (SEO phase 2): the 404 log table, the
 * `source` column of the redirect table and the "404 log" menu entry. One definition
 * shared by the fresh install and the idempotent upgrade migration
 * (RISK-TECH-fresh-install-upgrade-gap).
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-not-found-log, US-SEO-alias-auto-redirect
 * @aidlc-adr ADR-seo-url-lifecycle
 */
class SeoUrlLifecycleInstaller
{
    public const TABLE_NOT_FOUND = 'front_not_found_log';

    public const MENU_URI = 'admin::seo_not_found';

    /** Menu of the SEO check screen (SEO phase 3). */
    public const AUDIT_MENU_URI = 'admin::seo_audit';

    /**
     * Create the 404 log table and add the redirect `source` column when missing.
     *
     * @return void
     */
    public static function install(): void
    {
        $schema = Schema::connection(GP247_DB_CONNECTION);

        if (!$schema->hasTable(GP247_DB_PREFIX . self::TABLE_NOT_FOUND)) {
            $schema->create(GP247_DB_PREFIX . self::TABLE_NOT_FOUND, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('store_id');
                $table->string('path', 500);
                // WHY a hash: a 500-char utf8mb4 path is too long for a unique index.
                $table->char('path_hash', 40);
                $table->unsignedInteger('hits')->default(1);
                $table->string('referer', 500)->nullable();
                $table->timestamp('first_seen_at')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->unique(['store_id', 'path_hash']);
                $table->index(['store_id', 'last_seen_at']);
            });
        }

        $redirects = GP247_DB_PREFIX . 'front_redirects';
        if ($schema->hasTable($redirects) && !$schema->hasColumn($redirects, 'source')) {
            $schema->table($redirects, function (Blueprint $table) {
                $table->string('source', 10)->default('manual');
            });
        }
    }

    /**
     * Add the "404 log" entry under the SEO menu group when missing.
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
        if (!$parent) {
            return;
        }
        $db->table($menu)->insert(self::menuRow((int) $parent));
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
            'sort' => 4,
            'title' => 'admin.menu_titles.seo_not_found',
            'icon' => 'fas fa-unlink',
            'uri' => self::MENU_URI,
            'key' => null,
            'type' => 0,
        ];
    }

    /**
     * Add the "SEO check" entry under the SEO menu group when missing (SEO phase 3).
     *
     * @return void
     *
     * @aidlc-story US-SEO-audit
     */
    public static function seedAuditMenu(): void
    {
        $db = DB::connection(GP247_DB_CONNECTION);
        $menu = GP247_DB_PREFIX . 'admin_menu';
        if ($db->table($menu)->where('uri', self::AUDIT_MENU_URI)->exists()) {
            return;
        }
        $parent = $db->table($menu)->where('key', 'ADMIN_SEO')->value('id');
        if ($parent) {
            $db->table($menu)->insert(self::auditMenuRow((int) $parent));
        }
    }

    /**
     * The "SEO check" menu row, shared with DataFrontDefaultSeeder.
     *
     * @param int $parentId Id of the ADMIN_SEO group.
     * @return array<string, mixed>
     *
     * @aidlc-story US-SEO-audit
     */
    public static function auditMenuRow(int $parentId): array
    {
        return [
            'parent_id' => $parentId,
            'sort' => 5,
            'title' => 'admin.menu_titles.seo_audit',
            'icon' => 'fas fa-clipboard-check',
            'uri' => self::AUDIT_MENU_URI,
            'key' => null,
            'type' => 0,
        ];
    }

    /**
     * Drop the 404 log table (fresh front reinstall only; the log is lost).
     *
     * @return void
     */
    public static function dropTables(): void
    {
        Schema::connection(GP247_DB_CONNECTION)->dropIfExists(GP247_DB_PREFIX . self::TABLE_NOT_FOUND);
    }
}
