<?php

namespace GP247\Front\TemplateComponents;

/**
 * Currency switcher (US-TPL-008): builds the $currencies list once here so
 * no template Blade view needs to call gp247_currency_all() itself. Only
 * meaningful when the shop package (gp247_cart()) is installed.
 *
 * @aidlc-unit frontend-template-dev
 * @aidlc-story US-TPL-008
 * @aidlc-adr ADR-013
 */
class CurrencySwitcher extends BaseFrontViewComponent
{
    /** @var array<int, array{code: string, name: string, url: string}> */
    public array $currencies;

    public string $activeCode;

    public function __construct()
    {
        $this->currencies = [];
        $this->activeCode = '';

        if (!function_exists('gp247_cart') || !gp247_config('link_currency', null, 1) || !function_exists('gp247_currency_all')) {
            return;
        }

        $all = gp247_currency_all();
        if (count($all) <= 1) {
            return;
        }

        $this->activeCode = gp247_currency_info()['code'] ?? '';

        foreach ($all as $currency) {
            $this->currencies[] = [
                'code' => $currency->code,
                'name' => $currency->name,
                'url' => gp247_route_front('front.currency', ['code' => $currency->code]),
            ];
        }
    }

    protected function templateViewKey(): string
    {
        return 'gp247_components.currency-switcher';
    }

    protected function defaultViewNamespace(): string
    {
        return 'gp247-front';
    }
}
