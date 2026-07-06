<?php

namespace GP247\Front\TemplateComponents;

/**
 * Language switcher (US-TPL-008): builds the $languages list once here so no
 * template Blade view needs to call gp247_language_all() itself.
 *
 * @aidlc-unit frontend-template-dev
 * @aidlc-story US-TPL-008
 * @aidlc-adr ADR-013
 */
class LanguageSwitcher extends BaseFrontViewComponent
{
    /** @var array<int, array{code: string, name: string, icon: string, active: bool, url: string}> */
    public array $languages;

    public function __construct()
    {
        $this->languages = [];

        if (!gp247_config('link_language', null, 1) || !function_exists('gp247_language_all')) {
            return;
        }

        $all = gp247_language_all();
        if (count($all) <= 1) {
            return;
        }

        $current = app()->getLocale();
        foreach ($all as $code => $language) {
            $this->languages[] = [
                'code' => $code,
                'name' => $language['name'],
                'icon' => $language['icon'],
                'active' => $code === $current,
                'url' => gp247_route_front('front.locale', ['code' => $code]),
            ];
        }
    }

    protected function templateViewKey(): string
    {
        return 'gp247_components.language-switcher';
    }

    protected function defaultViewNamespace(): string
    {
        return 'gp247-front';
    }
}
