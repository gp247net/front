<?php
/**
 * Front define
 */
// check if not exist, define it

if (!defined('GP247_FRONT_MIDDLEWARE')) {
    define('GP247_FRONT_MIDDLEWARE', ['web', 'front']);
}
if (!defined('GP247_SEO_LANG')) {
    define('GP247_SEO_LANG', config('gp247-config.front.route.GP247_SEO_LANG'));
}
