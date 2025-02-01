<?php
if (file_exists(app_path('GP247/Front/Controllers/HomeController.php'))) {
    $nameSpaceHome = 'App\GP247\Front\Controllers';
} else {
    $nameSpaceHome = 'GP247\Front\Controllers';
}
$suffix = GP247_SUFFIX_URL;
$langUrl = GP247_SEO_LANG ?'{lang?}/' : '';