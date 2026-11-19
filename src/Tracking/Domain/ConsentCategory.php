<?php

namespace GP247\Front\Tracking\Domain;

/**
 * Cookie groups a visitor consents to. "necessary" is always on and never holds
 * tracking code; analytics and marketing code waits for consent.
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-preset-services, US-tracking-consent-custom-code
 * @aidlc-adr tracking-consent_deferred-script-activation
 */
enum ConsentCategory: string
{
    case NECESSARY = 'necessary';
    case ANALYTICS = 'analytics';
    case MARKETING = 'marketing';

    /**
     * Groups a site owner may assign to tracking code (GTM and custom code).
     * WHY no "necessary": code put there would run without asking the visitor.
     *
     * @return array<int, self>
     */
    public static function assignable(): array
    {
        return [self::ANALYTICS, self::MARKETING];
    }

    /**
     * An assignable group from its stored value, or null when it is not one.
     *
     * @param string $value
     * @return self|null
     */
    public static function assignableFrom(string $value): ?self
    {
        $category = self::tryFrom($value);

        return in_array($category, self::assignable(), true) ? $category : null;
    }
}
