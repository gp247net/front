<?php
use Illuminate\Support\Facades\Route;
    
$listAbility = [
    config('gp247-config.api.auth.api_scope_admin'),
    config('gp247-config.api.auth.api_scope_admin_supper')
];


Route::group([
    'middleware' => [
        'auth:admin-api', 
        'ability:'.implode(',', $listAbility)
    ],
    'prefix' => GP247_API_CORE_PREFIX,
], function (){
        if (file_exists(app_path('GP247/Front/Api/AdminController.php'))) {
            $nameSpaceAdmin = 'App\GP247\Front\Api';
        } else {
            $nameSpaceAdmin = 'GP247\Front\Api';
        }
        Route::group([
            'prefix' => 'banner',
        ], function () use($nameSpaceAdmin) {
            Route::get('list', $nameSpaceAdmin.'\AdminController@getBannerList');
            Route::get('detail/{id}', $nameSpaceAdmin.'\AdminController@getBannerDetail');
        });
    
        Route::group([
            'prefix' => 'page',
        ], function () use($nameSpaceAdmin) {
            Route::get('list', $nameSpaceAdmin.'\AdminController@getPageList');
            Route::get('detail/{id}', $nameSpaceAdmin.'\AdminController@getPageDetail');
        });

});
