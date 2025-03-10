<?php

use Illuminate\Support\Facades\Route;

$langUrl = GP247_SEO_LANG ?'{lang?}/' : '';
$suffix = GP247_SUFFIX_URL;

if (file_exists(app_path('GP247/Front/Controllers/HomeController.php'))) {
    $nameSpaceHome = 'App\GP247\Front\Controllers';
} else {
    $nameSpaceHome = 'GP247\Front\Controllers';
}

Route::get($langUrl.'search'.$suffix, $nameSpaceHome.'\HomeController@searchProcessFront')
->name('front.search');

//Process click banner
Route::get('/banner/{id}', $nameSpaceHome.'\HomeController@clickBanner')
->name('front.banner.click');


//Subscribe
Route::post('/subscribe', $nameSpaceHome.'\HomeController@emailSubscribe')
    ->name('front.subscribe');


Route::get('/', $nameSpaceHome.'\HomeController@index')->name('front.home');

Route::get('index.html', function(){
    return redirect()->route('front.home');
});

//Language
Route::get('locale/{code}', function ($code) {
    session(['locale' => $code]);
    if (request()->fullUrl() === redirect()->back()->getTargetUrl()
    ) {
        return redirect()->route('front.home');
    }
    $urlBack = str_replace(url('/' . app()->getLocale()) . '/', url('/' . $code) . '/', back()->getTargetUrl());
    return redirect($urlBack);
})->name('front.locale');


//Currency
Route::get('currency/{code}', function ($code) {
    session(['currency' => $code]);
    if (request()->fullUrl() === redirect()->back()->getTargetUrl()) {
        return redirect()->route('front.home');
    }
    return back();
})->name('front.currency');