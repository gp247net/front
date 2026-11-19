<?php

namespace GP247\Front\Tracking\Domain;

/**
 * Generates the standard GA4 (gtag.js), GTM and Meta Pixel code from fixed templates.
 * Ids arrive already checked against their pattern (TrackingSettings) and are written
 * into JavaScript through json_encode, so these fields are never a way to inject code.
 * No <noscript> fallbacks: they cannot wait for consent (ADR deferred-script §6).
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-preset-services
 * @aidlc-adr tracking-consent_deferred-script-activation
 */
final class ServiceTagFactory
{
    private const JS_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR;

    /**
     * Code of every service with an id, in the order GA4, GTM, Pixel.
     *
     * @param TrackingSettings $settings
     * @return array<int, ServiceTag>
     */
    public static function forSettings(TrackingSettings $settings): array
    {
        $tags = [];
        if ($settings->ga4Id !== '') {
            $tags[] = new ServiceTag('ga4', ConsentCategory::ANALYTICS, self::ga4($settings->ga4Id));
        }
        if ($settings->gtmId !== '') {
            $tags[] = new ServiceTag('gtm', $settings->gtmCategory, self::gtm($settings->gtmId));
        }
        if ($settings->pixelId !== '') {
            $tags[] = new ServiceTag('pixel', ConsentCategory::MARKETING, self::pixel($settings->pixelId));
        }

        return $tags;
    }

    /**
     * @param string $id GA4 measurement id.
     * @return string
     */
    private static function ga4(string $id): string
    {
        $js = json_encode($id, self::JS_FLAGS);

        return '<script async src="https://www.googletagmanager.com/gtag/js?id=' . rawurlencode($id) . '"></script>'
            . '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}'
            . "gtag('js',new Date());gtag('config'," . $js . ');</script>';
    }

    /**
     * @param string $id GTM container id.
     * @return string
     */
    private static function gtm(string $id): string
    {
        return "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});"
            . "var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;"
            . "j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})"
            . "(window,document,'script','dataLayer'," . json_encode($id, self::JS_FLAGS) . ');</script>';
    }

    /**
     * @param string $id Meta Pixel id.
     * @return string
     */
    private static function pixel(string $id): string
    {
        return '<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?'
            . 'n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;'
            . "n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];"
            . "s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');"
            . "fbq('init'," . json_encode($id, self::JS_FLAGS) . ");fbq('track','PageView');</script>";
    }
}
