<?php

namespace GP247\Front\Tests\Feature\Seo;

use GP247\Front\Library\SeoContentMeta;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use GP247\Front\Tests\TestCase;

/**
 * An existing site receives the two SEO tables through gp247:update; running the
 * upgrade again changes nothing and keeps the data (NFR-MAINT-breaking-change-migration).
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-content-meta
 * @aidlc-adr ADR-seo-content-meta-table
 */
class SeoContentMetaUpgradeMigrationTest extends TestCase
{
    use DatabaseTransactions;

    private const MIGRATION = 'vendor/gp247/front/src/Admin/Database/Migrations/upgrade/2026_11_17_140612_install_front_seo_meta.php';

    public function test_running_the_upgrade_again_keeps_the_tables_and_their_data(): void
    {
        config(['gp247-config.front.seo_content_types.zz_upgrade' => ['label' => 'x']]);
        SeoContentMeta::save('zz_upgrade', 'zz-up-1', ['noindex' => true, 'desc' => ['en' => ['title' => 'ZZ Kept']]]);

        $migration = require base_path(self::MIGRATION);
        $migration->up();
        $migration->up();

        $this->assertTrue(Schema::hasTable(GP247_DB_PREFIX . 'front_seo_meta'));
        $this->assertTrue(Schema::hasTable(GP247_DB_PREFIX . 'front_seo_meta_description'));
        $saved = SeoContentMeta::for('zz_upgrade', 'zz-up-1', 'en');
        $this->assertTrue($saved['noindex']);
        $this->assertSame('ZZ Kept', $saved['title']);
    }

    public function test_the_tables_have_the_columns_the_design_names(): void
    {
        $meta = GP247_DB_PREFIX . 'front_seo_meta';
        $desc = GP247_DB_PREFIX . 'front_seo_meta_description';

        $this->assertTrue(Schema::hasColumns($meta, ['id', 'type', 'content_id', 'noindex', 'nofollow', 'og_image', 'created_at', 'updated_at']));
        $this->assertTrue(Schema::hasColumns($desc, ['seo_meta_id', 'lang', 'title', 'description', 'canonical']));
    }
}
