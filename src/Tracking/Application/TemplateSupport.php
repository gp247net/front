<?php

namespace GP247\Front\Tracking\Application;

/**
 * Whether a storefront template calls the meta-head component — the one place the
 * tracking layer reaches every page through (ADR tracking-consent_head-injection-via-meta-head).
 * A template that does not call it gets no tracking code and no banner at all.
 *
 * Reads the template's layout files through the view hint paths (gp247_template_files —
 * never a glob of app/, rule gp247.md §2a), so a template running from the package and a
 * published override are both seen.
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-template-hook, US-tracking-consent-health-check
 * @aidlc-adr tracking-consent_head-injection-via-meta-head
 */
final class TemplateSupport
{
    private const CALL_PATTERN = '/<x-gp247-front::meta-head\b|TemplateComponents\\\\MetaHead\b/';

    /** @var array<string, bool> template => calls meta-head (per process) */
    private static array $memo = [];

    /**
     * @param string $template Template name, e.g. "GP247Front".
     * @return bool Whether its layout (layout.blade.php or layout/*.blade.php) calls meta-head.
     *              An unknown template (no layout found) counts as calling it: nothing to warn about.
     */
    public static function callsMetaHead(string $template): bool
    {
        if ($template === '' || !function_exists('gp247_template_files')) {
            return true;
        }
        if (array_key_exists($template, self::$memo)) {
            return self::$memo[$template];
        }
        $files = gp247_template_files($template, '', 'layout.blade.php') + gp247_template_files($template, 'layout');
        if ($files === []) {
            return self::$memo[$template] = true;
        }
        foreach ($files as $file) {
            // WHY strip comments: a call left in a Blade comment renders nothing.
            $source = (string) preg_replace('/\{\{--.*?--\}\}/s', '', (string) @file_get_contents($file));
            if (preg_match(self::CALL_PATTERN, $source) === 1) {
                return self::$memo[$template] = true;
            }
        }

        return self::$memo[$template] = false;
    }

    /**
     * @param int|string|null $storeId Null = the store of the request.
     * @return string Template the store uses.
     */
    public static function templateOf($storeId = null): string
    {
        return (string) gp247_store_info('template', null, $storeId ?? config('app.storeId'));
    }

    /**
     * Forget the per-process answers (tests, or after a template switch).
     *
     * @return void
     */
    public static function forget(): void
    {
        self::$memo = [];
    }
}
