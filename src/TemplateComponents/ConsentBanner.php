<?php

namespace GP247\Front\TemplateComponents;

/**
 * Cookie consent banner and its "customise" panel. The server prints it inside an inert
 * <template> in <head> (TrackingHead); the consent loader copies it into the page only
 * when the visitor still has to choose or opens "Cookie settings". A template restyles it
 * at `app/GP247/Templates/<Template>/gp247_components/consent-banner.blade.php`, keeping the
 * `data-gp247-consent-*` hooks the loader looks for and the three equal buttons (Q12).
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-banner, US-tracking-consent-template-hook
 * @aidlc-adr tracking-consent_deferred-script-activation, tracking-consent_head-injection-via-meta-head
 */
class ConsentBanner extends BaseFrontViewComponent
{
    /**
     * @param string $message   Banner text, plain (printed escaped).
     * @param string $policyUrl Link to the cookie policy page, '' for none.
     */
    public function __construct(
        public string $message = '',
        public string $policyUrl = '',
    ) {
    }

    /**
     * @return string
     */
    protected function templateViewKey(): string
    {
        return 'gp247_components.consent-banner';
    }

    /**
     * @return string
     */
    protected function defaultViewNamespace(): string
    {
        return 'gp247-front';
    }
}
