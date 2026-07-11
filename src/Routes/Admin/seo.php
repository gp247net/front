<?php
use GP247\Front\Admin\Livewire\SeoMetaSettings;
use GP247\Front\Admin\Livewire\SeoSitemapSettings;
use Illuminate\Support\Facades\Route;

// SEO settings (US-SEO-004/005): 2 single-record screens (no create/edit
// variants, mirrors store_info.php), split (modification 20260711T154553) so
// each can be granted as a separate RBAC permission — "Meta & JSON-LD"
// (robots.txt + JSON-LD toggle) vs "Sitemap.xml" (sitemap toggles/rebuild).
//
// WHY flat paths (no 'seo' prefix group): the admin menu's `uri` field
// (`admin::seo_meta` / `admin::seo_sitemap`, DataFrontDefaultSeeder) is
// resolved by gp247_url_render() via its bare "admin::" branch, which
// strips the prefix and appends the REST LITERALLY to GP247_ADMIN_PREFIX
// (see vendor/gp247/core src/Library/Helpers/other.php) — it does not
// consult Laravel's route name/URI at all. The menu path and the route
// path must therefore match literally, or the sidebar link 404s.
Route::get('seo_meta', SeoMetaSettings::class)->name('admin_seo_meta.index');
Route::get('seo_sitemap', SeoSitemapSettings::class)->name('admin_seo_sitemap.index');
