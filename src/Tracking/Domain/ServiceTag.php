<?php

namespace GP247\Front\Tracking\Domain;

/**
 * Standard code of one preset service, generated from its id.
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-preset-services
 * @aidlc-adr tracking-consent_deferred-script-activation
 */
final class ServiceTag
{
    /**
     * @param string          $service  ga4|gtm|pixel
     * @param ConsentCategory $category Cookie group it waits for.
     * @param string          $html     Generated code.
     */
    public function __construct(
        public readonly string $service,
        public readonly ConsentCategory $category,
        public readonly string $html,
    ) {
    }
}
