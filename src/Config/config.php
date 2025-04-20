<?php
return [        
    // Config for front
    'front' => [
        'middleware' => [
            1 => 'check.domain',
            2 => 'localization',
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
        'GP247_TEMPLATE_FRONT_DEFAULT' => env('GP247_TEMPLATE_FRONT_DEFAULT', 'Default'),
        'GP247_SUFFIX_URL'    => env('GP247_SUFFIX_URL', '.html'), //Suffix url, ex: domain.com/news/1.html 

        'layout_page' => [
            'front_home' => 'admin.layout_block_page.home',
            'front_contact' => 'admin.layout_block_page.contact',
            'front_about' => 'admin.layout_block_page.about',
            'front_page_list' => 'admin.layout_block_page.page_list',
            'front_page_detail' => 'admin.layout_block_page.page_detail',
            'front_news_list' => 'admin.layout_block_page.news_list',
            'front_news_detail' => 'admin.layout_block_page.news_detail',
            'front_search' => 'admin.layout_block_page.search',       
        ],
        'layout_position' => [
            'top_site' => 'admin.layout_block_position.top_site',
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
