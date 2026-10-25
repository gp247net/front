<?php
use GP247\Front\Admin\Livewire\LinkGroupForm;
use GP247\Front\Admin\Livewire\LinkGroupList;
use GP247\Front\Admin\Livewire\LinkManager;
use Illuminate\Support\Facades\Route;

// Two-panel manager (modification 20260630): form + list on one page via
// LinkManager (ResourcePanel). Route names kept for back-compat.
Route::group(['prefix' => 'link'], function () {
    Route::get('/', gp247_namespace(LinkManager::class))->name('admin_link.index');
    Route::get('create', gp247_namespace(LinkManager::class))->name('admin_link.create');
    Route::get('/edit/{id}', gp247_namespace(LinkManager::class))->name('admin_link.edit');
});

// Link group
Route::group(['prefix' => 'link_group'], function () {
    Route::get('/', gp247_namespace(LinkGroupList::class))->name('admin_link_group.index');
    Route::get('create', gp247_namespace(LinkGroupForm::class))->name('admin_link_group.create');
    Route::get('/edit/{id}', gp247_namespace(LinkGroupForm::class))->name('admin_link_group.edit');
});
