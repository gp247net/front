<?php
use GP247\Front\Admin\Livewire\TrackingSettings;
use Illuminate\Support\Facades\Route;

// "Tracking code & cookies" (tracking-consent): administrators only — the component checks
// it on mount and on every action (core's administratorOnly() list is fixed, so the path
// cannot be added there from front; ADR tracking-consent_config-storage-and-authority §4).
// Path matches the menu uri admin::tracking literally (see seo.php for why).
Route::get('tracking', gp247_namespace(TrackingSettings::class))->name('admin_tracking.index');
