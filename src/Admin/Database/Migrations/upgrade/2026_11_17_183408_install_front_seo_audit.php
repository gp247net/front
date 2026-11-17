<?php

use GP247\Front\Support\SeoUrlLifecycleInstaller;
use Illuminate\Database\Migrations\Migration;

/**
 * Idempotent upgrade (SEO phase 3): the "SEO check" entry under the SEO admin menu.
 * No table. A fresh install writes the same row from DataFrontDefaultSeeder.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-audit
 * @aidlc-adr ADR-seo-audit
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up()
    {
        SeoUrlLifecycleInstaller::seedAuditMenu();
    }

    /**
     * Irreversible on purpose: a menu row an older front simply does not route.
     *
     * @return void
     */
    public function down()
    {
    }
};
