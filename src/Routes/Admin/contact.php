<?php
use GP247\Front\Admin\Livewire\ContactManager;
use Illuminate\Support\Facades\Route;

// Contact form (front-contact): the inbox of visitor messages, and the settings,
// which live as the "Contact" tab of the Configuration hub — the contact_config URL
// only redirects there (same pattern as shop_config), but keeps its own permission.
Route::get('contact', ContactManager::class)->name('admin_contact.index');
Route::get('contact_config', function () {
    return redirect()->route('admin_config.index');
})->name('admin_contact_config.index');
