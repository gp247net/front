<?php

use GP247\Front\Support\SeoMetaInstaller;
use Illuminate\Database\Migrations\Migration;

/**
 * Idempotent upgrade: create the per-content SEO tables on a site that already runs
 * front. Existing tables are left untouched; nothing is deleted or overwritten. A site
 * without rows behaves exactly as before (no own SEO values = generated values).
 *
 * Runs via gp247:front-update (--path upgrade/), which gp247:update calls. A fresh
 * install creates the same tables from the create-tables migration and marks this file
 * as applied (UpgradeMigrationBaseline).
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-content-meta, US-SEO-content-index-control
 * @aidlc-adr ADR-seo-content-meta-table
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up()
    {
        SeoMetaInstaller::createTables();
    }

    /**
     * Irreversible on purpose: the tables hold SEO values typed by the site owner, and
     * an older front simply ignores them. Drop them by hand only after exporting.
     *
     * @return void
     */
    public function down()
    {
    }
};
