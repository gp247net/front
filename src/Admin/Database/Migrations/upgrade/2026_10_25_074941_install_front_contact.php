<?php

use GP247\Front\Support\ContactInstaller;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent upgrade: install the contact form on a site that already runs front —
 * the front_contact table, the settings (moving shop's two legacy mail toggles over
 * with their values), the admin menu entry, the two permissions and the labels.
 * Nothing is deleted or overwritten; the form starts switched off.
 *
 * Runs via gp247:front-update (--path upgrade/), never the create-tables migration.
 *
 * @aidlc-unit front-contact
 * @aidlc-story US-front-contact-config-tab, US-SADM-sendmail-contact-handover
 * @aidlc-adr front-contact_contact-form-in-front
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up()
    {
        ContactInstaller::createTable();
        $this->moveLegacyMailToggles();
        $this->seedConfig();
        ContactInstaller::seedAdmin();
        ContactInstaller::seedLabels();
    }

    /**
     * Public so a test can run the data steps inside its transaction.
     *
     * @return int Rows moved.
     */
    public function moveLegacyMailToggles(): int
    {
        return ContactInstaller::moveLegacyMailToggles();
    }

    /**
     * @return void
     */
    public function seedConfig(): void
    {
        ContactInstaller::seedConfig();
    }

    /**
     * Gives the two mail toggles back to shop's Send mail tab (values kept).
     * WARNING: deliberately keeps the front_contact table — it holds messages
     * visitors sent; drop it by hand only after exporting what you need. The
     * contact_status row, menu, permissions and labels also stay (harmless).
     *
     * @return void
     */
    public function down()
    {
        DB::connection(GP247_DB_CONNECTION)->table(GP247_DB_PREFIX . 'admin_config')
            ->whereIn('key', ContactInstaller::LEGACY_MAIL_KEYS)
            ->where('code', 'contact_config')
            ->update(['group' => 'gp247_cart', 'code' => 'sendmail_config']);
    }
};
