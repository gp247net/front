<?php
use GP247\Front\Admin\Livewire\LayoutBlockManager;
use Illuminate\Support\Facades\Route;

// Two-panel manager (modification 20260630): form + list on one page via
// LayoutBlockManager (ResourcePanel). Route names kept for back-compat;
// create route maps to the same manager (form is always visible on the left panel).
Route::group(['prefix' => 'layout_block'], function () {
    Route::get('/', LayoutBlockManager::class)->name('admin_layout_block.index');
    Route::get('create', LayoutBlockManager::class)->name('admin_layout_block.create');
    Route::get('/edit/{id}', LayoutBlockManager::class)->name('admin_layout_block.edit');
});
