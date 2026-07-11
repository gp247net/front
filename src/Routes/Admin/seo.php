<?php
use GP247\Front\Admin\Livewire\SeoMetaSettings;
use GP247\Front\Admin\Livewire\SeoSitemapSettings;
use Illuminate\Support\Facades\Route;

// SEO settings (US-SEO-004/005): 2 single-record screens (no create/edit
// variants, mirrors store_info.php), split (modification 20260711T154553) so
// each can be granted as a separate RBAC permission — "Meta & JSON-LD"
// (robots.txt + JSON-LD toggle) vs "Sitemap.xml" (sitemap toggles/rebuild).
Route::group(['prefix' => 'seo'], function () {
    Route::get('meta', SeoMetaSettings::class)->name('admin_seo_meta.index');
    Route::get('sitemap', SeoSitemapSettings::class)->name('admin_seo_sitemap.index');
});
