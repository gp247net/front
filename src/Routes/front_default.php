<?php

use Illuminate\Support\Facades\Route;

$langUrl = GP247_SEO_LANG ?'{lang?}/' : '';
$suffix = GP247_SUFFIX_URL;

// SEO: sitemap.xml and robots.txt — registered before the catch-all {alias}.html
// route so they are never swallowed by the page-detail handler (US-SEO-004).
$nameSpaceSeo = class_exists('App\GP247\Front\Controllers\SeoController')
    ? 'App\GP247\Front\Controllers'
    : 'GP247\Front\Controllers';

Route::get('/sitemap.xml', $nameSpaceSeo . '\SeoController@sitemap')->name('front.sitemap');
Route::get('/robots.txt',  $nameSpaceSeo . '\SeoController@robots')->name('front.robots');

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
    return redirect(gp247_route_front('front.home'));
});

//Language
Route::get('locale/{code}', function ($code) {
    session(['locale' => $code]);
    if (request()->fullUrl() === redirect()->back()->getTargetUrl()
    ) {
        return redirect(gp247_route_front('front.home'));
    }
    $urlBack = str_replace(url('/' . app()->getLocale()) . '/', url('/' . $code) . '/', back()->getTargetUrl());
    return redirect($urlBack);
})->name('front.locale');


//Currency
Route::get('currency/{code}', function ($code) {
    session(['currency' => $code]);
    if (request()->fullUrl() === redirect()->back()->getTargetUrl()) {
        return redirect(gp247_route_front('front.home'));
    }
    return back();
})->name('front.currency');