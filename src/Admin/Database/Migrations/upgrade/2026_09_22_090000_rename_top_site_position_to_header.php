<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent upgrade: move the LayoutBlock head-code position from the key
 * 'top_site' to 'header' on installed sites. Fresh installs are born with
 * 'header' — see Config/config.php ('gp247-config.front.layout_position') and
 * DataFrontLanguageSeeder.
 *
 * WHY: the position key is matched at render time against the literal each
 * template emits, and the only call that reaches the document <head> is
 * gp247_render_block('header') in GP247Front's layout.blade.php. The registry
 * offered 'top_site' instead, which no template ever renders, so the admin
 * dropdown's only head-code option produced markup that appeared nowhere — the
 * block saved fine and then silently did nothing. Verified before the fix by
 * inserting one block per key and rendering the home page: the 'top_site' marker
 * was absent from the response, the 'header' marker landed inside <head>.
 *
 * Renaming the language row (rather than seeding a new code and deleting the old)
 * carries over a label a site owner edited in the admin Language manager. The
 * insertOrIgnore below then covers a site whose row was missing entirely, and the
 * delete clears a 'top_site' row left behind when a 'header' row already occupied
 * the (code, location) unique key. The vi row also moves to the
 * 'admin.layout_block' position group, where all its siblings live — it was the
 * lone row filed under 'admin.layout_page_block'.
 *
 * Blocks already filed under 'top_site' move to 'header', so head code a site
 * configured earlier starts rendering where it was always meant to. No cron/queue
 * (NFR-AVAIL-001). Runs via gp247:front-update (--path upgrade/), never the
 * create-tables migration.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-004
 */
return new class extends Migration
{
    /**
     * Carry the label and the blocks over to the 'header' key.
     *
     * @return void
     */
    public function up()
    {
        $languages = DB::connection(GP247_DB_CONNECTION)->table(GP247_DB_PREFIX.'languages');

        // Keep an edited translation: rename in place, skipping any locale that
        // already holds a 'header' row (the (code, location) unique key).
        $taken = (clone $languages)
            ->where('code', 'admin.layout_block_position.header')
            ->pluck('location')
            ->all();

        (clone $languages)
            ->where('code', 'admin.layout_block_position.top_site')
            ->when($taken !== [], fn ($q) => $q->whereNotIn('location', $taken))
            ->update([
                'code' => 'admin.layout_block_position.header',
                'position' => 'admin.layout_block',
            ]);

        // A site missing the row entirely still gets a label.
        (clone $languages)->insertOrIgnore([
            ['code' => 'admin.layout_block_position.header', 'text' => 'Vị trí Head code :meta, css, javascript,...', 'position' => 'admin.layout_block', 'location' => 'vi'],
            ['code' => 'admin.layout_block_position.header', 'text' => 'Position Head code: meta, css, javascript, ...', 'position' => 'admin.layout_block', 'location' => 'en'],
        ]);

        // Nothing reads 'top_site' any more; leaving it would clutter the admin
        // Language manager with a key no screen resolves.
        (clone $languages)->where('code', 'admin.layout_block_position.top_site')->delete();

        DB::connection(GP247_DB_CONNECTION)
            ->table(GP247_DB_PREFIX.'front_layout_block')
            ->where('position', 'top_site')
            ->update(['position' => 'header']);
    }

    /**
     * Put the label and the blocks back under 'top_site'.
     *
     * @return void
     */
    public function down()
    {
        $languages = DB::connection(GP247_DB_CONNECTION)->table(GP247_DB_PREFIX.'languages');

        $taken = (clone $languages)
            ->where('code', 'admin.layout_block_position.top_site')
            ->pluck('location')
            ->all();

        (clone $languages)
            ->where('code', 'admin.layout_block_position.header')
            ->when($taken !== [], fn ($q) => $q->whereNotIn('location', $taken))
            ->update(['code' => 'admin.layout_block_position.top_site']);

        DB::connection(GP247_DB_CONNECTION)
            ->table(GP247_DB_PREFIX.'front_layout_block')
            ->where('position', 'header')
            ->update(['position' => 'top_site']);
    }
};
