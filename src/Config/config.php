<?php
return [        
    // Config for front
    'front' => [
        'middleware' => [
            1 => 'check.domain',
            2 => 'localization',
        ],
        'route' => [
            //Prefix member, as domain.com/customer/login
            'GP247_PREFIX_MEMBER' => env('GP247_PREFIX_MEMBER', 'customer'), 
    
            //Prefix lange on url, as domain.com/en/abc.html
            //If value is empty, it will not be displayed, as dommain.com/abc.html
            'GP247_SEO_LANG' => env('GP247_SEO_LANG', 0),
        ],
        'GP247_TEMPLATE_FRONT_DEFAULT' => env('GP247_TEMPLATE_FRONT_DEFAULT', 'Default'),
        'GP247_SUFFIX_URL'    => env('GP247_SUFFIX_URL', '.html'), //Suffix url, ex: domain.com/news/1.html 

        'layout_page' => [
            'front_home' => 'admin.layout_block_page.home',
            'front_contact' => 'admin.layout_block_page.contact',
            'front_page_list' => 'admin.layout_block_page.page_list',
            'front_page_detail' => 'admin.layout_block_page.page_detail',
            'front_news_list' => 'admin.layout_block_page.news_list',
            'front_news_detail' => 'admin.layout_block_page.news_detail',
            'front_search' => 'admin.layout_block_page.search',       
        ],
        'layout_position' => [
            'header' => 'admin.layout_block_position.header',
            'banner_top' => 'admin.layout_block_position.banner_top',
            'top' => 'admin.layout_block_position.top',
            'left' => 'admin.layout_block_position.left',
            'right' => 'admin.layout_block_position.right',
            'bottom' => 'admin.layout_block_position.bottom',
        ],
    ],
];
