<?php
namespace GP247\Front\Admin\Controllers;

use GP247\Core\Controllers\RootAdminController;
use GP247\Core\Controllers\ExtensionController;
use GP247\Core\Models\AdminStore;


class AdminTemplateController extends RootAdminController
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
            'applyData' => gp247_route_admin('admin_template.apply_data'),
            // WHY: the TailAdmin-ported extension_upload view links back to the
            // local list via listUrlAction['urlLocal']; omitting it 500s the import tab.
            'urlLocal' => gp247_route_admin('admin_template.index'),
            'urlOnline' => gp247_route_admin('admin_template_online.index'),
            'urlImport' => gp247_route_admin('admin_template.import'),
        ];
    }

    /**
     * The fixed "Customize theme" menu entry: open the settings page of the template the working store uses, or go
     * back to the Template manager with a notice when that template has none (not installed, disabled, no clickApp,
     * or clickApp failing — the shared core helper decides).
     *
     * @return mixed What the template's clickApp() returns, or a redirect to the Template manager.
     *
     * @aidlc-unit front-admin
     * @aidlc-story US-FADM-template-customize-entry
     * @aidlc-adr front-admin_template-settings-entry
     */
    public function customize()
    {
        // WHY session('adminStoreId'): the store this admin works on — ROOT, or the store a store-admin is bound to
        // (same value ResourcePanel and the template's own settings screen scope to).
        $storeId = session('adminStoreId') ?: GP247_STORE_ID_ROOT;
        $key = (string) AdminStore::where('id', $storeId)->value('template');
        $class = $key !== '' ? gp247_extension_get_namespace(type: $this->groupType, key: $key).'\AppConfig' : null;

        if ($class && class_exists($class) && gp247_extension_check_active($this->groupType, $key)) {
            $appConfig = new $class;
            if (gp247_extension_has_settings($appConfig)) {
                return $appConfig->clickApp();
            }
        }

        return redirect(gp247_route_admin('admin_template.index'))
            ->with('warning', gp247_language_render('admin.template.no_customize', ['name' => $key ?: '—']));
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
