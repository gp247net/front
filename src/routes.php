<?php

use Illuminate\Support\Facades\Route;

$langUrl = GP247_SEO_LANG ?'{lang?}/' : '';
$suffix = GP247_SUFFIX_URL;
Route::group(
    [
        'middleware' => GP247_FRONT_MIDDLEWARE,
    ],
    function () use($langUrl, $suffix){
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

        if (file_exists($filename = __DIR__ . '/Routes/front.php')) {
            $this->loadRoutesFrom($filename);
        }

  
        //--Please keep 2 lines route (pages + pageNotFound) at the bottom
        Route::get($langUrl.'{alias}'.$suffix, $nameSpaceHome.'\HomeController@pageDetailProcessFront')->name('front.page.detail');
        //=======End Front

    }
);


// Admin routes
Route::group(
    [
        'prefix' => GP247_ADMIN_PREFIX,
        'middleware' => GP247_ADMIN_MIDDLEWARE,
    ],
    function () {
        if (file_exists($filename = __DIR__ . '/Routes/admin.php')) {
            $this->loadRoutesFrom($filename);
        }
    }
);
