<?php

namespace GP247\Front\Tracking\Domain;

/**
 * Tracking settings of one store: the GA4 / GTM / Meta Pixel ids, the cookie group of
 * GTM, the banner mode, the "turn every tracking code off" switch and whether the
 * store runs its own custom code blocks. An id is either well-formed or empty — a
 * malformed stored value (edited outside the screen) is read as empty and never printed.
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-settings-screen, US-tracking-consent-preset-services
 * @aidlc-adr tracking-consent_config-storage-and-authority
 */
final class TrackingSettings
{
    public const MODE_OPTIN = 'optin';
    public const MODE_NOTICE = 'notice';
    public const MODE_OFF = 'off';

    public const MODES = [self::MODE_OPTIN, self::MODE_NOTICE, self::MODE_OFF];

    /** Field => id pattern (logical design §9). */
    public const ID_PATTERNS = [
        'ga4' => '/^G-[A-Z0-9]{4,20}$/',
        'gtm' => '/^GTM-[A-Z0-9]{4,20}$/',
        'pixel' => '/^\d{5,20}$/',
    ];

    /**
     * The sample text shown in the id fields (G-XXXXXXXXXX, GTM-XXXXXXX). It matches the
     * id format, so without this check an owner who typed it over would load a fake id.
     */
    private const PLACEHOLDER_PATTERN = '/^(G|GTM)-X+$/';

    /** Characters of the banner text of one language (plain text, printed escaped). */
    public const MAX_BANNER_TEXT = 500;

    /** Alias of a CMS page (no slash, no space — it is put into a route). */
    private const PAGE_ALIAS_PATTERN = '/^[\pL\pN_.\-]{1,120}$/u';

    /** Field => admin_config key. */
    public const ID_KEYS = [
        'ga4' => 'tracking_ga4_id',
        'gtm' => 'tracking_gtm_id',
        'pixel' => 'tracking_pixel_id',
    ];

    /**
     * @param bool            $enabled       The "tracking code on" switch (false = print nothing).
     * @param string          $ga4Id         GA4 measurement id or ''.
     * @param string          $gtmId         GTM container id or ''.
     * @param ConsentCategory $gtmCategory   Cookie group GTM waits for.
     * @param string          $pixelId       Meta Pixel id or ''.
     * @param string          $bannerMode    One of MODES.
     * @param int             $policyVersion Version of the cookie policy (asked again when raised).
     * @param bool            $snippetOwn    A sub-store runs its own custom code blocks.
     * @param string          $policyPage    Alias of the CMS page linked from the banner, or ''.
     * @param array<string, string> $bannerText Language code => owner's banner text (non-empty only).
     * @param string          $consentVersion Version a visitor's choice must carry ('' = $policyVersion).
     */
    public function __construct(
        public readonly bool $enabled,
        public readonly string $ga4Id,
        public readonly string $gtmId,
        public readonly ConsentCategory $gtmCategory,
        public readonly string $pixelId,
        public readonly string $bannerMode,
        public readonly int $policyVersion,
        public readonly bool $snippetOwn,
        public readonly string $policyPage = '',
        public readonly array $bannerText = [],
        string $consentVersion = '',
    ) {
        $this->consentVersion = $consentVersion !== '' ? $consentVersion : (string) $policyVersion;
    }

    /** Version a visitor's stored choice must carry, e.g. "2" (ROOT) or "2.1" (a sub-store that asked again). */
    public readonly string $consentVersion;

    /**
     * Values of a fresh install and of a site that just updated (Q21): nothing set,
     * switch on, banner off.
     *
     * @return self
     */
    public static function defaults(): self
    {
        return new self(true, '', '', ConsentCategory::MARKETING, '', self::MODE_OFF, 1, false);
    }

    /**
     * Build from stored admin_config values (key => value); anything that breaks a
     * rule falls back to its default.
     *
     * @param array<string, mixed> $values
     * @return self
     */
    public static function fromStored(array $values): self
    {
        $id = static function (string $field) use ($values): string {
            $value = self::normalizeId($field, (string) ($values[self::ID_KEYS[$field]] ?? ''));

            return preg_match(self::ID_PATTERNS[$field], $value) === 1 && preg_match(self::PLACEHOLDER_PATTERN, $value) !== 1 ? $value : '';
        };
        $mode = (string) ($values['tracking_banner_mode'] ?? self::MODE_OFF);

        return new self(
            (bool) (int) ($values['tracking_enabled'] ?? 1),
            $id('ga4'),
            $id('gtm'),
            ConsentCategory::assignableFrom((string) ($values['tracking_gtm_category'] ?? '')) ?? ConsentCategory::MARKETING,
            $id('pixel'),
            in_array($mode, self::MODES, true) ? $mode : self::MODE_OFF,
            max(1, (int) ($values['tracking_policy_version'] ?? 1)),
            (bool) (int) ($values['tracking_snippet_own'] ?? 0),
            self::validAlias((string) ($values['tracking_policy_page'] ?? '')) ? (string) $values['tracking_policy_page'] : '',
            self::decodeBannerText((string) ($values['tracking_banner_text'] ?? '')),
        );
    }

    /**
     * The same settings with the "tracking code on" switch off (ROOT switched off — D4).
     *
     * @return self
     */
    public function switchedOff(): self
    {
        return new self(false, $this->ga4Id, $this->gtmId, $this->gtmCategory, $this->pixelId, $this->bannerMode, $this->policyVersion, $this->snippetOwn, $this->policyPage, $this->bannerText, $this->consentVersion);
    }

    /**
     * @param string $version Consent version worked out for the store (ROOT + own "ask again").
     * @return self The same settings checked against that version.
     */
    public function withConsentVersion(string $version): self
    {
        return new self($this->enabled, $this->ga4Id, $this->gtmId, $this->gtmCategory, $this->pixelId, $this->bannerMode, $this->policyVersion, $this->snippetOwn, $this->policyPage, $this->bannerText, $version);
    }

    /**
     * @param string $lang Language code.
     * @return string The owner's banner text for that language, '' when none (default text applies).
     */
    public function bannerTextFor(string $lang): string
    {
        return $this->bannerText[$lang] ?? '';
    }

    /**
     * Check the banner settings typed on the screen.
     *
     * @param array{mode?: mixed, page?: mixed, text?: mixed} $input
     * @param array<int, string> $pageAliases Aliases of the store's CMS pages that may be linked.
     * @return array<string, string> field (mode|page|text.<lang>) => language code of the message.
     */
    public static function validateBanner(array $input, array $pageAliases): array
    {
        $errors = [];
        if (!in_array((string) ($input['mode'] ?? ''), self::MODES, true)) {
            $errors['mode'] = 'admin.tracking.error_banner_mode';
        }
        $page = (string) ($input['page'] ?? '');
        if ($page !== '' && !in_array($page, $pageAliases, true)) {
            $errors['page'] = 'admin.tracking.error_policy_page';
        }
        foreach ((array) ($input['text'] ?? []) as $lang => $text) {
            if (is_scalar($text) && mb_strlen(trim((string) $text)) > self::MAX_BANNER_TEXT) {
                $errors['text.' . $lang] = 'admin.tracking.error_banner_text_long';
            }
        }

        return $errors;
    }

    /**
     * Stored form of the banner texts: trimmed, empty ones left out.
     *
     * @param array<string, mixed> $texts Language code => typed text.
     * @return string JSON object ('{}' when no text).
     */
    public static function encodeBannerText(array $texts): string
    {
        $clean = [];
        foreach ($texts as $lang => $text) {
            $text = is_scalar($text) ? trim((string) $text) : '';
            if ($text !== '' && preg_match('/^[a-zA-Z_-]{2,10}$/', (string) $lang) === 1) {
                $clean[(string) $lang] = mb_substr($text, 0, self::MAX_BANNER_TEXT);
            }
        }

        return $clean === [] ? '{}' : (string) json_encode($clean, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param string $json Stored banner texts.
     * @return array<string, string> Language code => text; anything malformed is dropped.
     */
    private static function decodeBannerText(string $json): array
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        $texts = [];
        foreach ($decoded as $lang => $text) {
            if (is_string($text) && trim($text) !== '') {
                $texts[(string) $lang] = trim($text);
            }
        }

        return $texts;
    }

    /**
     * @param string $alias
     * @return bool Whether it looks like a CMS page alias.
     */
    private static function validAlias(string $alias): bool
    {
        return preg_match(self::PAGE_ALIAS_PATTERN, $alias) === 1;
    }

    /**
     * Trim an id and upper-case the Google ones, the way the site owner most likely meant it.
     *
     * @param string $field ga4|gtm|pixel
     * @param string $raw   Typed value.
     * @return string
     */
    public static function normalizeId(string $field, string $raw): string
    {
        $value = trim($raw);

        return $field === 'pixel' ? $value : strtoupper($value);
    }

    /**
     * Check typed ids (already normalised or not) and the GTM group.
     *
     * @param array<string, string> $input Any of ga4, gtm, pixel, gtm_category.
     * @return array<string, string> field => language code of the message; empty when valid.
     */
    public static function validateIds(array $input): array
    {
        $errors = [];
        foreach (self::ID_PATTERNS as $field => $pattern) {
            if (!array_key_exists($field, $input)) {
                continue;
            }
            $value = self::normalizeId($field, (string) $input[$field]);
            if ($value !== '' && preg_match($pattern, $value) !== 1) {
                $errors[$field] = 'admin.tracking.error_' . $field;
            } elseif (preg_match(self::PLACEHOLDER_PATTERN, $value) === 1) {
                $errors[$field] = 'admin.tracking.error_placeholder';
            }
        }
        if (array_key_exists('gtm_category', $input) && ConsentCategory::assignableFrom((string) $input['gtm_category']) === null) {
            $errors['gtm_category'] = 'admin.tracking.error_category';
        }

        return $errors;
    }

    /**
     * @return bool Whether at least one preset service is set.
     */
    public function hasAnyService(): bool
    {
        return $this->ga4Id !== '' || $this->gtmId !== '' || $this->pixelId !== '';
    }
}
