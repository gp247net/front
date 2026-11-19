<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Domain\AuthorizationException;
use GP247\Core\AdminShell\Infrastructure\GP247AdminComponent;
use GP247\Core\AdminShell\Support\AdminAlertService;
use GP247\Core\Models\AdminStore;
use GP247\Front\Tracking\Application\PolicyPages;
use GP247\Front\Tracking\Application\SnippetService;
use GP247\Front\Tracking\Application\TrackingSettingsService;
use GP247\Front\Tracking\Domain\CodeSnippet;
use GP247\Front\Tracking\Domain\ConsentCategory;
use GP247\Front\Tracking\Domain\InvalidTrackingSetting;
use GP247\Front\Tracking\Domain\SnippetRegion;
use GP247\Front\Tracking\Domain\TrackingSettings as Settings;
use Illuminate\Contracts\View\View;

/**
 * "Tracking code & cookies" screen: GA4 / GTM / Meta Pixel ids, the switch that turns
 * every tracking code off, and custom code blocks with their before / after history.
 * Per store, inheriting the ROOT store (Q13). Administrators only (Q3): checked on mount,
 * on render and at the top of every action, because core's administrator-only path list
 * cannot be extended from front (ADR tracking-consent_config-storage-and-authority §4).
 * Actions are explicit method calls (no live property writes) so the operation log of
 * the admin shell records every save.
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-settings-screen, US-tracking-consent-preset-services, US-tracking-consent-custom-code, US-tracking-consent-banner, US-tracking-consent-consent-gate
 * @aidlc-adr tracking-consent_config-storage-and-authority
 */
class TrackingSettings extends GP247AdminComponent
{
    /** Validation field => form key. */
    private const ERROR_FIELDS = [
        'ga4' => 'tracking_ga4_id',
        'gtm' => 'tracking_gtm_id',
        'pixel' => 'tracking_pixel_id',
        'gtm_category' => 'tracking_gtm_category',
    ];

    /** Store being edited (ROOT unless a multi-store site picked another). */
    public string $storeId = '';

    /** @var array<string, string> FORM_KEYS => value */
    public array $form = [];

    /** @var array<string, string> Values as loaded, to save only what changed at a sub-store. */
    public array $original = [];

    /** @var array<string, bool> key => shown value comes from ROOT */
    public array $inherited = [];

    public bool $enabled = true;

    public bool $snippetOwn = false;

    /** @var array<int, array<string, mixed>> Custom code blocks being edited. */
    public array $snippets = [];

    /** @var array{mode?: string, page?: string, text?: array<string, string>} Cookie banner form. */
    public array $banner = [];

    /** @var array<string, string> Banner values as loaded (BANNER_KEYS => stored form). */
    public array $bannerOriginal = [];

    /** @var array<string, bool> BANNER_KEYS => shown value comes from ROOT */
    public array $bannerInherited = [];

    /**
     * @return void
     * @throws AuthorizationException When the signed-in admin is not an administrator.
     */
    public function mount(): void
    {
        $this->authorizeAdministrator();
        parent::mount();
        $this->storeId = TrackingSettingsService::rootId();
        $this->load();
    }

    /**
     * Livewire hook: the picked store comes from the browser — accept only a real store.
     *
     * @return void
     */
    public function updatedStoreId(): void
    {
        $this->authorizeAdministrator();
        if (!array_key_exists($this->storeId, $this->storeOptions())) {
            $this->storeId = TrackingSettingsService::rootId();
        }
        $this->resetErrorBag();
        $this->load();
    }

    /**
     * Store the ids and the GTM group.
     *
     * @return void
     */
    public function save(): void
    {
        $this->authorizeAdministrator();
        $this->resetErrorBag();
        // WHY: public properties arrive from the browser — keep only the form's keys, as text.
        $form = [];
        foreach (TrackingSettingsService::FORM_KEYS as $key) {
            $value = $this->form[$key] ?? '';
            $form[$key] = is_scalar($value) ? (string) $value : '';
        }
        $changed = array_keys(array_diff_assoc($form, $this->original));
        try {
            TrackingSettingsService::saveForm($this->currentStore(), $form, $changed);
        } catch (InvalidTrackingSetting $e) {
            foreach ($e->errors() as $field => $code) {
                $this->addError('form.' . (self::ERROR_FIELDS[$field] ?? $field), gp247_language_render($code));
            }

            return;
        }
        $this->loadSettings();
        $this->notify('success', gp247_language_render('admin.setting_saved'));
    }

    /**
     * Store the banner mode, the policy page and the banner texts.
     *
     * @return void
     */
    public function saveBanner(): void
    {
        $this->authorizeAdministrator();
        $this->resetErrorBag();
        $input = $this->bannerInput();
        $stored = [
            'tracking_banner_mode' => $input['mode'],
            'tracking_policy_page' => $input['page'],
            'tracking_banner_text' => Settings::encodeBannerText($input['text']),
        ];
        $changed = array_keys(array_diff_assoc($stored, $this->bannerOriginal));
        try {
            $pages = array_keys(PolicyPages::options($this->currentStore()));
            // WHY: an unchanged page is not checked again — a page deleted later must not
            // stop the owner from changing the mode or the text.
            if ($input['page'] === ($this->bannerOriginal['tracking_policy_page'] ?? '')) {
                $pages[] = $input['page'];
            }
            TrackingSettingsService::saveBanner($this->currentStore(), $input, $changed, $pages);
        } catch (InvalidTrackingSetting $e) {
            foreach ($e->errors() as $field => $code) {
                $this->addError('banner.' . $field, gp247_language_render($code));
            }

            return;
        }
        $this->loadSettings();
        $this->notify('success', gp247_language_render('admin.setting_saved'));
    }

    /**
     * Raise the cookie policy version so every visitor sees the banner again (Q11).
     *
     * @return void
     */
    public function askAgain(): void
    {
        $this->authorizeAdministrator();
        TrackingSettingsService::askAgain($this->currentStore());
        $this->loadSettings();
        $this->notify('success', gp247_language_render('admin.tracking.ask_again_done'));
    }

    /**
     * Store the "tracking code on" switch at once (emergency off — Q7).
     *
     * @return void
     */
    public function saveEnabled(): void
    {
        $this->authorizeAdministrator();
        TrackingSettingsService::saveSwitch($this->currentStore(), 'tracking_enabled', $this->enabled);
        $this->loadSettings();
        $this->notify('success', gp247_language_render('admin.setting_saved'));
    }

    /**
     * Store "this store uses its own code blocks" at once (sub-stores only).
     *
     * @return void
     */
    public function saveSnippetOwn(): void
    {
        $this->authorizeAdministrator();
        if (!$this->isSubStore()) {
            return;
        }
        TrackingSettingsService::saveSwitch($this->currentStore(), 'tracking_snippet_own', $this->snippetOwn);
        $this->loadSettings();
        $this->notify('success', gp247_language_render('admin.setting_saved'));
    }

    /**
     * Drop a sub-store's own value so it follows the ROOT store again.
     *
     * @param string $key One of TrackingSettingsService::FORM_KEYS or BANNER_KEYS.
     * @return void
     */
    public function resetToRoot(string $key): void
    {
        $this->authorizeAdministrator();
        if (!in_array($key, array_merge(TrackingSettingsService::FORM_KEYS, TrackingSettingsService::BANNER_KEYS), true)) {
            return;
        }
        TrackingSettingsService::resetToRoot($this->currentStore(), $key);
        $this->loadSettings();
        $this->notify('success', gp247_language_render('admin.setting_saved'));
    }

    /**
     * Add an empty block at the end of the list (saved with "Save code blocks").
     *
     * @return void
     */
    public function addSnippet(): void
    {
        $this->authorizeAdministrator();
        if (count($this->snippets) >= CodeSnippet::MAX_PER_STORE) {
            $this->addError('snippets', gp247_language_render('admin.tracking.error_too_many'));

            return;
        }
        $this->snippets[] = [
            'label' => '',
            'region' => SnippetRegion::HEAD->value,
            'category' => ConsentCategory::MARKETING->value,
            'content' => '',
            'status' => true,
        ];
    }

    /**
     * Remove a block from the list (saved with "Save code blocks").
     *
     * @param int $index
     * @return void
     */
    public function removeSnippet(int $index): void
    {
        $this->authorizeAdministrator();
        unset($this->snippets[$index]);
        $this->snippets = array_values($this->snippets);
        $this->resetErrorBag();
    }

    /**
     * Check and store every block of the store as one list, with a revision.
     *
     * @return void
     */
    public function saveSnippets(): void
    {
        $this->authorizeAdministrator();
        $this->resetErrorBag();
        try {
            $snippets = CodeSnippet::listFromInput($this->snippets);
        } catch (InvalidTrackingSetting $e) {
            foreach ($e->errors() as $field => $code) {
                $this->addError($field, gp247_language_render($code));
            }

            return;
        }
        $changed = SnippetService::save($this->currentStore(), $snippets);
        $this->load();
        $this->notify('success', gp247_language_render($changed ? 'admin.setting_saved' : 'admin.tracking.no_change'));
    }

    /**
     * @return View
     */
    public function render(): View
    {
        $this->authorizeAdministrator();
        $store = $this->currentStore();
        $isSub = $this->isSubStore();
        $effective = TrackingSettingsService::forStore($store);

        return view('gp247-front-admin::tracking-settings', [
            'modes' => Settings::MODES,
            'languages' => $this->languageOptions(),
            'policyPages' => PolicyPages::options($store),
            'policyVersion' => $effective->consentVersion,
            // WHY: "banner off" while code runs means no visitor is ever asked (AC consent-gate).
            'offWithCode' => $effective->bannerMode === Settings::MODE_OFF
                && ($effective->hasAnyService() || SnippetService::activeFor($store, $effective) !== []),
            'maxBannerText' => Settings::MAX_BANNER_TEXT,
            'storeOptions' => $this->multiStore() ? $this->storeOptions() : [],
            'isSubStore' => $isSub,
            'rootOff' => $isSub && !TrackingSettingsService::forStore(TrackingSettingsService::rootId())->enabled,
            'regions' => array_map(static fn (SnippetRegion $r) => $r->value, SnippetRegion::cases()),
            'categories' => array_map(static fn (ConsentCategory $c) => $c->value, ConsentCategory::assignable()),
            'revisions' => SnippetService::revisions($store),
            'maxSnippets' => CodeSnippet::MAX_PER_STORE,
        ])->layout('gp247-admin::layouts.admin', ['title' => gp247_language_render('admin.tracking.title')]);
    }

    /**
     * Read the store's values into the form.
     *
     * @return void
     */
    private function load(): void
    {
        $this->loadSettings();
        $this->snippets = array_map(static fn (CodeSnippet $s) => $s->toArray(), SnippetService::ownSnippets($this->currentStore()));
    }

    /**
     * Read only the short settings, leaving blocks being edited untouched.
     * WHY: saving an id or a switch must not throw away code pasted but not saved yet.
     *
     * @return void
     */
    private function loadSettings(): void
    {
        $screen = TrackingSettingsService::screenValues($this->currentStore());
        $this->form = array_intersect_key($screen['values'], array_flip(TrackingSettingsService::FORM_KEYS));
        $this->original = $this->form;
        $this->inherited = array_intersect_key($screen['inherited'], array_flip(TrackingSettingsService::FORM_KEYS));
        $this->enabled = (bool) (int) $screen['values']['tracking_enabled'];
        $this->snippetOwn = (bool) (int) $screen['values']['tracking_snippet_own'];
        $this->bannerOriginal = array_intersect_key($screen['values'], array_flip(TrackingSettingsService::BANNER_KEYS));
        $this->bannerInherited = array_intersect_key($screen['inherited'], array_flip(TrackingSettingsService::BANNER_KEYS));
        $stored = json_decode($this->bannerOriginal['tracking_banner_text'] ?? '{}', true);
        $texts = [];
        foreach (array_keys($this->languageOptions()) as $lang) {
            $texts[$lang] = is_array($stored) && is_string($stored[$lang] ?? null) ? $stored[$lang] : '';
        }
        $this->banner = [
            'mode' => $this->bannerOriginal['tracking_banner_mode'] ?? Settings::MODE_OFF,
            'page' => $this->bannerOriginal['tracking_policy_page'] ?? '',
            'text' => $texts,
        ];
    }

    /**
     * Banner form as text only, keeping just the site's languages.
     * WHY: public properties arrive from the browser.
     *
     * @return array{mode: string, page: string, text: array<string, string>}
     */
    private function bannerInput(): array
    {
        $scalar = static fn ($value): string => is_scalar($value) ? (string) $value : '';
        // WHY start from the stored texts: a language switched off keeps its text, and a
        // save that changed nothing stores exactly what was there (no needless override).
        $stored = json_decode($this->bannerOriginal['tracking_banner_text'] ?? '{}', true);
        $texts = is_array($stored) ? array_filter($stored, 'is_string') : [];
        $typed = is_array($this->banner['text'] ?? null) ? $this->banner['text'] : [];
        foreach (array_keys($this->languageOptions()) as $lang) {
            $texts[$lang] = $scalar($typed[$lang] ?? '');
        }

        return ['mode' => $scalar($this->banner['mode'] ?? ''), 'page' => $scalar($this->banner['page'] ?? ''), 'text' => $texts];
    }

    /**
     * @return array<string, string> language code => name, of the site's active languages
     */
    private function languageOptions(): array
    {
        $options = [];
        foreach (gp247_language_all() as $code => $language) {
            $options[(string) $code] = (string) ($language->name ?? $code);
        }

        return $options;
    }

    /**
     * @return string The store being edited, never one the browser made up.
     */
    private function currentStore(): string
    {
        if (!$this->multiStore() || !array_key_exists($this->storeId, $this->storeOptions())) {
            return TrackingSettingsService::rootId();
        }

        return $this->storeId;
    }

    /**
     * @return bool Whether a sub-store (not ROOT) is being edited.
     */
    private function isSubStore(): bool
    {
        return $this->currentStore() !== TrackingSettingsService::rootId();
    }

    /**
     * @return bool Whether a multi-store plugin is installed (store picker shown).
     */
    private function multiStore(): bool
    {
        return function_exists('gp247_store_check_multi_domain_installed') && gp247_store_check_multi_domain_installed();
    }

    /**
     * @return array<string, string> store id => store name
     */
    private function storeOptions(): array
    {
        $options = [];
        foreach (AdminStore::getListTitle() as $id => $title) {
            $options[(string) $id] = (string) $title;
        }

        return $options;
    }

    /**
     * @return void
     * @throws AuthorizationException When the signed-in admin is not an administrator.
     */
    private function authorizeAdministrator(): void
    {
        if (!app(AdminAlertService::class)->allowed()) {
            throw AuthorizationException::fromReason('tracking code is managed by administrators only');
        }
    }
}
