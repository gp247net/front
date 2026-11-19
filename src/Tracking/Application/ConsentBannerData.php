<?php

namespace GP247\Front\Tracking\Application;

use GP247\Front\TemplateComponents\ConsentBanner;
use GP247\Front\Tracking\Domain\TrackingSettings;
use Illuminate\Support\Facades\Blade;

/**
 * Banner content for the page being shown: the owner's text in the visitor's language
 * (or the default text of the language rows) and the link to the chosen policy page.
 * No query: the page link is built from the stored alias (checked when saved).
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-banner
 * @aidlc-adr tracking-consent_deferred-script-activation
 */
final class ConsentBannerData
{
    /**
     * @param TrackingSettings $settings Effective settings of the store.
     * @param string           $lang     Language of the page.
     * @return string Banner text, plain.
     */
    public static function message(TrackingSettings $settings, string $lang): string
    {
        $own = $settings->bannerTextFor($lang);

        return $own !== '' ? $own : (string) gp247_language_render('front.consent.message');
    }

    /**
     * @param TrackingSettings $settings
     * @param string           $lang
     * @return string Link to the cookie policy page, '' when none is chosen.
     */
    public static function policyUrl(TrackingSettings $settings, string $lang): string
    {
        if ($settings->policyPage === '' || !function_exists('gp247_route_front')) {
            return '';
        }

        return (string) gp247_route_front('front.page.detail', ['alias' => $settings->policyPage, 'lang' => $lang]);
    }

    /**
     * The banner as an inert <template> for the loader; '' when the banner is off.
     *
     * @param TrackingSettings $settings
     * @return string
     */
    public static function template(TrackingSettings $settings): string
    {
        if ($settings->bannerMode === TrackingSettings::MODE_OFF) {
            return '';
        }
        $lang = app()->getLocale();
        $banner = Blade::renderComponent(new ConsentBanner(self::message($settings, $lang), self::policyUrl($settings, $lang)));

        return '<template id="gp247-consent-banner">' . $banner . '</template>';
    }
}
