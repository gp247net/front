<?php

use GP247\Front\Api\AdminController;
use Illuminate\Support\Facades\Route;



Route::group([
    'middleware' => [
        'auth:admin-api',
        \GP247\Core\Api\Support\ApiAbilities::admin(),
    ],
    'prefix' => GP247_API_CORE_PREFIX,
], function (){
        $adminController = gp247_namespace(AdminController::class);
        Route::group([
            'prefix' => 'banner',
            'middleware' => 'api.admin:admin_banner.index',
        ], function () use($adminController) {
            Route::get('list', $adminController.'@getBannerList');
            Route::get('detail/{id}', $adminController.'@getBannerDetail');
        });

        Route::group([
            'prefix' => 'page',
            'middleware' => 'api.admin:admin_page.index',
        ], function () use($adminController) {
            Route::get('list', $adminController.'@getPageList');
            Route::get('detail/{id}', $adminController.'@getPageDetail');
        });

});
