<?php
if (file_exists(app_path('GP247/Front/Controllers/Admin/AdminPageController.php'))) {
    $nameSpaceHome = 'App\GP247\Front\Controllers\Admin';
} else {
    $nameSpaceHome = 'GP247\Front\Controllers\Admin';
}
Route::group(['prefix' => 'page'], function () use ($nameSpaceHome) {
    Route::get('/', $nameSpaceHome.'\AdminPageController@index')->name('admin_page.index');
    Route::get('create', $nameSpaceHome.'\AdminPageController@create')->name('admin_page.create');
    Route::post('/create', $nameSpaceHome.'\AdminPageController@postCreate')->name('admin_page.post_create');
    Route::get('/edit/{id}', $nameSpaceHome.'\AdminPageController@edit')->name('admin_page.edit');
    Route::post('/edit/{id}', $nameSpaceHome.'\AdminPageController@postEdit')->name('admin_page.post_edit');
    Route::post('/delete', $nameSpaceHome.'\AdminPageController@deleteList')->name('admin_page.delete');
});


if (file_exists(app_path('GP247/Front/Controllers/Admin/AdminBannerController.php'))) {
    $nameSpaceHome = 'App\GP247\Front\Controllers\Admin';
} else {
    $nameSpaceHome = 'GP247\Front\Controllers\Admin';
}
Route::group(['prefix' => 'banner'], function () use ($nameSpaceHome) {
    Route::get('/', $nameSpaceHome.'\AdminBannerController@index')->name('admin_banner.index');
    Route::get('create', $nameSpaceHome.'\AdminBannerController@create')->name('admin_banner.create');
    Route::post('/create', $nameSpaceHome.'\AdminBannerController@postCreate')->name('admin_banner.post_create');
    Route::get('/edit/{id}', $nameSpaceHome.'\AdminBannerController@edit')->name('admin_banner.edit');
    Route::post('/edit/{id}', $nameSpaceHome.'\AdminBannerController@postEdit')->name('admin_banner.post_edit');
    Route::post('/delete', $nameSpaceHome.'\AdminBannerController@deleteList')->name('admin_banner.delete');
});
