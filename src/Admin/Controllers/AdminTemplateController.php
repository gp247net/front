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
     * The fixed "Customize theme" menu entry: a template picker listing every installed template with what it lets
     * the owner customize. Only the template the working store uses can be opened (through its clickApp(), like the
     * config button); the others are shown disabled so the owner sees their capability without switching the store.
     *
     * @return \Illuminate\Contracts\View\View
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
        $inUse = (string) AdminStore::where('id', $storeId)->value('template');

        return view('gp247-front-admin::template-customize', [
            'title' => gp247_language_render('admin.menu_titles.template_customize'),
            'templates' => $this->customizableTemplates($inUse),
            'configUrl' => gp247_route_admin('admin_template.index'),
            'storeInfoUrl' => gp247_route_admin('admin_store.index'),
        ]);
    }

    /**
     * Installed templates for the picker, the store's template first.
     *
     * WHY clickApp() only for enabled templates: a template registers its admin routes only while it is on, so asking a
     * disabled one would fail; it is shown as "disabled" instead of guessing its capability.
     *
     * @param string $inUse Key of the template the working store uses.
     * @return array<int, array{key: string, title: string, image: string|null, version: string, capability: string, inUse: bool}>
     *
     * @aidlc-unit front-admin
     * @aidlc-story US-FADM-template-customize-entry
     */
    private function customizableTemplates(string $inUse): array
    {
        $installed = gp247_extension_get_installed(type: $this->groupType, active: false)->toArray();
        $templates = [];
        foreach (gp247_extension_get_all_local(type: $this->groupType) as $key => $namespace) {
            if (!array_key_exists($key, $installed) || !class_exists($namespace.'\AppConfig')) {
                continue;
            }
            try {
                $class = $namespace.'\AppConfig';
                $appConfig = new $class;
            } catch (\Throwable $e) {
                gp247_report(msg: 'Template picker: '.$key.' AppConfig failed: '.$e->getMessage(), channel: null);
                continue;
            }
            $enabled = (int) ($installed[$key]['value'] ?? 0) === 1;
            $templates[] = [
                'key' => (string) $key,
                'title' => (string) ($appConfig->title ?? $key),
                'image' => $appConfig->image ?? null,
                'version' => (string) ($appConfig->version ?? ''),
                'capability' => !$enabled ? 'disabled' : (gp247_extension_has_settings($appConfig) ? 'settings' : 'none'),
                'inUse' => (string) $key === $inUse,
            ];
        }
        usort($templates, fn ($a, $b) => $b['inUse'] <=> $a['inUse']);

        return $templates;
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
