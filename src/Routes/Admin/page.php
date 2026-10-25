<?php
use GP247\Front\Admin\Livewire\PageManager;
use Illuminate\Support\Facades\Route;

// Page — two-panel manager (US-FADM-002, ADR-005): PageManager handles create and
// edit in the same screen (form left, list right). The create route also resolves to
// PageManager (no id → create mode) so existing references keep working.
Route::group(['prefix' => 'page'], function () {
    Route::get('/', gp247_namespace(PageManager::class))->name('admin_page.index');
    Route::get('create', gp247_namespace(PageManager::class))->name('admin_page.create');
    Route::get('/edit/{id}', gp247_namespace(PageManager::class))->name('admin_page.edit');
});
