<?php
namespace GP247\Front\Admin\Controllers;

use GP247\Front\Admin\Controllers\RootFrontAdminController;
use GP247\Core\Controllers\ExtensionOnlineController;
class AdminTemplateOnlineController extends RootFrontAdminController
{
    use ExtensionOnlineController;
    public $type = 'Template';
    public $groupType = 'Templates';
    public $urlOnline = '';
    public $listUrlAction = [];

    public function __construct()
    {
        parent::__construct();
        $this->urlOnline = gp247_route_admin('admin_template_online');
        $this->listUrlAction = $this->listUrlAction();
    }

    protected function listUrlAction()
    {
        return [
            'install' => gp247_route_admin('admin_template.install'),
            'uninstall' => gp247_route_admin('admin_template.uninstall'),
            'enable' => gp247_route_admin('admin_template.enable'),
            'disable' => gp247_route_admin('admin_template.disable'),
            'urlOnline' => gp247_route_admin('admin_template_online.index'),
            'urlImport' => gp247_route_admin('admin_template.import'),
        ];
    }

    protected function processUninstall(string $key)
    {
        //
    }

    protected function processDisable(string $key)
    {
        //
    }
}
