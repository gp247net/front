<?php
use Illuminate\Support\Facades\Route;

Route::group(
    [
        'middleware' => GP247_API_MIDDLEWARE,
        'prefix' => 'api',
    ],
    function () {
        Route::group([
        ], function () {
            //
        });
    }
);
