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