<?php
use GP247\Front\Admin\Livewire\BannerForm;
use GP247\Front\Admin\Livewire\BannerList;
use GP247\Front\Admin\Livewire\BannerTypeForm;
use GP247\Front\Admin\Livewire\BannerTypeList;
use Illuminate\Support\Facades\Route;

// Banner — cutover (PA-1): the legacy URLs now render the modern Livewire/TailAdmin
// screens in-place (keep route name + path + http_uri). CRUD mutations flow through
// livewire/update, so the POST create/edit/delete routes are removed. RBAC slug
// derives from the component (admin_banner), so the URI-based permission is unchanged.
Route::group(['prefix' => 'banner'], function () {
    Route::get('/', BannerList::class)->name('admin_banner.index');
    Route::get('create', BannerForm::class)->name('admin_banner.create');
    Route::get('/edit/{id}', BannerForm::class)->name('admin_banner.edit');
});

// Banner type
Route::group(['prefix' => 'banner_type'], function () {
    Route::get('/', BannerTypeList::class)->name('admin_banner_type.index');
    Route::get('create', BannerTypeForm::class)->name('admin_banner_type.create');
    Route::get('/edit/{id}', BannerTypeForm::class)->name('admin_banner_type.edit');
});
