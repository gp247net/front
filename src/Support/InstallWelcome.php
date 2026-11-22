<?php

namespace GP247\Front\Support;

/**
 * Default home welcome and first menu link of a fresh install, named after the product
 * declared under `gp247-config.install` (read through core's InstallDefaults).
 *
 * Front owns the wording in its default languages; core only supplies the product name and
 * web address. Nothing declared — or a core released before the setting existed — keeps
 * today's wording and the GP247 link.
 *
 * @aidlc-unit installer-deploy
 * @aidlc-story US-DEP-install-default-product-name
 * @aidlc-adr installer-deploy_install-default-product-name
 */
final class InstallWelcome
{
    /** Language key the GP247Front "welcome" block prints. */
    public const LANG_KEY = 'front.welcome_message';

    /** Welcome used when no product is declared (en, vi, ja). */
    private const PLATFORM_WELCOME = [
        'en' => 'Welcome to CMS created by GP247 system',
        'vi' => 'Chào mừng đến với CMS được tạo bởi hệ thống GP247',
        'ja' => 'GP247 システムで作成された CMS へようこそ',
    ];

    /** First menu link used when no product name and address are declared. */
    private const PLATFORM_LINK = ['name' => 'GP247', 'url' => 'https://gp247.net'];

    /**
     * Welcome text per default language.
     *
     * @return array{en: string, vi: string, ja: string}
     */
    public static function texts(): array
    {
        $name = self::productName();
        if ($name === null) {
            return self::PLATFORM_WELCOME;
        }

        return [
            'en' => 'Welcome to ' . $name,
            'vi' => 'Chào mừng bạn đến với ' . $name,
            'ja' => $name . ' へようこそ',
        ];
    }

    /**
     * Language rows of the welcome key, for the front language seeder (insertOrIgnore).
     *
     * @return array<int, array{code: string, text: string, position: string, location: string}>
     */
    public static function languageRows(): array
    {
        $rows = [];
        foreach (self::texts() as $lang => $text) {
            $rows[] = ['code' => self::LANG_KEY, 'text' => $text, 'position' => 'front', 'location' => $lang];
        }

        return $rows;
    }

    /**
     * Name and address of the first main-menu link: the product's when both are declared, else GP247's.
     *
     * @return array{name: string, url: string}
     */
    public static function menuLink(): array
    {
        $name = self::productName();
        $url = self::productUrl();

        return ($name !== null && $url !== null) ? ['name' => $name, 'url' => $url] : self::PLATFORM_LINK;
    }

    /**
     * Declared product name, or null when none is declared or core predates the setting.
     *
     * @return string|null
     */
    private static function productName(): ?string
    {
        $defaults = \GP247\Core\Support\InstallDefaults::class;

        return class_exists($defaults) ? $defaults::productName() : null;
    }

    /**
     * Declared product web address, or null.
     *
     * @return string|null
     */
    private static function productUrl(): ?string
    {
        $defaults = \GP247\Core\Support\InstallDefaults::class;

        return class_exists($defaults) ? $defaults::productUrl() : null;
    }
}
