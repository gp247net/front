<?php
// Add new helper function here

if (! function_exists('gp247front_is_rtl')) {
    /**
     * Determine whether the currently active locale's admin-configured
     * language is flagged RTL (`admin_language.rtl`).
     *
     * WHY a local helper instead of reusing a `gp247/core` helper: no
     * existing helper exposes the `rtl` column (gp247_language_all() /
     * LanguageSwitcher only expose code/name/icon/url), and this template
     * may not add PHP outside its own directory (US-TPL-009 AC), so RTL
     * resolution is self-contained here using the already-public
     * AdminLanguage::getListActive() model method.
     *
     * @return bool True when the active language's `rtl` flag is set.
     *
     * @aidlc-unit frontend-template-dev
     * @aidlc-story US-TPL-009
     * @aidlc-adr ADR-014
     */
    function gp247front_is_rtl(): bool
    {
        $locale = app()->getLocale();
        $language = \GP247\Core\Models\AdminLanguage::getListActive()->get($locale);

        return (bool) ($language->rtl ?? false);
    }
}
