<?php

namespace GP247\Front\Support;

/**
 * Catalog of front storefront page-types for the LayoutBlock "Page" scope.
 *
 * Single source of truth: each case value is the `$layout_page` token a front
 * screen/controller emits at render time (matched by `gp247_render_block()`
 * against `FrontLayoutBlock.page`). `label()` maps to the i18n code shown in the
 * admin dropdown; `registry()` builds the token => label map registered into
 * `config('gp247-config.front.layout_page')`.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-004
 * @aidlc-adr ADR-front-admin-layout-page-enum-catalog
 */
enum FrontLayoutPage: string
{
    case Home = 'front_home';
    case PageDetail = 'front_page_detail';
    case Search = 'front_search';

    /**
     * i18n label code (seeded in DataFrontDefaultSeeder, group admin.layout_block).
     *
     * WHY: front's legacy label codes do NOT follow the token spelling
     * (`admin.layout_block_page.home`, not `.front_home`), so the mapping is
     * explicit rather than derived from the case value.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Home => 'admin.layout_block_page.home',
            self::PageDetail => 'admin.layout_block_page.page_detail',
            self::Search => 'admin.layout_block_page.search',
        };
    }

    /**
     * Build the token => i18n label code map for registry registration.
     *
     * @return array<string, string>
     */
    public static function registry(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }
        return $out;
    }
}
