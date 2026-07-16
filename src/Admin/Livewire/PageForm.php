<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\FormComponent;
use GP247\Core\Models\AdminLanguage;
use GP247\Front\Models\FrontPage;
use GP247\Front\Models\FrontPageDescription;
use Illuminate\Contracts\View\View;

/**
 * Page (CMS) create/edit form (front-admin Unit) — modern port of the legacy
 * AdminPageController create/edit: image (LFM), alias, status, plus a
 * multilingual descriptions block (title/keyword/description/content per active
 * language, content via the rich-text editor) and multi-store assignment when
 * multistore/partner is installed. Domain unchanged (FrontPage +
 * FrontPageDescription + FrontPageStore). Gated by `admin_page`.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-002
 * @aidlc-adr ADR-006, ADR-007
 */
class PageForm extends FormComponent
{
    protected ?string $permission = 'admin_page';

    /** @var array<string, mixed> */
    public array $form = [
        'image' => '',
        'alias' => '',
        'status' => 1,
    ];

    /**
     * Per-language description rows keyed by language code, each with
     * title/keyword/description/content.
     *
     * @var array<string, array<string, string>>
     */
    public array $descriptions = [];

    /** @var array<int, int> Store ids assigned to the page (multistore). */
    public array $stores = [];

    /**
     * `content` holds admin-authored rich HTML (TinyMCE, via `<x-gp247::rich-editor>`)
     * that must survive persist() as-is. `$descriptions` is a page-specific
     * property (not FormComponent::$richFields' `$form`), so sanitize it
     * explicitly here instead (RISK-TECH-022 mitigation).
     *
     * @var array<int, string>
     */
    private const RICH_DESCRIPTION_FIELDS = ['content'];

    /**
     * Load active languages, seed empty (or existing) description rows and, when
     * editing, hydrate the page attributes and assigned stores.
     *
     * @param string|null $id Page id to edit; null to create.
     * @return void
     */
    public function mount(?string $id = null): void
    {
        parent::mount();

        // WHY: mirror AdminPageController — the active language list drives which
        // description rows exist; every active language always gets a row.
        $languages = AdminLanguage::getListActive();

        $existing = [];
        if ($id !== null) {
            $page = FrontPage::findOrFail($id);
            $this->editingId = (string) $page->id;
            $this->form = [
                'image' => (string) $page->image,
                'alias' => (string) $page->alias,
                'status' => (int) $page->status,
            ];
            $existing = $page->descriptions->keyBy('lang');
            $this->stores = $page->stores()->pluck('store_id')->map(static fn ($v): int => (int) $v)->all();
        }

        foreach ($languages as $code => $language) {
            $row = $existing[$code] ?? null;
            $this->descriptions[$code] = [
                'name' => $row->name ?? '',
                'keyword' => $row->keyword ?? '',
                'description' => $row->description ?? '',
                'content' => $row->content ?? '',
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'form.alias' => ['required', 'string', 'max:100'],
            'descriptions.*.name' => ['required', 'string', 'max:200'],
            'descriptions.*.keyword' => ['nullable', 'string', 'max:200'],
            'descriptions.*.description' => ['nullable', 'string', 'max:500'],
            'descriptions.*.content' => ['nullable', 'string'],
        ];
    }

    /**
     * Create/update the page, upsert per-language descriptions (delete then
     * reinsert, like the legacy controller) and sync stores when multistore is on.
     *
     * @param array<string, mixed> $data Sanitised form values.
     * @return void
     */
    protected function persist(array $data): void
    {
        // WHY: mirror legacy — when alias is blank, derive it from the first
        // active language title, slugify and cap at 100 chars.
        $alias = $data['alias'] ?? '';
        if ($alias === '') {
            $firstCode = (string) array_key_first($this->descriptions);
            $alias = $this->descriptions[$firstCode]['name'] ?? '';
        }
        $alias = gp247_word_limit(gp247_word_format_url($alias), 100);

        $attributes = [
            'image' => $data['image'] ?? '',
            'alias' => $alias,
            'status' => empty($data['status']) ? 0 : 1,
        ];

        if ($this->editingId !== null) {
            $page = FrontPage::findOrFail($this->editingId);
            $page->update($attributes);
        } else {
            $page = FrontPage::create($attributes);
        }

        // WHY: delete + reinsert the whole description set per language (legacy
        // behaviour) so removed/renamed locales never leave stale rows.
        $page->descriptions()->delete();
        $rows = [];
        foreach ($this->descriptions as $code => $row) {
            $fields = ['name' => '', 'keyword' => '', 'description' => '', 'content' => ''];
            foreach ($fields as $field => $default) {
                $value = (string) ($row[$field] ?? $default);
                // WHY: rich HTML fields keep their markup (raw); plain-text fields are XSS-cleaned.
                $fields[$field] = in_array($field, self::RICH_DESCRIPTION_FIELDS, true) ? $value : gp247_clean($value);
            }
            $rows[] = array_merge(['page_id' => $page->id, 'lang' => $code], $fields);
        }
        FrontPageDescription::create($rows);

        // WHY: only sync the store pivot when multistore/partner is active, so a
        // single-store install behaves exactly like the legacy screen (no pivot).
        if (gp247_store_check_multi_partner_installed() || gp247_store_check_multi_store_installed()) {
            $page->stores()->sync($this->stores);
        }
    }

    /**
     * Save, then return to the list with a flash.
     *
     * @return void
     */
    public function save(): void
    {
        parent::save();

        session()->flash('gp247_admin_success', gp247_language_render('admin.save_success'));
        $this->redirectRoute('admin_page.index', navigate: true);
    }

    /**
     * @return array{name: string, url: string}
     */
    protected function listCrumb(): array
    {
        return ['name' => gp247_language_render('admin.page.title'), 'url' => route('admin_page.index')];
    }

    /**
     * @return View
     */
    public function render(): View
    {
        $multiStore = gp247_store_check_multi_partner_installed() || gp247_store_check_multi_store_installed();

        return view('gp247-front-admin::page-form', [
            'languages' => AdminLanguage::getListActive(),
            'multiStore' => $multiStore,
            'storeList' => $multiStore ? \GP247\Core\Models\AdminStore::pluck('name', 'id')->all() : [],
        ])->layout('gp247-admin::layouts.admin', [
            'title' => gp247_language_render($this->editingId !== null ? 'action.edit' : 'admin.page.add_new'),
            'breadcrumb' => $this->listCrumb(),
        ]);
    }
}
