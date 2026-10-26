<?php

use GP247\Front\Api\V2\ContentController;
use Illuminate\Support\Facades\Route;

// REST API v2 — public banners, CMS pages and the API description (US-CMP-api-v2-contract).
Route::group([
    'prefix' => GP247_API_V2_FRONT_PREFIX,
], function () {
    $content = gp247_namespace(ContentController::class);
    Route::get('meta', $content.'@meta');
    Route::get('banner/list', $content.'@bannerList');
    Route::get('banner/detail/{id}', $content.'@bannerDetail');
    Route::get('page/list', $content.'@pageList');
    Route::get('page/detail/{id}', $content.'@pageDetail');
});
