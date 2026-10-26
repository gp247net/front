<?php

use GP247\Core\Api\Support\ApiAbilities;
use GP247\Front\Api\V2\ContentController;
use Illuminate\Support\Facades\Route;

// REST API v2 — admin banners and CMS pages; each endpoint declares its admin screen
// (api.admin:<admin route name>, same mapping as v1 — US-RBAC-api-route-permission).
Route::group([
    'prefix'     => GP247_API_V2_CORE_PREFIX,
    'middleware' => ['auth:admin-api', ApiAbilities::admin()],
], function () {
    $content = gp247_namespace(ContentController::class);
    Route::get('banner/list', $content.'@adminBannerList')->middleware('api.admin:admin_banner.index');
    Route::get('banner/detail/{id}', $content.'@adminBannerDetail')->middleware('api.admin:admin_banner.index');
    Route::get('page/list', $content.'@adminPageList')->middleware('api.admin:admin_page.index');
    Route::get('page/detail/{id}', $content.'@adminPageDetail')->middleware('api.admin:admin_page.index');
});
