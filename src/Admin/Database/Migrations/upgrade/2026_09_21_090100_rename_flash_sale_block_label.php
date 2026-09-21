<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent upgrade: rename the admin-facing name of the home promotion strip from
 * "Flash sale (<Template>)" to "Promotion products (<Template>)" on installed sites
 * (modification 20260921T231520). Fresh installs are born with the new name — see
 * GP247Front/AppConfig.php.
 *
 * WHY: the block renders ShopProduct::getProductPromotion() — products carrying an
 * active price promotion, whose date_end is routinely weeks out. It is not the
 * time-boxed ProductFlashSale plugin (own stock/sold ledger), so the old name sent a
 * site owner looking for flash-sale settings that do not exist here. The storefront
 * heading and its dropped countdown are handled in gp247/shop, which owns the blade.
 *
 * Only `name` (the label in the admin Layout Block screen) is touched here; the block
 * key `text` was still 'shop_flash_sale' at this point. The key is renamed to
 * 'shop_product_promotion' by the later migration 2026_09_22_060000, which moves the
 * blade file with it — this one must keep matching on the OLD key so the two run in
 * order on a site upgrading across both.
 *
 * Idempotent: a second run finds no "Flash sale (" prefix left to replace. The
 * template name inside the parentheses is preserved, so a site whose template is not
 * GP247Front keeps its own suffix. No cron/queue (NFR-AVAIL-001). Runs via
 * gp247:front-update (--path upgrade/), never the create-tables migration.
 *
 * @aidlc-unit frontend-template-dev
 * @aidlc-story US-TPL-009
 */
return new class extends Migration
{
    /**
     * Replace the "Flash sale (" prefix with "Promotion products (" on the block rows.
     *
     * @return void
     */
    public function up()
    {
        DB::connection(GP247_DB_CONNECTION)
            ->table(GP247_DB_PREFIX.'front_layout_block')
            ->where('text', 'shop_flash_sale')
            ->where('name', 'like', 'Flash sale (%')
            ->update([
                'name' => DB::raw("REPLACE(name, 'Flash sale (', 'Promotion products (')"),
            ]);
    }

    /**
     * Restore the previous name.
     *
     * @return void
     */
    public function down()
    {
        DB::connection(GP247_DB_CONNECTION)
            ->table(GP247_DB_PREFIX.'front_layout_block')
            ->where('text', 'shop_flash_sale')
            ->where('name', 'like', 'Promotion products (%')
            ->update([
                'name' => DB::raw("REPLACE(name, 'Promotion products (', 'Flash sale (')"),
            ]);
    }
};
