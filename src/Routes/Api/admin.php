<?php

use GP247\Front\Api\AdminController;
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
        $adminController = gp247_namespace(AdminController::class);
        Route::group([
            'prefix' => 'banner',
        ], function () use($adminController) {
            Route::get('list', $adminController.'@getBannerList');
            Route::get('detail/{id}', $adminController.'@getBannerDetail');
        });

        Route::group([
            'prefix' => 'page',
        ], function () use($adminController) {
            Route::get('list', $adminController.'@getPageList');
            Route::get('detail/{id}', $adminController.'@getPageDetail');
        });

});
