<?php

namespace GP247\Front\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the per-content SEO tables. One definition shared by the create-tables
 * migration (fresh install) and the idempotent upgrade migration (existing sites),
 * so the two paths cannot drift (RISK-TECH-fresh-install-upgrade-gap).
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-content-meta, US-SEO-content-index-control
 * @aidlc-adr ADR-seo-content-meta-table
 */
class SeoMetaInstaller
{
    /** Content-level row: one per (type, content_id). */
    public const TABLE_META = 'front_seo_meta';

    /** Language-level row: one per (seo_meta_id, lang). */
    public const TABLE_DESCRIPTION = 'front_seo_meta_description';

    /**
     * Create both tables when missing; a table that already exists is left untouched.
     *
     * @return void
     */
    public static function createTables(): void
    {
        $schema = Schema::connection(GP247_DB_CONNECTION);

        if (!$schema->hasTable(GP247_DB_PREFIX . self::TABLE_META)) {
            $schema->create(GP247_DB_PREFIX . self::TABLE_META, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('type', 50);
                // WHY string: content ids are uuid (product, page…) or integers (tag, plugins).
                $table->string('content_id', 64);
                $table->tinyInteger('noindex')->default(0);
                $table->tinyInteger('nofollow')->default(0);
                $table->string('og_image', 255)->nullable();
                $table->timestamps();
                $table->unique(['type', 'content_id']);
                // WHY: the sitemap filters "noindex content of this type".
                $table->index(['type', 'noindex']);
            });
        }

        if (!$schema->hasTable(GP247_DB_PREFIX . self::TABLE_DESCRIPTION)) {
            $schema->create(GP247_DB_PREFIX . self::TABLE_DESCRIPTION, function (Blueprint $table) {
                $table->unsignedBigInteger('seo_meta_id');
                $table->string('lang', 10);
                $table->string('title', 255)->nullable();
                $table->string('description', 500)->nullable();
                $table->string('canonical', 500)->nullable();
                $table->unique(['seo_meta_id', 'lang']);
            });
        }
    }

    /**
     * Drop both tables. Only reached by a fresh front install/uninstall
     * (create-tables migration down()); the SEO values typed by the site owner are lost.
     *
     * @return void
     */
    public static function dropTables(): void
    {
        $schema = Schema::connection(GP247_DB_CONNECTION);
        $schema->dropIfExists(GP247_DB_PREFIX . self::TABLE_DESCRIPTION);
        $schema->dropIfExists(GP247_DB_PREFIX . self::TABLE_META);
        \GP247\Front\Library\SeoContentMeta::resetTableReady();
    }
}
