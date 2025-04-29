<?php
use Illuminate\Support\Facades\Route;

// Link
if (file_exists(app_path('GP247/Front/Admin/Controllers/AdminLinkController.php'))) {
    $nameSpaceAdminLink = 'App\GP247\Front\Admin\Controllers';
} else {
    $nameSpaceAdminLink = 'GP247\Front\Admin\Controllers';
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
if (file_exists(app_path('GP247/Front/Admin/Controllers/AdminLinkGroupController.php'))) {
    $nameSpaceAdminLinkGroup = 'App\GP247\Front\Admin\Controllers';
} else {
    $nameSpaceAdminLinkGroup = 'GP247\Front\Admin\Controllers';
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