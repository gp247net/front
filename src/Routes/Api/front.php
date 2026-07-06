<?php

use GP247\Front\Api\FrontController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => GP247_API_FRONT_PREFIX,
], function (){

    $frontController = gp247_namespace(FrontController::class);
    Route::group([
        'prefix' => 'banner',
    ], function () use($frontController) {
        Route::get('list', $frontController.'@getBannerList');
        Route::get('detail/{id}', $frontController.'@getBannerDetail');
    });

    Route::group([
        'prefix' => 'page',
    ], function () use($frontController) {
        Route::get('list', $frontController.'@getPageList');
        Route::get('detail/{id}', $frontController.'@getPageDetail');
    });


});
