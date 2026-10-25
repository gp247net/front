<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\ConfigForm;
use GP247\Core\AdminShell\Infrastructure\ReadinessCheck;
use GP247\Front\Support\ContactSettings;

/**
 * "Contact" tab of the Configuration hub (front-contact): switch the contact form
 * on/off and choose who gets mail. Base tier = ROOT store, so a single-store site
 * edits exactly what the storefront reads; with multi-store a store-admin overrides
 * their own store (ContactSettings falls back to ROOT). Authorized against its own
 * URI even when embedded in the hub (ADR admin-shell_config-hub-tab-registry).
 *
 * @aidlc-unit front-contact
 * @aidlc-story US-front-contact-config-tab
 * @aidlc-adr front-contact_contact-form-in-front, admin-shell_config-hub-tab-registry
 */
class ContactConfigForm extends ConfigForm
{
    protected ?string $permission = 'admin_contact_config';

    /**
     * Always authorize against this tab's own URI — also on Livewire update requests
     * and when embedded in the hub — with the site's configured admin prefix.
     * WHY not a $screenUri set in mount(): protected properties are not kept between
     * Livewire requests, so save() would fall back to a hard-coded prefix.
     *
     * @return string|null
     */
    protected function authScreenUri(): ?string
    {
        return (defined('GP247_ADMIN_PREFIX') ? GP247_ADMIN_PREFIX : 'gp247_admin') . '/contact_config';
    }

    /**
     * @return string
     */
    protected function group(): string
    {
        return ContactSettings::GROUP;
    }

    /**
     * @return string
     */
    protected function heading(): string
    {
        return gp247_language_render('admin.contact.config.heading');
    }

    /**
     * @return int|string The ROOT store holds the shared values.
     */
    protected function storeId()
    {
        return defined('GP247_STORE_ID_ROOT') ? GP247_STORE_ID_ROOT : 1;
    }

    /**
     * @return bool Per-store overrides when multi-store is installed.
     */
    protected function storeScoped(): bool
    {
        return true;
    }

    /**
     * @return array<int, string>
     */
    protected function keys(): array
    {
        return array_keys(ContactSettings::DEFAULTS);
    }

    /**
     * @return array<string, string>
     */
    protected function fieldTypes(): array
    {
        return array_fill_keys($this->keys(), 'bool');
    }

    /**
     * What the form needs to deliver mail: system mail on, and a store address.
     *
     * @return array<int, ReadinessCheck>
     */
    protected function readiness(): array
    {
        $store = $this->readinessStoreId();
        $mailOn = (bool) gp247_config('email_action_mode', $store);
        $storeEmail = trim((string) gp247_store_info('email', '', $store));

        return [
            ReadinessCheck::failIf(
                !$mailOn,
                'system_mail',
                gp247_language_render('admin.contact.readiness_system_mail'),
                ReadinessCheck::WARNING,
                gp247_route_admin('admin_config.index'),
                gp247_language_render('admin.contact.readiness_fix_mail'),
                gp247_language_render('admin.contact.readiness_system_mail_ok'),
            ),
            ReadinessCheck::failIf(
                $storeEmail === '',
                'store_email',
                gp247_language_render('admin.contact.readiness_store_email'),
                ReadinessCheck::WARNING,
                gp247_route_admin('admin_store.index'),
                gp247_language_render('admin.contact.readiness_fix_store'),
                gp247_language_render('admin.contact.readiness_store_email_ok'),
            ),
        ];
    }
}
