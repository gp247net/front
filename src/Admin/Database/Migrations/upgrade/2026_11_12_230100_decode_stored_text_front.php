<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Running sites: give back the plain text of front tables that gp247_clean() HTML-encoded on
 * save before core 3.12 ("Cron &amp; Queue" ⇒ "Cron & Queue"), once, so text is stored as
 * typed (US-CMP-text-stored-as-typed, decision D2). Column list approved 2026-11-12 (rule
 * data-safety §C, modification_plan_20261112T061240): only plain-text columns; never rich
 * content, JSON, codes, aliases or e-mail addresses.
 * Only columns whose every write path went through gp247_clean(); left out on purpose:
 * front_contact (the contact form stores what the visitor typed) and front_subscribe.phone/content
 * (no write path cleans them).
 *
 * CHANGES DATA — back up the database before gp247:update. Idempotent through the
 * `text_storage_decoded_front` flag; rows are read in chunks and updated by primary key.
 * Needs core 3.12 (GP247\Core\Support\StoredTextDecoder); on an older core it does nothing
 * and leaves the flag unset, so it runs after core is updated.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-CMP-text-stored-as-typed
 * @aidlc-adr compat-foundation_text-stored-as-typed
 */
return new class extends Migration
{
    /**
     * Table (without prefix) => plain-text columns to decode.
     *
     * @return array<string, array<int, string>>
     */
    public static function columns(): array
    {
        return [
            'front_page_description' => ['name', 'keyword', 'description'],
            'front_link' => ['name', 'url'],
            'front_link_group' => ['name'],
            'front_banner' => ['name', 'url'],
            'front_banner_type' => ['name'],
            'front_layout_block' => ['name'],
        ];
    }

    /**
     * Decode once.
     *
     * @return void
     */
    public function up()
    {
        if (!class_exists(\GP247\Core\Support\StoredTextDecoder::class)) {
            return;
        }
        \GP247\Core\Support\StoredTextDecoder::run('text_storage_decoded_front', self::columns());
    }

    /**
     * Irreversible: encoding the text again would bring back "&amp;" everywhere. Restore the
     * backup taken before gp247:update to go back.
     *
     * @return void
     */
    public function down()
    {
        // Irreversible on purpose — see method doc.
    }
};
