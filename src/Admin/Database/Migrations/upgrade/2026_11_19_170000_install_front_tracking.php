<?php

use GP247\Front\Tracking\Infrastructure\TrackingInstaller;
use Illuminate\Database\Migrations\Migration;

/**
 * Idempotent upgrade (tracking-consent): the custom code + revision tables, the default
 * tracking settings on ROOT (nothing set, switch on, banner off — Q21) and the
 * "Tracking code & cookies" menu entry. A fresh install writes the same through the
 * create-tables migration and DataFrontDefaultSeeder; labels ship in
 * DataFrontLanguageSeeder, which gp247:front-update runs after the migrations.
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-install-lifecycle
 * @aidlc-adr tracking-consent_config-storage-and-authority
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up()
    {
        TrackingInstaller::createTables();
        TrackingInstaller::seedConfig();
        TrackingInstaller::seedAdmin();
    }

    /**
     * Irreversible on purpose: dropping the tables would lose the site owner's code blocks
     * and their revisions; an older front simply ignores them.
     *
     * @return void
     */
    public function down()
    {
    }
};
