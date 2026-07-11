<?php
use GP247\Front\Admin\Livewire\SeoSettings;
use Illuminate\Support\Facades\Route;

// SEO settings (US-SEO-004): robots.txt editor + sitemap rebuild/toggles,
// single settings screen (no create/edit variants, mirrors store_info.php).
Route::group(['prefix' => 'seo'], function () {
    Route::get('/', SeoSettings::class)->name('admin_seo.index');
});
