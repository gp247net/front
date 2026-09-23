<?php
return [        
    // Config for front
    'front' => [
        'middleware' => [
            1 => 'check.domain',
            2 => 'localization',
            3 => 'check.active',
            4 => 'front.redirect',
        ],
        'route' => [
            //Prefix lange on url, as domain.com/en/abc.html
            //If value is empty, it will not be displayed, as dommain.com/abc.html
            'GP247_SEO_LANG' => env('GP247_SEO_LANG', 0),

            //Route exclude language, ex: front.home, front.locale, front.banner.click
            //when GP247_SEO_LANG = 1, url of this route will not be displayed with language, as domain.com/abc.html
            // default: front.home, front.locale, front.banner.click will not be displayed with language on url
            'GP247_ROUTE_EXCLUDE_LANGUAGE' => env('GP247_ROUTE_EXCLUDE_LANGUAGE', ''),
        ],
        // WHY: 'Default' was removed entirely from the project (modification
        // 20260705T124936, ADR-014 Amend #1) — GP247Front is now the sole template.
        'GP247_TEMPLATE_FRONT_DEFAULT' => env('GP247_TEMPLATE_FRONT_DEFAULT', 'GP247Front'),
        'GP247_SUFFIX_URL'    => env('GP247_SUFFIX_URL', '.html'), //Suffix url, ex: domain.com/news/1.html 

        // Page-type registry for LayoutBlock "Page" scope (matched at render
        // against $layout_page). Base = front's own page-types, sourced from the
        // FrontLayoutPage enum — single source of truth (token + label +
        // registration in one place; ADR front-admin_layout-page-enum-catalog,
        // modification 20260729T054157). Runtime-append: shop adds its page-types
        // in ShopServiceProvider, plugins in their Provider (same idiom as
        // seo_sitemap_providers).
        'layout_page' => \GP247\Front\Support\FrontLayoutPage::registry(),
        // Sitemap URL providers contributed by plugins (US-PLG-007, ADR
        // seo_plugin-sitemap-extension): each plugin appends a [Class, 'method']
        // callable to this array from its own Provider.php (same runtime-append
        // idiom already used above for 'layout_page'), gated by its own
        // gp247_extension_check_active() check. SeoController::collectUrls()
        // reads this list — front never hardcodes a plugin's name.
        'seo_sitemap_providers' => [],
        // Storefront extension points a plugin can render into WITHOUT editing a
        // template (ADR front_storefront-plugin-hooks). Shape:
        //   ['<hook-name>' => [ ['key' => 'PluginKey', 'callback' => [Class, 'method']], ... ]]
        // A screen calls gp247_render_plugin_hook('<hook-name>', [...data]); each
        // registered callback receives that data and returns HTML. Plugins append
        // from their own Provider.php, gated by gp247_extension_check_active() —
        // the same runtime-append idiom as layout_page and seo_sitemap_providers
        // above, so front never hardcodes a plugin's name.
        //
        // WHY this exists: a storefront screen is template-owned Blade, so before
        // this registry every plugin that wanted to show something on a product
        // page had to have each site edit its template by hand.
        'plugin_hooks' => [],
        // Storefront blocks contributed by a plugin, for the LayoutBlock screen.
        // Shape: ['<block name>' => '<view key>'], e.g.
        //   ['product_flash_sale' => 'Plugins/ProductFlashSale::blocks.product_flash_sale']
        // Plugins append from their own Provider.php inside the existing
        // gp247_extension_check_active() block — the same runtime-append idiom as
        // layout_page, seo_sitemap_providers and plugin_hooks above.
        //
        // WHY this exists (ADR frontend-template-dev_plugin-layout-block-views): a
        // block is resolved as GP247TemplatePath::<template>.blocks.<name>, and the
        // template name is a PATH SEGMENT — so before this registry, a plugin could
        // only offer a block by shipping a directory named after somebody else's
        // template, or by copying a file into app/GP247/Templates at install time.
        // The first hardcodes a template it does not own (and pollutes
        // TemplateSourceAudit::roots()); the second needs a writable directory,
        // misses every other template, and orphans the file when the plugin goes.
        //
        // The template's own file is always looked up FIRST (gp247_render_block),
        // so a site that published a block to edit it keeps winning.
        'layout_block_views' => [],
        'layout_position' => [
            // WHY 'header' and not 'top_site': this key is matched at render against
            // the position each template emits, and the only call that reaches the
            // document <head> is gp247_render_block('header') in GP247Front's
            // layout.blade.php. 'top_site' was offered here but rendered nowhere, so a
            // block an admin filed under it silently never appeared (modification
            // 20260922T*). Installed sites are carried over by the upgrade migration
            // 2026_09_22_090000_rename_top_site_position_to_header.
            'header' => 'admin.layout_block_position.header',
            'top' => 'admin.layout_block_position.top',
            'left' => 'admin.layout_block_position.left',
            'center' => 'admin.layout_block_position.center',
            'right' => 'admin.layout_block_position.right',
            'bottom' => 'admin.layout_block_position.bottom',
            'footer' => 'admin.layout_block_position.footer',
        ],
        'GP247_SEARCH_MODE' => env('GP247_SEARCH_MODE', 'PRODUCT'), //PRODUCT, NEWS, PAGE
    ],
];
