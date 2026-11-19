<?php

namespace GP247\Front\Tracking\Application;

use GP247\Core\Models\AdminConfig;
use GP247\Front\Tracking\Domain\InvalidTrackingSetting;
use GP247\Front\Tracking\Domain\TrackingSettings;

/**
 * Reads and writes the short tracking settings (admin_config group "tracking") the way
 * StoreConfigForm does: the ROOT store's rows are the base, a sub-store writes override
 * rows with the same group and key, and removing an override falls back to ROOT
 * (ADR tracking-consent_config-storage-and-authority, decision D2 of the TC-1 plan).
 *
 * WHY not gp247_config(): it falls back to the GLOBAL tier, which this screen never
 * writes, so a sub-store would ignore the root owner's choice (same as ContactSettings).
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-settings-screen, US-tracking-consent-preset-services, US-tracking-consent-custom-code
 * @aidlc-adr tracking-consent_config-storage-and-authority
 */
final class TrackingSettingsService
{
    public const GROUP = 'tracking';

    public const CODE = 'tracking_config';

    /** Key => default value of a fresh install and of an updated site (Q21). */
    public const DEFAULTS = [
        'tracking_enabled' => 1,
        'tracking_ga4_id' => '',
        'tracking_gtm_id' => '',
        'tracking_gtm_category' => 'marketing',
        'tracking_pixel_id' => '',
        'tracking_banner_mode' => TrackingSettings::MODE_OFF,
        'tracking_policy_version' => 1,
        'tracking_snippet_own' => 0,
        'tracking_policy_page' => '',
        'tracking_banner_text' => '{}',
    ];

    /** Keys of the "Save" form on the screen (the switches are saved on their own). */
    public const FORM_KEYS = ['tracking_ga4_id', 'tracking_gtm_id', 'tracking_gtm_category', 'tracking_pixel_id'];

    /** Keys of the "Cookie banner" form on the screen. */
    public const BANNER_KEYS = ['tracking_banner_mode', 'tracking_policy_page', 'tracking_banner_text'];

    /**
     * Keys a sub-store never takes from ROOT.
     * WHY: a CMS page belongs to one store (1-1 ownership); ROOT's page does not exist on a
     * sub-store's domain, so an inherited link would lead its visitors to a 404.
     */
    public const OWN_ONLY_KEYS = ['tracking_policy_page'];

    /**
     * Effective settings of a store: its own rows, then ROOT's, then the defaults. The
     * switch is on only when both ROOT's and the store's are on (decision D4), so
     * turning it off on ROOT stops every store at once.
     *
     * @param int|string|null $storeId Null = the store of the current request.
     * @return TrackingSettings
     */
    public static function forStore($storeId = null): TrackingSettings
    {
        $storeId = (string) ($storeId ?? config('app.storeId'));
        $own = self::pick(AdminConfig::getAllConfigOfStore($storeId));
        if ($storeId === self::rootId()) {
            return TrackingSettings::fromStored($own + self::DEFAULTS);
        }
        $root = self::pick(AdminConfig::getAllConfigOfStore(self::rootId()));
        $merged = $own + $root + self::DEFAULTS;
        foreach (self::OWN_ONLY_KEYS as $key) {
            $merged[$key] = $own[$key] ?? self::DEFAULTS[$key];
        }
        $settings = TrackingSettings::fromStored($merged)->withConsentVersion(self::consentVersionOf($own, $root));
        $rootOn = (bool) (int) ($root['tracking_enabled'] ?? 1);

        return $rootOn ? $settings : $settings->switchedOff();
    }

    /**
     * Version a visitor's choice is checked against: ROOT's version, plus the sub-store's
     * own "ask again" count once it has one ("3.2"). WHY combined: "ask again" at ROOT must
     * still reach a store that asked again on its own before.
     *
     * @param array<string, mixed> $own  The store's own rows.
     * @param array<string, mixed> $root ROOT's rows.
     * @return string
     */
    private static function consentVersionOf(array $own, array $root): string
    {
        $rootVersion = max(1, (int) ($root['tracking_policy_version'] ?? 1));
        $ownVersion = (int) ($own['tracking_policy_version'] ?? 0);

        return $ownVersion > 0 ? $rootVersion . '.' . $ownVersion : (string) $rootVersion;
    }

    /**
     * Values to show on the screen for a store and whether each one is inherited from ROOT.
     *
     * @param int|string $storeId
     * @return array{values: array<string, string>, inherited: array<string, bool>}
     */
    public static function screenValues($storeId): array
    {
        $storeId = (string) $storeId;
        $own = self::pick(AdminConfig::getAllConfigOfStore($storeId));
        $isRoot = $storeId === self::rootId();
        $root = $isRoot ? [] : self::pick(AdminConfig::getAllConfigOfStore(self::rootId()));
        $values = [];
        $inherited = [];
        foreach (self::DEFAULTS as $key => $default) {
            $ownOnly = in_array($key, self::OWN_ONLY_KEYS, true);
            $inherited[$key] = !$isRoot && !$ownOnly && !array_key_exists($key, $own);
            $values[$key] = (string) ($own[$key] ?? ($ownOnly ? null : $root[$key] ?? null) ?? $default);
        }

        return ['values' => $values, 'inherited' => $inherited];
    }

    /**
     * Check and store the ids and the GTM group for a store. At ROOT every key is
     * written; at a sub-store only the keys in $changed, as overrides.
     *
     * @param int|string           $storeId
     * @param array<string, mixed> $input   FORM_KEYS => typed value.
     * @param array<int, string>   $changed Keys the admin changed (sub-store only).
     * @return array<string, string> The stored values.
     * @throws InvalidTrackingSetting When an id or the group is malformed.
     */
    public static function saveForm($storeId, array $input, array $changed): array
    {
        $errors = TrackingSettings::validateIds([
            'ga4' => (string) ($input['tracking_ga4_id'] ?? ''),
            'gtm' => (string) ($input['tracking_gtm_id'] ?? ''),
            'pixel' => (string) ($input['tracking_pixel_id'] ?? ''),
            'gtm_category' => (string) ($input['tracking_gtm_category'] ?? ''),
        ]);
        if ($errors !== []) {
            throw new InvalidTrackingSetting($errors);
        }
        $clean = [
            'tracking_ga4_id' => TrackingSettings::normalizeId('ga4', (string) ($input['tracking_ga4_id'] ?? '')),
            'tracking_gtm_id' => TrackingSettings::normalizeId('gtm', (string) ($input['tracking_gtm_id'] ?? '')),
            'tracking_gtm_category' => (string) $input['tracking_gtm_category'],
            'tracking_pixel_id' => TrackingSettings::normalizeId('pixel', (string) ($input['tracking_pixel_id'] ?? '')),
        ];
        $isRoot = (string) $storeId === self::rootId();
        foreach ($clean as $key => $value) {
            if ($isRoot || in_array($key, $changed, true)) {
                self::write($storeId, $key, $value);
            }
        }
        AdminConfig::forgetMemo();

        return $clean;
    }

    /**
     * Check and store the banner mode, the policy page and the banner texts of a store.
     * At ROOT every key is written; at a sub-store only the keys in $changed.
     *
     * @param int|string           $storeId
     * @param array<string, mixed> $input       mode, page, text (language code => text).
     * @param array<int, string>   $changed     BANNER_KEYS the admin changed (sub-store only).
     * @param array<int, string>   $pageAliases Pages of the store that may be linked.
     * @return void
     * @throws InvalidTrackingSetting When a value breaks a rule.
     */
    public static function saveBanner($storeId, array $input, array $changed, array $pageAliases): void
    {
        $errors = TrackingSettings::validateBanner($input, $pageAliases);
        if ($errors !== []) {
            throw new InvalidTrackingSetting($errors);
        }
        $clean = [
            'tracking_banner_mode' => (string) $input['mode'],
            'tracking_policy_page' => (string) ($input['page'] ?? ''),
            'tracking_banner_text' => TrackingSettings::encodeBannerText((array) ($input['text'] ?? [])),
        ];
        $isRoot = (string) $storeId === self::rootId();
        foreach ($clean as $key => $value) {
            if ($isRoot || in_array($key, $changed, true)) {
                self::write($storeId, $key, $value);
            }
        }
        AdminConfig::forgetMemo();
    }

    /**
     * Raise the cookie policy version of a store so every visitor is asked again (Q11).
     *
     * At a sub-store this raises only its own count, which is added to ROOT's version
     * (consentVersionOf), so a later "ask again" at ROOT still reaches it.
     *
     * @param int|string $storeId
     * @return string The new consent version of the store.
     */
    public static function askAgain($storeId): string
    {
        $own = self::pick(AdminConfig::getAllConfigOfStore((string) $storeId));
        $isRoot = (string) $storeId === self::rootId();
        $current = (int) ($own['tracking_policy_version'] ?? ($isRoot ? 1 : 0));
        self::write($storeId, 'tracking_policy_version', (string) ($current + 1));
        AdminConfig::forgetMemo();

        return self::forStore($storeId)->consentVersion;
    }

    /**
     * Store one on/off setting (the tracking switch or "own code blocks") at once.
     *
     * @param int|string $storeId
     * @param string     $key   tracking_enabled|tracking_snippet_own
     * @param bool       $on
     * @return void
     * @throws \InvalidArgumentException For any other key.
     */
    public static function saveSwitch($storeId, string $key, bool $on): void
    {
        if (!in_array($key, ['tracking_enabled', 'tracking_snippet_own'], true)) {
            throw new \InvalidArgumentException("Not a tracking switch [{$key}]");
        }
        self::write($storeId, $key, $on ? '1' : '0');
        AdminConfig::forgetMemo();
    }

    /**
     * Drop a sub-store's override so the key follows ROOT again. No-op at ROOT.
     *
     * @param int|string $storeId
     * @param string     $key
     * @return void
     */
    public static function resetToRoot($storeId, string $key): void
    {
        if ((string) $storeId === self::rootId() || !array_key_exists($key, self::DEFAULTS)) {
            return;
        }
        AdminConfig::where('group', self::GROUP)->where('key', $key)->where('store_id', (string) $storeId)->delete();
        AdminConfig::forgetMemo();
    }

    /**
     * Whether gp247:update / install has seeded the tracking rows (the tables come with them).
     * Reads the ROOT config map, already loaded for the request — no extra query.
     *
     * @return bool
     */
    public static function installed(): bool
    {
        return array_key_exists('tracking_enabled', AdminConfig::getAllConfigOfStore(self::rootId()));
    }

    /**
     * @return string Id of the ROOT store.
     */
    public static function rootId(): string
    {
        return (string) (defined('GP247_STORE_ID_ROOT') ? GP247_STORE_ID_ROOT : 1);
    }

    /**
     * Upsert one row of the group for a store, copying sort/detail of the ROOT row.
     *
     * @param int|string $storeId
     * @param string     $key
     * @param string     $value
     * @return void
     */
    private static function write($storeId, string $key, string $value): void
    {
        $sort = array_search($key, array_keys(self::DEFAULTS), true);
        AdminConfig::updateOrCreate(
            ['key' => $key, 'store_id' => (string) $storeId],
            [
                'group' => self::GROUP,
                'code' => self::CODE,
                'value' => $value,
                'sort' => $sort === false ? 0 : $sort,
                'detail' => 'admin.tracking.config.' . $key,
            ],
        );
    }

    /**
     * Only this group's keys out of a store's whole config map.
     *
     * @param array<string, mixed> $all
     * @return array<string, mixed>
     */
    private static function pick(array $all): array
    {
        return array_intersect_key($all, self::DEFAULTS);
    }
}
