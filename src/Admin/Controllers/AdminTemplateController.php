<?php
namespace GP247\Front\Admin\Controllers;

use GP247\Front\Admin\Controllers\RootFrontAdminController;
use GP247\Core\Controllers\ExtensionController;
use GP247\Core\Models\AdminStore;


class AdminTemplateController extends RootFrontAdminController
{
    use ExtensionController;

    public $type = 'Template';
    public $groupType = 'Templates';
    public $listUrlAction = [];

    public function __construct()
    {
        parent::__construct();
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
        // Check template use
        $checkTemplateUse = (new AdminStore)->where('template', $key)->count();
        if ($checkTemplateUse) {
            $msg = gp247_language_render('admin.extension.error_template_use');
            gp247_report(msg:$msg, channel:null);
            return response()->json(['error' => 1, 'msg' => $msg]);
        }
        if ($key == GP247_TEMPLATE_FRONT_DEFAULT) {
            // If template default, can't uninstall
            $msg = gp247_language_render('admin.extension.error_template_use');
            gp247_report(msg:$msg, channel:null);
            return response()->json(['error' => 1, 'msg' => $msg]);
        }
    }

    protected function processDisable(string $key)
    {
        $checkTemplateUse = (new AdminStore)->where('template', $key)->count();
        if ($checkTemplateUse) {
            return response()->json(['error' => 1, 'msg' => gp247_language_render('admin.extension.error_template_use')]);
        }
    }
}
