<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent upgrade: seed the two labels under the layout-block content field
 * — the HTML help line and the "no view block available" notice — which the
 * screen rendered as raw language codes because they were never seeded.
 * Fresh installs get them from DataFrontLanguageSeeder; insertOrIgnore keeps any
 * text a site owner already edited.
 *
 * Runs via gp247:front-update (--path upgrade/), never the create-tables migration.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-004
 */
return new class extends Migration
{
    /**
     * Label rows shipped by this migration (same text as DataFrontLanguageSeeder
     * and its Locales/ja.php).
     *
     * @return array<int, array<string, string>>
     */
    public static function rows(): array
    {
        $p = 'admin.layout_block';

        return [
            ['code' => 'admin.layout_block.text_help', 'text' => 'HTML content shown at the selected position.', 'position' => $p, 'location' => 'en'],
            ['code' => 'admin.layout_block.text_help', 'text' => 'Nội dung HTML hiển thị tại vị trí đã chọn.', 'position' => $p, 'location' => 'vi'],
            ['code' => 'admin.layout_block.text_help', 'text' => '選択した位置に表示するHTMLコンテンツです。', 'position' => $p, 'location' => 'ja'],
            ['code' => 'admin.layout_block.view_empty', 'text' => 'The active template and plugins provide no view blocks yet.', 'position' => $p, 'location' => 'en'],
            ['code' => 'admin.layout_block.view_empty', 'text' => 'Template đang dùng và các plugin chưa cung cấp khối giao diện nào.', 'position' => $p, 'location' => 'vi'],
            ['code' => 'admin.layout_block.view_empty', 'text' => '使用中のテンプレートとプラグインには、まだビューブロックがありません。', 'position' => $p, 'location' => 'ja'],
        ];
    }

    /**
     * @return void
     */
    public function up()
    {
        DB::connection(GP247_DB_CONNECTION)
            ->table(GP247_DB_PREFIX.'languages')
            ->insertOrIgnore(self::rows());
    }

    /**
     * WARNING: removes these label rows, including any text a site owner edited.
     *
     * @return void
     */
    public function down()
    {
        DB::connection(GP247_DB_CONNECTION)
            ->table(GP247_DB_PREFIX.'languages')
            ->whereIn('code', array_values(array_unique(array_column(self::rows(), 'code'))))
            ->delete();
    }
};
