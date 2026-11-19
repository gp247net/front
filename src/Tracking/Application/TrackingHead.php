<?php

namespace GP247\Front\Tracking\Application;

use GP247\Front\Tracking\Domain\CodeSnippet;
use GP247\Front\Tracking\Domain\DormantScript;
use GP247\Front\Tracking\Domain\ServiceTag;
use GP247\Front\Tracking\Domain\ServiceTagFactory;
use GP247\Front\Tracking\Domain\SnippetRegion;
use GP247\Front\Tracking\Infrastructure\TrackingAssets;
use Illuminate\Support\Facades\Cache;

/**
 * What the tracking layer adds to the storefront <head>: the loader settings (JSON),
 * the loader itself and every tracking code in dormant form. Appended by the MetaHead
 * component, which every template calls, so no template has to change
 * (ADR tracking-consent_head-injection-via-meta-head).
 *
 * The switch is checked first (off ⇒ the code cache is not even read) and nothing at
 * all is printed when no code is set (NFR-AVAIL-tracking-kill-switch,
 * US-tracking-consent-preset-services "empty fields print nothing").
 *
 * @aidlc-unit tracking-consent
 * The cookie banner rides along as an inert <template> when the banner mode is not "off"
 * (US-tracking-consent-banner); the page itself never depends on the visitor's choice,
 * so a page cache or CDN cannot leak code to a visitor who has not agreed.
 *
 * @aidlc-story US-tracking-consent-preset-services, US-tracking-consent-custom-code, US-tracking-consent-template-hook, US-tracking-consent-banner
 * @aidlc-adr tracking-consent_head-injection-via-meta-head, tracking-consent_deferred-script-activation
 */
final class TrackingHead
{
    /** Cookie where the consent loader keeps the visitor's choice. */
    public const CONSENT_COOKIE = 'gp247_consent';

    private const REPORTED_KEY = 'gp247_tracking_head_reported';

    /** Seconds between two reports of the same failure. */
    private const REPORT_WINDOW = 600;

    /**
     * HTML for the current storefront request; '' on admin pages, when switched off or
     * when nothing is set. A failure (tables not created yet on a site that has not run
     * gp247:update) is reported and prints nothing — the page itself must still render.
     *
     * @return string
     */
    public static function html(): string
    {
        if (self::isAdminRequest()) {
            return '';
        }

        try {
            return self::build(config('app.storeId'));
        } catch (\Throwable $e) {
            // WHY once per window: this runs on every storefront page — a lasting fault must
            // not write (or post to Slack) once per visitor.
            if (function_exists('gp247_report') && Cache::add(self::REPORTED_KEY, 1, self::REPORT_WINDOW)) {
                gp247_report('[tracking-consent] head not printed: ' . $e->getMessage());
            }

            return '';
        }
    }

    /**
     * @param int|string|null $storeId
     * @return string
     */
    public static function build($storeId): string
    {
        // WHY first: a site that updated the package but has not run gp247:update yet has
        // no tracking rows and no tables — print nothing without touching the tables.
        if (!TrackingSettingsService::installed()) {
            return '';
        }
        $settings = TrackingSettingsService::forStore($storeId);
        if (!$settings->enabled) {
            return '';
        }
        $tags = ServiceTagFactory::forSettings($settings);
        $snippets = SnippetService::activeFor($storeId ?? config('app.storeId'), $settings);
        if ($tags === [] && $snippets === []) {
            return '';
        }

        $html = '<script type="application/json" id="gp247-tracking-config">'
            . DormantScript::json([
                'mode' => $settings->bannerMode,
                'version' => $settings->consentVersion,
                'labels' => ['settings' => (string) gp247_language_render('front.consent.settings')],
            ])
            . '</script>'
            . ConsentBannerData::template($settings)
            . '<script src="' . e(TrackingAssets::url()) . '" defer data-gp247-loader></script>';
        foreach ($tags as $tag) {
            /** @var ServiceTag $tag */
            $html .= DormantScript::wrap($tag->html, $tag->category, SnippetRegion::HEAD, $tag->service);
        }
        foreach ($snippets as $snippet) {
            /** @var CodeSnippet $snippet */
            $html .= DormantScript::wrap($snippet->content, $snippet->category, $snippet->region);
        }

        return $html;
    }

    /**
     * @return bool Whether this request is an admin page (tracking code never runs there — Q4).
     */
    private static function isAdminRequest(): bool
    {
        $prefix = trim(defined('GP247_ADMIN_PREFIX') ? GP247_ADMIN_PREFIX : 'gp247_admin', '/');
        $path = trim(request()->path(), '/');

        return $path === $prefix || str_starts_with($path, $prefix . '/');
    }
}
