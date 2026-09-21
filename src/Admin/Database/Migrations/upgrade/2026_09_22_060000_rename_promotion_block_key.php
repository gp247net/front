<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Idempotent upgrade: move the home promotion strip from the block key
 * `shop_flash_sale` to `shop_product_promotion` on installed sites
 * (modification 20260922T062944). The blade file was renamed to match in
 * gp247/shop; fresh installs are born with the new key — see GP247Front/AppConfig.php.
 *
 * WHY the key had to change too: the previous modification renamed only what the
 * storefront and the Layout Block list show, on the assumption that the key was
 * internal. It is not — the block picker offers blocks BY FILE NAME
 * (FrontAdmin\LayoutBlockManager::getListViewBlock()), so a site owner creating a
 * home block saw `shop_flash_sale` sitting next to the ProductFlashSale plugin's
 * `product_flash_sale` with nothing to tell them apart. This strip lists plain
 * price promotions; the plugin is the time-boxed mechanism.
 *
 * Two things this migration takes care of beyond the DB row:
 *   - A site that published its own copy of the blade under app/ would silently
 *     lose that override, because the row now resolves to a file name that copy
 *     does not have. Where the old file is present and the new one is not, it is
 *     renamed alongside the row. Best-effort: a read-only app/ directory leaves the
 *     override behind rather than failing the upgrade — the storefront then falls
 *     back to the package's copy, which renders the same strip.
 *   - The window between `composer update` and `gp247:update`: the row still points
 *     at a file name the packages no longer carry, so the block does not render.
 *     gp247_render_block() checks view()->exists, so it stays quiet rather than
 *     throwing, and this migration closes the window.
 *
 * Idempotent: a second run finds no rows on the old key and no old file to move.
 * No cron/queue (NFR-AVAIL-001). Runs via gp247:front-update (--path upgrade/),
 * never the create-tables migration.
 *
 * @aidlc-unit frontend-template-dev
 * @aidlc-story US-TPL-009
 */
return new class extends Migration
{
    /** The block key before and after this upgrade. */
    private const OLD_KEY = 'shop_flash_sale';
    private const NEW_KEY = 'shop_product_promotion';

    /**
     * Point the block rows at the new key and carry any published override with them.
     *
     * @return void
     */
    public function up()
    {
        $this->movePublishedOverrides(self::OLD_KEY, self::NEW_KEY);

        DB::connection(GP247_DB_CONNECTION)
            ->table(GP247_DB_PREFIX.'front_layout_block')
            ->where('text', self::OLD_KEY)
            ->update(['text' => self::NEW_KEY]);
    }

    /**
     * Reverse both halves, for a site rolling back to the previous package version.
     *
     * @return void
     */
    public function down()
    {
        $this->movePublishedOverrides(self::NEW_KEY, self::OLD_KEY);

        DB::connection(GP247_DB_CONNECTION)
            ->table(GP247_DB_PREFIX.'front_layout_block')
            ->where('text', self::NEW_KEY)
            ->update(['text' => self::OLD_KEY]);
    }

    /**
     * Rename a published block override in every template directory that holds one.
     *
     * WHY glob app/ here although listing template files must go through the hint
     * paths (gp247_template_files): this is not listing what a template can render —
     * it is finding the site's own published copies, which by definition live under
     * app/ and nowhere else. The package copies are renamed by shipping the new file.
     *
     * @param string $from Block key whose file is being moved.
     * @param string $to   Block key the file should carry afterwards.
     * @return void
     */
    private function movePublishedOverrides(string $from, string $to): void
    {
        $templates = glob(app_path('GP247/Templates/*/blocks'), GLOB_ONLYDIR) ?: [];

        foreach ($templates as $dir) {
            $source = $dir.DIRECTORY_SEPARATOR.$from.'.blade.php';
            $target = $dir.DIRECTORY_SEPARATOR.$to.'.blade.php';

            if (!is_file($source) || file_exists($target)) {
                continue;
            }

            try {
                File::move($source, $target);
            } catch (\Throwable $e) {
                // Best-effort — see the class doc. The package copy still renders.
            }
        }
    }
};
