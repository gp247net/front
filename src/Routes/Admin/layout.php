<?php
use Illuminate\Support\Facades\Route;
// Layout block
if (file_exists(app_path('GP247/Front/Admin/Controllers/AdminLayoutBlockController.php'))) {
    $nameSpaceAdminLayoutBlock = 'App\GP247\Front\Admin\Controllers';
} else {
    $nameSpaceAdminLayoutBlock = 'GP247\Front\Admin\Controllers';
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