<?php

namespace GP247\Front\Support;

use GP247\Core\Models\AdminConfig;

/**
 * Contact-form settings (admin_config, group front_contact / code contact_config).
 *
 * A store's own row wins; a store without one follows the ROOT store (the tier the
 * Contact tab edits), then the built-in default. WHY not gp247_config(): it falls
 * back to the GLOBAL tier, which this screen never writes, so a sub-store would
 * silently ignore the root owner's choice.
 *
 * @aidlc-unit front-contact
 * @aidlc-story US-front-contact-toggle, US-front-contact-notify-mail
 * @aidlc-adr front-contact_contact-form-in-front
 */
final class ContactSettings
{
    public const GROUP = 'front_contact';

    public const CODE = 'contact_config';

    /** Setting key => default value. */
    public const DEFAULTS = [
        'contact_status' => 0,
        'contact_to_admin' => 1,
        'contact_to_customer' => 0,
    ];

    /**
     * Effective boolean value of a setting for a store.
     *
     * @param string          $key     One of DEFAULTS' keys.
     * @param int|string|null $storeId Store id; null = the store of the current request.
     * @return bool
     * @throws \InvalidArgumentException When the key is not a contact setting.
     */
    public static function get(string $key, $storeId = null): bool
    {
        $storeId = (string) ($storeId ?? config('app.storeId'));
        $root = (string) (defined('GP247_STORE_ID_ROOT') ? GP247_STORE_ID_ROOT : 1);

        return self::resolve(
            $key,
            AdminConfig::getAllConfigOfStore($storeId),
            $storeId === $root ? [] : AdminConfig::getAllConfigOfStore($root),
        );
    }

    /**
     * @param int|string|null $storeId Store id; null = current request store.
     * @return bool Whether the contact form is switched on.
     */
    public static function enabled($storeId = null): bool
    {
        return self::get('contact_status', $storeId);
    }

    /**
     * Pure resolution: store row, then root row, then default.
     *
     * @param string               $key        Setting key.
     * @param array<string, mixed> $storeRows  key => value of the store.
     * @param array<string, mixed> $rootRows   key => value of the root store.
     * @return bool
     * @throws \InvalidArgumentException When the key is not a contact setting.
     */
    public static function resolve(string $key, array $storeRows, array $rootRows): bool
    {
        if (!array_key_exists($key, self::DEFAULTS)) {
            throw new \InvalidArgumentException("Unknown contact setting [{$key}]");
        }

        $value = $storeRows[$key] ?? $rootRows[$key] ?? self::DEFAULTS[$key];

        return (bool) (int) $value;
    }
}
