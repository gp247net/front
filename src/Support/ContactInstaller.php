<?php

namespace GP247\Front\Support;

use GP247\Front\Admin\Database\Seeders\DataFrontLanguageSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One source for everything the contact form needs in the database: the
 * front_contact table, its settings, the admin menu entry, the two permissions and
 * the labels. Used by the create-tables migration (fresh install), the default
 * seeder and the upgrade migration (sites already installed), so all three stay
 * identical. Every step is re-runnable and never deletes or overwrites a value.
 *
 * @aidlc-unit front-contact
 * @aidlc-story US-front-contact-toggle, US-front-contact-config-tab, US-SADM-sendmail-contact-handover
 * @aidlc-adr front-contact_contact-form-in-front
 */
final class ContactInstaller
{
    /** Mail toggles that shop seeded under its Send mail tab before the form existed. */
    public const LEGACY_MAIL_KEYS = ['contact_to_admin', 'contact_to_customer'];

    /** Label code prefixes owned by the contact form (filtered out of DataFrontLanguageSeeder). */
    private const LABEL_PREFIXES = ['admin.contact.', 'front.contact.', 'admin.menu_titles.contact_', 'admin.layout_block_page.contact'];

    /**
     * Create the messages table when missing.
     *
     * @return void
     */
    public static function createTable(): void
    {
        $schema = Schema::connection(GP247_DB_CONNECTION);
        if ($schema->hasTable(GP247_DB_PREFIX . 'front_contact')) {
            return;
        }

        $schema->create(GP247_DB_PREFIX . 'front_contact', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('store_id');
            $table->string('name', 100);
            $table->string('email', 150);
            $table->string('phone', 30)->nullable();
            $table->string('title', 200);
            $table->text('content');
            $table->tinyInteger('status')->default(0)->comment('0 new, 1 read, 2 handled');
            $table->timestamps();
            $table->index(['store_id', 'status']);
        });
    }

    /**
     * Hand the two mail toggles over from shop's Send mail tab: same key and value,
     * new group/code. Only rows still under shop's code are touched.
     *
     * @return int Rows moved.
     */
    public static function moveLegacyMailToggles(): int
    {
        $moved = 0;
        $sort = array_flip(array_keys(ContactSettings::DEFAULTS));
        foreach (self::LEGACY_MAIL_KEYS as $key) {
            // WHY label + sort too: the Contact tab shows `detail` as the label and
            // orders by `sort`; shop's values would show shop's old wording.
            $moved += DB::connection(GP247_DB_CONNECTION)->table(GP247_DB_PREFIX . 'admin_config')
                ->where('key', $key)
                ->where('code', 'sendmail_config')
                ->update([
                    'group' => ContactSettings::GROUP,
                    'code' => ContactSettings::CODE,
                    'detail' => 'admin.contact.config.' . $key,
                    'sort' => $sort[$key],
                ]);
        }

        return $moved;
    }

    /**
     * Default settings on the ROOT store (the tier the Contact tab edits). Existing
     * rows — including moved legacy toggles — keep their value (unique key, store_id).
     *
     * @return void
     */
    public static function seedConfig(): void
    {
        $root = (string) (defined('GP247_STORE_ID_ROOT') ? GP247_STORE_ID_ROOT : 1);
        $rows = [];
        $sort = 0;
        foreach (ContactSettings::DEFAULTS as $key => $value) {
            $rows[] = [
                'group' => ContactSettings::GROUP,
                'code' => ContactSettings::CODE,
                'key' => $key,
                'value' => (string) $value,
                'sort' => $sort++,
                'detail' => 'admin.contact.config.' . $key,
                'store_id' => $root,
            ];
        }

        DB::connection(GP247_DB_CONNECTION)->table(GP247_DB_PREFIX . 'admin_config')->insertOrIgnore($rows);
    }

    /**
     * Admin menu entry (under the CMS block) and the two permissions, by natural key.
     * WHY two permissions: reading visitors' messages and changing the form settings
     * are different jobs; the inbox URI pattern must not cover contact_config.
     *
     * @return void
     */
    public static function seedAdmin(): void
    {
        $db = DB::connection(GP247_DB_CONNECTION);
        $prefix = defined('GP247_ADMIN_PREFIX') ? GP247_ADMIN_PREFIX : 'gp247_admin';

        if (!$db->table(GP247_DB_PREFIX . 'admin_menu')->where('uri', 'admin::contact')->exists()) {
            $parent = $db->table(GP247_DB_PREFIX . 'admin_menu')->where('key', 'ADMIN_CONTENT')->value('id');
            $db->table(GP247_DB_PREFIX . 'admin_menu')->insert([
                'parent_id' => $parent ?: 0,
                'sort' => 6,
                'title' => 'admin.menu_titles.contact_messages',
                'icon' => 'fas fa-envelope-open-text',
                'uri' => 'admin::contact',
                'key' => null,
                'type' => 0,
            ]);
        }

        $permissions = [
            ['name' => 'Contact messages', 'slug' => 'front.contact', 'http_uri' => 'ANY::' . $prefix . '/contact,ANY::' . $prefix . '/contact/*'],
            ['name' => 'Contact form settings', 'slug' => 'front.contact_config', 'http_uri' => 'ANY::' . $prefix . '/contact_config,ANY::' . $prefix . '/contact_config/*'],
        ];
        foreach ($permissions as $row) {
            // WHY name too: admin_permission.name is unique as well, and a site may
            // already have a hand-made permission with the same name.
            $exists = $db->table(GP247_DB_PREFIX . 'admin_permission')
                ->where('slug', $row['slug'])
                ->orWhere('name', $row['name'])
                ->exists();
            if (!$exists) {
                $db->table(GP247_DB_PREFIX . 'admin_permission')->insert($row + ['created_at' => date('Y-m-d H:i:s')]);
            }
        }
    }

    /**
     * The contact-form label rows (every default locale), taken from
     * DataFrontLanguageSeeder so the upgrade ships exactly the fresh-install text.
     *
     * @return array<int, array<string, string>>
     */
    public static function labelRows(): array
    {
        return array_values(array_filter(
            (new DataFrontLanguageSeeder())->dataLang(),
            static function (array $row): bool {
                foreach (self::LABEL_PREFIXES as $prefix) {
                    if (str_starts_with($row['code'], $prefix)) {
                        return true;
                    }
                }

                return false;
            }
        ));
    }

    /**
     * Insert the labels, keeping any text a site owner already edited.
     *
     * @return void
     */
    public static function seedLabels(): void
    {
        DB::connection(GP247_DB_CONNECTION)->table(GP247_DB_PREFIX . 'languages')->insertOrIgnore(self::labelRows());
    }
}
