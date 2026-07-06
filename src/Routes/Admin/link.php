<?php
use GP247\Front\Admin\Livewire\LinkGroupForm;
use GP247\Front\Admin\Livewire\LinkGroupList;
use GP247\Front\Admin\Livewire\LinkManager;
use Illuminate\Support\Facades\Route;

// Two-panel manager (modification 20260630): form + list on one page via
// LinkManager (ResourcePanel). Route names kept for back-compat.
Route::group(['prefix' => 'link'], function () {
    Route::get('/', LinkManager::class)->name('admin_link.index');
    Route::get('create', LinkManager::class)->name('admin_link.create');
    Route::get('/edit/{id}', LinkManager::class)->name('admin_link.edit');
});

// Link group
Route::group(['prefix' => 'link_group'], function () {
    Route::get('/', LinkGroupList::class)->name('admin_link_group.index');
    Route::get('create', LinkGroupForm::class)->name('admin_link_group.create');
    Route::get('/edit/{id}', LinkGroupForm::class)->name('admin_link_group.edit');
});
