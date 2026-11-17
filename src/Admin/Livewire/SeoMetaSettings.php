<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\GP247AdminComponent;
use GP247\Core\Models\AdminConfig;
use GP247\Front\Controllers\SeoController;
use GP247\Front\Library\SeoContentMeta;
use GP247\Front\Library\SeoMeta;
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
 * `seo.jsonld_enabled`). Gated by `admin_seo_meta`. Also holds the Google /
 * Bing site-verification tokens (`seo.verify_google`, `seo.verify_bing`,
 * modification 20261116T050835).
 *
 * @aidlc-unit seo
 * Also holds the title templates per content type (`seo.title_template.<type>`,
 * SEO phase 1, modification 20261117T140612).
 *
 * @aidlc-story US-SEO-004, US-SEO-005, US-SEO-site-verification, US-SEO-title-template
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


    /** @var string Current robots.txt body (bound to the textarea). */
    public string $robotsTxt = '';

    /** @var bool Master on/off switch for all JSON-LD output (Organization + any @push('jsonld')). */
    public bool $jsonldEnabled = true;

    /** @var string Google Search Console verification token (US-SEO-site-verification). */
    public string $verifyGoogle = '';

    /** @var string Bing Webmaster verification token (US-SEO-site-verification). */
    public string $verifyBing = '';

    /**
     * Title template per registered content type + `default` (US-SEO-title-template).
     *
     * @var array<string, string>
     */
    public array $titleTemplates = [];

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

        // WHY reuse SeoController::defaultRobots(): the editor prefill must match
        // exactly what SeoController::robots() serves when nothing is saved yet —
        // including the configurable admin prefix (default `gp247_admin`), so a
        // single source of truth prevents the two from drifting.
        $this->robotsTxt = (string) gp247_config(self::CONFIG_ROBOTS, $storeId, SeoController::defaultRobots());
        $this->jsonldEnabled = gp247_config(self::CONFIG_JSONLD_ENABLED, $storeId, '1') != '0';
        $this->verifyGoogle = (string) gp247_config(SeoMeta::VERIFICATION_KEYS['google'], $storeId, '');
        $this->verifyBing = (string) gp247_config(SeoMeta::VERIFICATION_KEYS['bing'], $storeId, '');
        foreach ($this->titleTemplateTypes() as $type => $labelKey) {
            $this->titleTemplates[$type] = SeoContentMeta::titleTemplate($type, $storeId);
        }
    }

    /**
     * Content types offered a title template: every registered type, then `default`.
     *
     * @return array<string, string> Type => language key of its label.
     *
     * @aidlc-story US-SEO-title-template
     */
    public function titleTemplateTypes(): array
    {
        $types = [];
        foreach (SeoContentMeta::types() as $type => $definition) {
            $types[$type] = (string) ($definition['label'] ?? $type);
        }
        $types[SeoContentMeta::TITLE_TEMPLATE_DEFAULT] = 'admin.seo.title_template_default';

        return $types;
    }

    /**
     * Persist one title template on blur (Layer-2 gated). Refused unless empty or
     * containing `{title}` within TITLE_TEMPLATE_MAX characters.
     *
     * @param mixed  $value Typed template.
     * @param string $type  Changed key of $titleTemplates.
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     *
     * @aidlc-story US-SEO-title-template
     */
    public function updatedTitleTemplates($value, $type): void
    {
        $this->authorizeAction('update');

        $type = (string) $type;
        $property = 'titleTemplates.' . $type;
        if (!array_key_exists($type, $this->titleTemplateTypes())) {
            unset($this->titleTemplates[$type]);

            return;
        }
        if (!is_string($value) && $value !== null) {
            $this->titleTemplates[$type] = SeoContentMeta::titleTemplate($type, $this->storeId());

            return;
        }
        $template = gp247_text_plain((string) $value, stripTags: true);
        if (!SeoContentMeta::isValidTitleTemplate($template)) {
            $this->addError($property, gp247_language_render('admin.seo.title_template_invalid'));
            $this->notify('error', gp247_language_render('admin.seo.title_template_invalid'));

            return;
        }

        $this->resetErrorBag($property);
        $this->titleTemplates[$type] = $template;
        $this->upsertConfig(SeoContentMeta::TITLE_TEMPLATE_KEY . $type, $template);
        $this->notify('success', gp247_language_render('admin.setting_saved'));
    }

    /**
     * Persist the Google verification token on blur (Layer-2 gated).
     *
     * @param mixed $value Typed or pasted input (a bare token or a whole <meta> tag).
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     *
     * @aidlc-story US-SEO-site-verification
     */
    public function updatedVerifyGoogle($value): void
    {
        $this->saveVerification('verifyGoogle', 'google', (string) $value);
    }

    /**
     * Persist the Bing verification token on blur (Layer-2 gated).
     *
     * @param mixed $value Typed or pasted input (a bare token or a whole <meta> tag).
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     *
     * @aidlc-story US-SEO-site-verification
     */
    public function updatedVerifyBing($value): void
    {
        $this->saveVerification('verifyBing', 'bing', (string) $value);
    }

    /**
     * Normalize, validate and store one verification token. Only a plain token is
     * ever stored — never the pasted HTML — so the storefront prints nothing but
     * `[A-Za-z0-9_-]`. An empty value clears the code.
     *
     * @param string $property Component property bound to the input.
     * @param string $engine   Key of SeoMeta::VERIFICATION_KEYS.
     * @param string $raw      Input as received.
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     *
     * @aidlc-story US-SEO-site-verification
     */
    private function saveVerification(string $property, string $engine, string $raw): void
    {
        $this->authorizeAction('update');

        $token = SeoMeta::normalizeVerificationInput($raw);
        if ($token !== '' && !SeoMeta::isVerificationToken($token)) {
            $this->addError($property, gp247_language_render('admin.seo.verify_invalid'));
            $this->notify('error', gp247_language_render('admin.seo.verify_invalid'));

            return;
        }

        $this->resetErrorBag($property);
        $this->{$property} = $token;
        $this->upsertConfig(SeoMeta::VERIFICATION_KEYS[$engine], $token);
        $this->notify('success', gp247_language_render('admin.setting_saved'));
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

        $clean = gp247_text_plain((string) $value, stripTags: true);

        if (mb_strlen($clean) > self::CONFIG_VALUE_MAX_LENGTH) {
            $this->notify('error', gp247_language_render('admin.seo.robots_txt_too_long'));

            return;
        }

        $this->upsertConfig(self::CONFIG_ROBOTS, $clean);
        $this->notify('success', gp247_language_render('admin.setting_saved'));
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
        $this->notify('success', gp247_language_render('admin.setting_saved'));
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
