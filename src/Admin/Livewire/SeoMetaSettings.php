<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\GP247AdminComponent;
use GP247\Core\Models\AdminConfig;
use Illuminate\Contracts\View\View;

/**
 * "Meta & JSON-LD" admin screen — single-record settings screen (ADR-005
 * pattern, mirrors `WebsiteInfo`/`CustomConfigForm`): editable `robots.txt`
 * content and the master JSON-LD on/off toggle. Split out of the former
 * combined `SeoSettings` screen (modification 20260711T154553) so robots/
 * JSON-LD editing and sitemap management (`SeoSitemapSettings`) can be
 * granted as separate RBAC permissions.
 *
 * Values persist to `admin_config` (group `seo`) the same key names
 * `SeoController`/`SeoMeta` already read (`seo.robots_txt`,
 * `seo.jsonld_enabled`). Gated by `admin_seo_meta`.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-004, US-SEO-005
 */
class SeoMetaSettings extends GP247AdminComponent
{
    protected ?string $permission = 'admin_seo_meta';

    /** admin_config key for the robots.txt body. */
    private const CONFIG_ROBOTS = 'seo.robots_txt';

    /** admin_config key for the master JSON-LD on/off toggle (SeoMeta::jsonldEnabled()). */
    private const CONFIG_JSONLD_ENABLED = 'seo.jsonld_enabled';

    /** admin_config "code" grouping this screen's rows (mirrors CustomConfigForm::CODE). */
    private const CODE = 'seo_meta_settings';

    /** `admin_config.value` is VARCHAR(500) — shared by every config key in the system. */
    private const CONFIG_VALUE_MAX_LENGTH = 500;

    /** Same default `SeoController::robots()` falls back to when no custom value is saved. */
    private const DEFAULT_ROBOTS = "User-agent: *\nDisallow: /admin/\nDisallow: /gp247-admin/\n";

    /** @var string Current robots.txt body (bound to the textarea). */
    public string $robotsTxt = '';

    /** @var bool Master on/off switch for all JSON-LD output (Organization + any @push('jsonld')). */
    public bool $jsonldEnabled = true;

    /**
     * The store this screen edits. WHY: unlike `WebsiteInfo`/`CustomConfigForm`
     * (always `GP247_STORE_ID_ROOT`, a single canonical "root store info"
     * record), this screen must key off the exact same store id
     * `SeoController::robots()` resolves at request time
     * (`config('app.storeId')`) — otherwise a saved robots.txt would silently
     * target the wrong store's config row and never reach the visitor.
     *
     * @return mixed Store UUID.
     */
    private function storeId()
    {
        return config('app.storeId');
    }

    /**
     * Load current config values (falling back to the same defaults
     * `SeoController`/`SeoMeta` themselves use when nothing has been saved yet).
     *
     * @return void
     */
    public function mount(): void
    {
        parent::mount();

        $storeId = $this->storeId();

        $this->robotsTxt = (string) gp247_config(self::CONFIG_ROBOTS, $storeId, self::DEFAULT_ROBOTS);
        $this->jsonldEnabled = gp247_config(self::CONFIG_JSONLD_ENABLED, $storeId, '1') != '0';
    }

    /**
     * Insert-or-update a single `admin_config` row for the current store.
     *
     * @param string $key   Config key.
     * @param string $value Config value.
     * @return void
     */
    private function upsertConfig(string $key, string $value): void
    {
        AdminConfig::updateOrCreate(
            ['key' => $key, 'store_id' => $this->storeId()],
            ['value' => $value, 'group' => 'seo', 'code' => self::CODE, 'sort' => 0]
        );
    }

    /**
     * Persist the robots.txt body on blur (Layer-2 gated). Rejected (not saved)
     * when it exceeds the shared `admin_config.value` column length.
     *
     * @param mixed $value
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function updatedRobotsTxt($value): void
    {
        $this->authorizeAction('update');

        $clean = gp247_clean((string) $value, hight: true);

        if (mb_strlen($clean) > self::CONFIG_VALUE_MAX_LENGTH) {
            $this->notify('error', gp247_language_render('admin.seo.robots_txt_too_long'));

            return;
        }

        $this->upsertConfig(self::CONFIG_ROBOTS, $clean);
        $this->notify('success', gp247_language_render('admin.core.setting_saved'));
    }

    /**
     * Persist the master JSON-LD on/off toggle (Layer-2 gated). Backs
     * `SeoMeta::jsonldEnabled()`, which gates Organization JSON-LD in
     * `MetaHead` and the entire `@stack('jsonld')` in `layout.blade.php`.
     *
     * @param mixed $value
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function updatedJsonldEnabled($value): void
    {
        $this->authorizeAction('update');

        $this->upsertConfig(self::CONFIG_JSONLD_ENABLED, $value ? '1' : '0');
        $this->notify('success', gp247_language_render('admin.core.setting_saved'));
    }

    /**
     * @return View
     */
    public function render(): View
    {
        return view('gp247-front-admin::seo-meta-settings', [
            'robotsMaxLength' => self::CONFIG_VALUE_MAX_LENGTH,
        ])->layout('gp247-admin::layouts.admin', ['title' => gp247_language_render('admin.seo.meta_title')]);
    }
}
