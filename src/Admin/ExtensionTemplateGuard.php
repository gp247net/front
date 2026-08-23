<?php

namespace GP247\Front\Admin;

use GP247\Core\Models\AdminStore;

/**
 * Guard that blocks disabling/uninstalling a storefront template that is still
 * in use by a store, or that is the configured default template.
 *
 * Registered at runtime into config('gp247-config.admin.extension.guards') by
 * FrontServiceProvider so the core ExtensionInstaller enforces it for both the
 * admin UI and the CLI — without core depending on front (NFR-MAINT-001).
 *
 * @aidlc-unit plugin-manager
 * @aidlc-story US-CLI-002
 * @aidlc-adr system-cli_service-extraction
 */
class ExtensionTemplateGuard
{
    /**
     * Decide whether a template lifecycle operation must be blocked.
     *
     * @param string $groupType Extension group (only 'Templates' is guarded).
     * @param string $key       Template key.
     * @param string $op        Operation: 'uninstall' | 'disable'.
     * @return string|null Localized block reason, or null when allowed.
     */
    public static function check(string $groupType, string $key, string $op): ?string
    {
        if ($groupType !== 'Templates') {
            return null;
        }

        if (in_array($op, ['uninstall', 'disable'], true)) {
            if ((new AdminStore)->where('template', $key)->count()) {
                return gp247_language_render('admin.extension.error_template_use');
            }
        }

        // The default template can never be uninstalled even if no store row
        // currently points at it (it is the fallback for the root store).
        if ($op === 'uninstall' && defined('GP247_TEMPLATE_FRONT_DEFAULT') && $key === GP247_TEMPLATE_FRONT_DEFAULT) {
            return gp247_language_render('admin.extension.error_template_use');
        }

        return null;
    }
}
