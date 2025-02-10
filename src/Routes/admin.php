<?php
use Illuminate\Support\Facades\Route;

// Page
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


// Banner
if (file_exists(app_path('GP247/Front/Controllers/Admin/AdminBannerController.php'))) {
    $nameSpaceAdminBanner = 'App\GP247\Front\Controllers\Admin';
} else {
    $nameSpaceAdminBanner = 'GP247\Front\Controllers\Admin';
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
if (file_exists(app_path('GP247/Front/Controllers/Admin/AdminBannerTypeController.php'))) {
    $nameSpaceAdminBannerType = 'App\GP247\Front\Controllers\Admin';
} else {
    $nameSpaceAdminBannerType = 'GP247\Front\Controllers\Admin';
}
Route::group(['prefix' => 'banner_type'], function () use ($nameSpaceAdminBannerType) {
    Route::get('/', $nameSpaceAdminBannerType.'\AdminBannerTypeController@index')->name('admin_banner_type.index');
    Route::get('create', $nameSpaceAdminBannerType.'\AdminBannerTypeController@create')->name('admin_banner_type.create');
    Route::post('/create', $nameSpaceAdminBannerType.'\AdminBannerTypeController@postCreate')->name('admin_banner_type.post_create');
    Route::get('/edit/{id}', $nameSpaceAdminBannerType.'\AdminBannerTypeController@edit')->name('admin_banner_type.edit');
    Route::post('/edit/{id}', $nameSpaceAdminBannerType.'\AdminBannerTypeController@postEdit')->name('admin_banner_type.post_edit');
    Route::post('/delete', $nameSpaceAdminBannerType.'\AdminBannerTypeController@deleteList')->name('admin_banner_type.delete');
});

// Link
if (file_exists(app_path('GP247/Front/Controllers/Admin/AdminLinkController.php'))) {
    $nameSpaceAdminLink = 'App\GP247\Front\Controllers\Admin';
} else {
    $nameSpaceAdminLink = 'GP247\Front\Controllers\Admin';
}
Route::group(['prefix' => 'link'], function () use ($nameSpaceAdminLink) {
    Route::get('/', $nameSpaceAdminLink.'\AdminLinkController@index')->name('admin_link.index');
    Route::get('create', $nameSpaceAdminLink.'\AdminLinkController@create')->name('admin_link.create');
    Route::post('/create', $nameSpaceAdminLink.'\AdminLinkController@postCreate')->name('admin_link.post_create');
    Route::get('collection_create', $nameSpaceAdminLink.'\AdminLinkController@collectionCreate')->name('admin_link.collection_create');
    Route::post('/collection_create', $nameSpaceAdminLink.'\AdminLinkController@postCollectionCreate')->name('admin_link.post_collection_create');
    Route::get('/edit/{id}', $nameSpaceAdminLink.'\AdminLinkController@edit')->name('admin_link.edit');
    Route::post('/edit/{id}', $nameSpaceAdminLink.'\AdminLinkController@postEdit')->name('admin_link.post_edit');
    Route::post('/delete', $nameSpaceAdminLink.'\AdminLinkController@deleteList')->name('admin_link.delete');
});

// Link group
if (file_exists(app_path('GP247/Front/Controllers/Admin/AdminLinkGroupController.php'))) {
    $nameSpaceAdminLinkGroup = 'App\GP247\Front\Controllers\Admin';
} else {
    $nameSpaceAdminLinkGroup = 'GP247\Front\Controllers\Admin';
}
Route::group(['prefix' => 'link_group'], function () use ($nameSpaceAdminLinkGroup) {
    Route::get('/', $nameSpaceAdminLinkGroup.'\AdminLinkGroupController@index')->name('admin_link_group.index');
    Route::get('create', function () {
        return redirect()->route('admin_link_group.index');
    });
    Route::post('/create', $nameSpaceAdminLinkGroup.'\AdminLinkGroupController@postCreate')->name('admin_link_group.create');
    Route::get('/edit/{id}', $nameSpaceAdminLinkGroup.'\AdminLinkGroupController@edit')->name('admin_link_group.edit');
    Route::post('/edit/{id}', $nameSpaceAdminLinkGroup.'\AdminLinkGroupController@postEdit')->name('admin_link_group.post_edit');
    Route::post('/delete', $nameSpaceAdminLinkGroup.'\AdminLinkGroupController@deleteList')->name('admin_link_group.delete');
});

// Layout block
if (file_exists(app_path('GP247/Front/Controllers/Admin/AdminLayoutBlockController.php'))) {
    $nameSpaceAdminLayoutBlock = 'App\GP247\Front\Controllers\Admin';
} else {
    $nameSpaceAdminLayoutBlock = 'GP247\Front\Controllers\Admin';
}
Route::group(['prefix' => 'layout_block'], function () use ($nameSpaceAdminLayoutBlock) {
    Route::get('/', $nameSpaceAdminLayoutBlock.'\AdminLayoutBlockController@index')->name('admin_layout_block.index');
    Route::get('create', $nameSpaceAdminLayoutBlock.'\AdminLayoutBlockController@create')->name('admin_layout_block.create');
    Route::post('/create', $nameSpaceAdminLayoutBlock.'\AdminLayoutBlockController@postCreate')->name('admin_layout_block.post_create');
    Route::get('/edit/{id}', $nameSpaceAdminLayoutBlock.'\AdminLayoutBlockController@edit')->name('admin_layout_block.edit');
    Route::post('/edit/{id}', $nameSpaceAdminLayoutBlock.'\AdminLayoutBlockController@postEdit')->name('admin_layout_block.post_edit');
    Route::get('/listblock_view', $nameSpaceAdminLayoutBlock.'\AdminLayoutBlockController@getListViewBlockHtml')->name('admin_layout_block.listblock_view');
    Route::get('/listblock_page', $nameSpaceAdminLayoutBlock.'\AdminLayoutBlockController@getListPageBlockHtml')->name('admin_layout_block.listblock_page');
    Route::post('/delete', $nameSpaceAdminLayoutBlock.'\AdminLayoutBlockController@deleteList')->name('admin_layout_block.delete');
});

// Template
if (file_exists(app_path('GP247/Front/Controllers/Admin/AdminTemplateController.php'))) {
    $nameSpaceAdminTemplate = 'App\GP247\Front\Controllers\Admin';
} else {
    $nameSpaceAdminTemplate = 'GP247\Front\Controllers\Admin';
}
Route::group(['prefix' => 'template'], function () use ($nameSpaceAdminTemplate) {
    //Process import
    Route::get('/import', $nameSpaceAdminTemplate.'\AdminTemplateController@importExtension')
        ->name('admin_template.import');
    Route::post('/import', $nameSpaceAdminTemplate.'\AdminTemplateController@processImport')
        ->name('admin_template.process_import');
    //End process

    Route::get('/', $nameSpaceAdminTemplate.'\AdminTemplateController@index')->name('admin_template.index');
    Route::post('install', $nameSpaceAdminTemplate.'\AdminTemplateController@install')->name('admin_template.install');
    Route::post('uninstall', $nameSpaceAdminTemplate.'\AdminTemplateController@uninstall')->name('admin_template.uninstall');
    Route::post('enable', $nameSpaceAdminTemplate.'\AdminTemplateController@enable')->name('admin_template.enable');
    Route::post('disable', $nameSpaceAdminTemplate.'\AdminTemplateController@disable')->name('admin_template.disable');

    if (config('gp247-config.admin.api_templates')) {
        Route::get('/online', $nameSpaceAdminTemplate.'\AdminTemplateOnlineController@index')->name('admin_template_online.index');
        Route::post('/online/install', $nameSpaceAdminTemplate.'\AdminTemplateOnlineController@install')
        ->name('admin_template_online.install');
    }
});