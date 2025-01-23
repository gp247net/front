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
        'GP247_TEMPLATE_FRONT_DEFAULT' => env('GP247_TEMPLATE_FRONT_DEFAULT', 'default'),
    ],
];
