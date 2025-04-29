<?php
use Illuminate\Support\Facades\Route;

// Banner
if (file_exists(app_path('GP247/Front/Admin/Controllers/AdminBannerController.php'))) {
    $nameSpaceAdminBanner = 'App\GP247\Front\Admin\Controllers';
} else {
    $nameSpaceAdminBanner = 'GP247\Front\Admin\Controllers';
}
Route::group(['prefix' => 'banner'], function () use ($nameSpaceAdminBanner) {
    Route::get('/', $nameSpaceAdminBanner.'\AdminBannerController@index')->name('admin_banner.index');
    Route::get('create', $nameSpaceAdminBanner.'\AdminBannerController@create')->name('admin_banner.create');
    Route::post('/create', $nameSpaceAdminBanner.'\AdminBannerController@postCreate')->name('admin_banner.post_create');
    Route::get('/edit/{id}', $nameSpaceAdminBanner.'\AdminBannerController@edit')->name('admin_banner.edit');
    Route::post('/edit/{id}', $nameSpaceAdminBanner.'\AdminBannerController@postEdit')->name('admin_banner.post_edit');
    Route::post('/delete', $nameSpaceAdminBanner.'\AdminBannerController@deleteList')->name('admin_banner.delete');
});

// Banner type
if (file_exists(app_path('GP247/Front/Admin/Controllers/AdminBannerTypeController.php'))) {
    $nameSpaceAdminBannerType = 'App\GP247\Front\Admin\Controllers';
} else {
    $nameSpaceAdminBannerType = 'GP247\Front\Admin\Controllers';
}
Route::group(['prefix' => 'banner_type'], function () use ($nameSpaceAdminBannerType) {
    Route::get('/', $nameSpaceAdminBannerType.'\AdminBannerTypeController@index')->name('admin_banner_type.index');
    Route::get('create', $nameSpaceAdminBannerType.'\AdminBannerTypeController@create')->name('admin_banner_type.create');
    Route::post('/create', $nameSpaceAdminBannerType.'\AdminBannerTypeController@postCreate')->name('admin_banner_type.post_create');
    Route::get('/edit/{id}', $nameSpaceAdminBannerType.'\AdminBannerTypeController@edit')->name('admin_banner_type.edit');
    Route::post('/edit/{id}', $nameSpaceAdminBannerType.'\AdminBannerTypeController@postEdit')->name('admin_banner_type.post_edit');
    Route::post('/delete', $nameSpaceAdminBannerType.'\AdminBannerTypeController@deleteList')->name('admin_banner_type.delete');
});
