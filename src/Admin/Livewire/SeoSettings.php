<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\GP247AdminComponent;
use GP247\Core\Models\AdminConfig;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

/**
 * SEO settings screen — single-record admin screen (ADR-005 settings-screen
 * pattern, mirrors `WebsiteInfo`/`CustomConfigForm`): editable `robots.txt`
 * content, sitemap inclusion toggles for products/categories, a wildcard alias
 * exclusion list, and a manual "rebuild sitemap" action. Fills the previously-
 * unmet acceptance criteria of US-SEO-004 (`SeoController` could only *read*
 * `seo.robots_txt`, never write it; sitemap only refreshed via the 6h cache
 * TTL with no manual trigger; no way to exclude individual URLs).
 *
 * Values persist to `admin_config` (group `seo`) the same key names
 * `SeoController` already reads (`seo.robots_txt`,
 * `seo.sitemap_include_products`, `seo.sitemap_include_categories`,
 * `seo.sitemap_exclude_aliases`). Gated by `admin_seo`.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-004
 */
class SeoSettings extends GP247AdminComponent
{
    protected ?string $permission = 'admin_seo';

    /** admin_config key for the robots.txt body. */
    private const CONFIG_ROBOTS = 'seo.robots_txt';

    /** admin_config key for the "include products in sitemap" toggle. */
    private const CONFIG_INCLUDE_PRODUCTS = 'seo.sitemap_include_products';

    /** admin_config key for the "include categories in sitemap" toggle. */
    private const CONFIG_INCLUDE_CATEGORIES = 'seo.sitemap_include_categories';

    /** admin_config key for the sitemap alias exclusion pattern list. */
    private const CONFIG_EXCLUDE_ALIASES = 'seo.sitemap_exclude_aliases';

    /** admin_config "code" grouping this screen's rows (mirrors CustomConfigForm::CODE). */
    private const CODE = 'seo_settings';

    /** `admin_config.value` is VARCHAR(500) — shared by every config key in the system. */
    private const CONFIG_VALUE_MAX_LENGTH = 500;

    /** Same default `SeoController::robots()` falls back to when no custom value is saved. */
    private const DEFAULT_ROBOTS = "User-agent: *\nDisallow: /admin/\nDisallow: /gp247-admin/\n";

    /** @var string Current robots.txt body (bound to the textarea). */
    public string $robotsTxt = '';

    /** @var bool Whether shop products are included in sitemap.xml. */
    public bool $includeProducts = true;

    /** @var bool Whether shop categories are included in sitemap.xml. */
    public bool $includeCategories = true;

    /** @var string Wildcard alias exclusion patterns, one per line. */
    public string $excludeAliases = '';

    /**
     * The store this screen edits. WHY: unlike `WebsiteInfo`/`CustomConfigForm`
     * (always `GP247_STORE_ID_ROOT`, a single canonical "root store info"
     * record), this screen must key off the exact same store id
     * `SeoController::sitemap()`/`robots()` resolve at request time
     * (`config('app.storeId')`) — otherwise a saved robots.txt or a sitemap
     * rebuild would silently target the wrong store's cache/config row and
     * never reach the visitor.
     *
     * @return mixed Store UUID.
     */
    private function storeId()
    {
        return config('app.storeId');
    }

    /**
     * Load current config values (falling back to the same defaults
     * `SeoController` itself uses when nothing has been saved yet).
     *
     * @return void
     */
    public function mount(): void
    {
        parent::mount();

        $storeId = $this->storeId();

        $this->robotsTxt = (string) gp247_config(self::CONFIG_ROBOTS, $storeId, self::DEFAULT_ROBOTS);
        $this->includeProducts = gp247_config(self::CONFIG_INCLUDE_PRODUCTS, $storeId, '1') != '0';
        $this->includeCategories = gp247_config(self::CONFIG_INCLUDE_CATEGORIES, $storeId, '1') != '0';
        $this->excludeAliases = (string) gp247_config(self::CONFIG_EXCLUDE_ALIASES, $storeId, '');
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
     * Persist the alias exclusion pattern list on blur (Layer-2 gated). Each
     * line is a wildcard pattern (`*`/`?`, matched via `fnmatch()` by
     * `SeoController`) applied to page/product/category aliases. Rejected (not
     * saved) when it exceeds the shared `admin_config.value` column length —
     * same handling as `updatedRobotsTxt()`.
     *
     * @param mixed $value
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function updatedExcludeAliases($value): void
    {
        $this->authorizeAction('update');

        $clean = gp247_clean((string) $value, hight: true);

        if (mb_strlen($clean) > self::CONFIG_VALUE_MAX_LENGTH) {
            $this->notify('error', gp247_language_render('admin.seo.exclude_aliases_too_long'));

            return;
        }

        $this->upsertConfig(self::CONFIG_EXCLUDE_ALIASES, $clean);
        $this->notify('success', gp247_language_render('admin.core.setting_saved'));
    }

    /**
     * Persist the "include products" toggle (Layer-2 gated).
     *
     * @param mixed $value
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function updatedIncludeProducts($value): void
    {
        $this->authorizeAction('update');

        $this->upsertConfig(self::CONFIG_INCLUDE_PRODUCTS, $value ? '1' : '0');
        $this->notify('success', gp247_language_render('admin.core.setting_saved'));
    }

    /**
     * Persist the "include categories" toggle (Layer-2 gated).
     *
     * @param mixed $value
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function updatedIncludeCategories($value): void
    {
        $this->authorizeAction('update');

        $this->upsertConfig(self::CONFIG_INCLUDE_CATEGORIES, $value ? '1' : '0');
        $this->notify('success', gp247_language_render('admin.core.setting_saved'));
    }

    /**
     * Force the sitemap cache to rebuild on the next visit (Layer-2 gated).
     *
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function rebuildSitemap(): void
    {
        $this->authorizeAction('update');

        Cache::forget('gp247_sitemap_' . $this->storeId());
        $this->notify('success', gp247_language_render('admin.seo.sitemap_rebuilt'));
    }

    /**
     * @return View
     */
    public function render(): View
    {
        return view('gp247-front-admin::seo-settings', [
            'robotsMaxLength' => self::CONFIG_VALUE_MAX_LENGTH,
            'excludeAliasesMaxLength' => self::CONFIG_VALUE_MAX_LENGTH,
        ])->layout('gp247-admin::layouts.admin', ['title' => gp247_language_render('admin.seo.title')]);
    }
}
