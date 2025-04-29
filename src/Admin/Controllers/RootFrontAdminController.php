<?php
namespace GP247\Front\Admin\Controllers;

use GP247\Core\Controllers\RootAdminController;

class RootFrontAdminController extends RootAdminController
{
    public $templatePath;
    public $templateFile;
    public function __construct()
    {
        parent::__construct();
        $this->templatePath = 'templates.' . gp247_store_info('template');
    }
}
