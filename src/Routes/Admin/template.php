<?php

use GP247\Front\Admin\Controllers\AdminTemplateController;
use GP247\Front\Admin\Controllers\AdminTemplateOnlineController;
use Illuminate\Support\Facades\Route;

// Template
$templateController = gp247_namespace(AdminTemplateController::class);
Route::group(['prefix' => 'template'], function () use ($templateController) {
    //Process import
    Route::get('/import', $templateController.'@importExtension')
        ->name('admin_template.import');
    Route::post('/import', $templateController.'@processImport')
        ->name('admin_template.process_import');
    //End process

    Route::get('/', $templateController.'@index')->name('admin_template.index');
    Route::post('install', $templateController.'@install')->name('admin_template.install');
    Route::post('uninstall', $templateController.'@uninstall')->name('admin_template.uninstall');
    Route::post('enable', $templateController.'@enable')->name('admin_template.enable');
    Route::post('disable', $templateController.'@disable')->name('admin_template.disable');

    if (config('gp247-config.admin.api_templates')) {
        $templateOnlineController = gp247_namespace(AdminTemplateOnlineController::class);
        Route::get('/online', $templateOnlineController.'@index')->name('admin_template_online.index');
        Route::post('/online/install', $templateOnlineController.'@install')
        ->name('admin_template_online.install');
    }
});
