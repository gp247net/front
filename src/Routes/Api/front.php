<?php
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => GP247_API_FRONT_PREFIX,
], function (){
    
    if (file_exists(app_path('GP247/Front/Api/FrontController.php'))) {
        $nameSpaceFront = 'App\GP247\Front\Api';
    } else {
        $nameSpaceFront = 'GP247\Front\Api';
    }
    Route::group([
        'prefix' => 'banner',
    ], function () use($nameSpaceFront) {
        Route::get('list', $nameSpaceFront.'\FrontController@getBannerList');
        Route::get('detail/{id}', $nameSpaceFront.'\FrontController@getBannerDetail');
    });

    Route::group([
        'prefix' => 'page',
    ], function () use($nameSpaceFront) {
        Route::get('list', $nameSpaceFront.'\FrontController@getPageList');
        Route::get('detail/{id}', $nameSpaceFront.'\FrontController@getPageDetail');
    });


});
