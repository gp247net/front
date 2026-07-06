<?php

use GP247\Front\Controllers\HomeController;
use GP247\Front\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

$langUrl = GP247_SEO_LANG ?'{lang?}/' : '';
$suffix = GP247_SUFFIX_URL;

// SEO: sitemap.xml and robots.txt — registered before the catch-all {alias}.html
// route so they are never swallowed by the page-detail handler (US-SEO-004).
$seoController = gp247_namespace(SeoController::class);

Route::get('/sitemap.xml', $seoController . '@sitemap')->name('front.sitemap');
Route::get('/robots.txt',  $seoController . '@robots')->name('front.robots');

$homeController = gp247_namespace(HomeController::class);

Route::get($langUrl.'search'.$suffix, $homeController.'@searchProcessFront')
->name('front.search');

//Process click banner
Route::get('/banner/{id}', $homeController.'@clickBanner')
->name('front.banner.click');


//Subscribe
Route::post('/subscribe', $homeController.'@emailSubscribe')
    ->name('front.subscribe');


Route::get('/', $homeController.'@index')->name('front.home');

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
