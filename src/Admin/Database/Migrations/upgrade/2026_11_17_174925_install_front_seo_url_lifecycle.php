<?php

use GP247\Front\Support\SeoUrlLifecycleInstaller;
use Illuminate\Database\Migrations\Migration;

/**
 * Idempotent upgrade (SEO phase 2): the 404 log table, the `source` column of
 * front_redirects (existing rows become `manual`) and the "404 log" admin menu entry.
 * Nothing existing is changed or deleted; the 404 log and IndexNow stay off.
 *
 * Runs via gp247:front-update (called by gp247:update). A fresh install creates the
 * same schema and menu row from the create-tables migration and the default seeder.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-not-found-log, US-SEO-alias-auto-redirect
 * @aidlc-adr ADR-seo-url-lifecycle
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up()
    {
        SeoUrlLifecycleInstaller::install();
        SeoUrlLifecycleInstaller::seedAdmin();
    }

    /**
     * Irreversible on purpose: the log and the redirect source are site data; an older
     * front ignores them.
     *
     * @return void
     */
    public function down()
    {
    }
};
