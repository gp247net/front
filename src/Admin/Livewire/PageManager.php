<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\HasValidationLabels;
use GP247\Core\AdminShell\Infrastructure\ResourcePanel;
use GP247\Core\Models\AdminLanguage;
use GP247\Front\Admin\Livewire\Concerns\HasSeoPanel;
use GP247\Front\Models\FrontPage;
use GP247\Front\Models\FrontPageDescription;
use GP247\Front\Support\PageDuplicator;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;

/**
 * Page (CMS) manager — two-panel screen (form left, list right) following the
 * ResourcePanel pattern (ADR-005, ADR-007, ui-tailadmin P1). Replaces the separate
 * PageList + PageForm pair. Multi-language descriptions and a scalar store_id owner (1-1)
 * are preserved. Gated by `admin_page`.
 *
 * Carries the shared SEO panel (HasSeoPanel, US-SEO-content-meta, modification 20261117T140612).
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-002, US-SEO-content-meta, US-FADM-page-duplicate
 * @aidlc-adr ADR-001, ADR-005, ADR-006, ADR-007, front-admin_page-duplicate
 */
class PageManager extends ResourcePanel
{
    use HasValidationLabels;
    use HasSeoPanel;

    protected ?string $permission = 'admin_page';

    /**
     * Keep list state (page/keyword/sort) and the edited record on screen when
     * editing/saving, instead of remounting via route navigation.
     *
     * @var bool
     * @aidlc-story US-AUI-two-panel-state-preservation
     * @aidlc-adr ADR-admin-shell-rbac-two-panel-state-preservation
     */
    protected bool $keepStateOnSave = true;

    /**
     * Per-language description rows keyed by language code, each with
     * title/keyword/description/content.
     *
     * @var array<string, array<string, string>>
     */
    public array $descriptions = [];

    /**
     * `content` holds admin-authored rich HTML (TinyMCE, via `<x-gp247::rich-editor>`)
     * that must survive persist() as-is. `$descriptions` is a page-specific
     * property (not the shared HasMultilingualDescriptions::$desc), so it is not
     * covered by ResourcePanel::$richFields — sanitize it explicitly here instead
     * (RISK-TECH-022 mitigation).
     *
     * @var array<int, string>
     */
    private const RICH_DESCRIPTION_FIELDS = ['content'];

    /**
     * Initialise the descriptions skeleton before ResourcePanel::mount so that
     * fillForm() / resetForm() can populate it correctly on first render.
     *
     * @param string|null $id Page id to edit; null for create mode.
     * @return void
     */
    public function mount($id = null): void
    {
        $this->descriptions = $this->emptyDescriptions();
        parent::mount($id);
    }

    /**
     * Build an empty per-language descriptions array from the active language list.
     *
     * @return array<string, array<string, string>>
     */
    private function emptyDescriptions(): array
    {
        $result = [];
        foreach (AdminLanguage::getListActive() as $code => $language) {
            $result[$code] = ['name' => '', 'keyword' => '', 'description' => '', 'content' => ''];
        }
        return $result;
    }

    /**
     * Store-scoped: pick a store on create (root admin), show it in the list, lock
     * it on edit. Page is a leaf entity (only multilingual descriptions).
     *
     * @return array<string, mixed>|null
     *
     * @aidlc-unit front-admin
     * @aidlc-story US-SADM-store-content-assignment
     * @aidlc-adr admin-shell_store-scoped-resource-panel
     */
    protected function storeScoped(): ?array
    {
        return ['display' => 'name', 'reset' => []];
    }

    /**
     * Store-scoped page query: root admin shows every store's pages (each row
     * labelled by its store); a scoped context (store-admin/switcher) or a
     * single-store install filters to the own store.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function baseQuery()
    {
        // WHY: 1-1 ownership — eager-load the single owning store (store.descriptions).
        $query = FrontPage::with(['descriptions', 'store.descriptions']);
        if (!($this->storeScopeActive() && $this->isRootScope())) {
            $query->where('store_id', $this->storeContext());
        }

        return $query;
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['alias'];
    }

    /**
     * Language keys naming the searched columns (shown in the search box).
     *
     * @return array<string, string>
     *
     * @aidlc-story US-admin-shell-list-search-scope-hint
     */
    protected function searchLabels(): array
    {
        return [
            'alias' => 'admin.search.col_alias',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function sortableColumns(): array
    {
        return ['alias', 'status'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function formDefaults(): array
    {
        return [
            'image'  => '',
            'alias'  => '',
            'status' => 1,
        ];
    }

    /**
     * Hydrate $form from the model; also sets $descriptions as a side effect so
     * both stay in sync whenever a row is loaded for editing.
     *
     * @param FrontPage $model
     * @return array<string, mixed>
     */
    protected function fillForm($model): array
    {
        $existing     = $model->descriptions->keyBy('lang');
        $descriptions = [];

        foreach (AdminLanguage::getListActive() as $code => $language) {
            $row                  = $existing[$code] ?? null;
            $descriptions[$code]  = [
                'name'        => $row->name ?? '',
                'keyword'     => $row->keyword ?? '',
                'description' => $row->description ?? '',
                'content'     => $row->content ?? '',
            ];
        }

        $this->descriptions = $descriptions;
        $this->fillSeo($model->id);

        // Store is immutable on edit — expose it for the read-only display.
        $this->formStoreId = (string) $model->store_id;

        return [
            'image'  => (string) $model->image,
            'alias'  => (string) $model->alias,
            'status' => (int) $model->status,
        ];
    }

    /**
     * Reset form back to create mode, clearing descriptions too.
     *
     * @return void
     */
    public function resetForm(): void
    {
        parent::resetForm();
        $this->descriptions = $this->emptyDescriptions();
        $this->fillSeo(null);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return array_merge([
            'form.alias'                   => ['required', 'string', 'max:100'],
            'descriptions.*.name'          => ['required', 'string', 'max:200'],
            'descriptions.*.keyword'       => ['nullable', 'string', 'max:200'],
            'descriptions.*.description'   => ['nullable', 'string', 'max:500'],
            'descriptions.*.content'       => ['nullable', 'string'],
        ], $this->seoRules());
    }

    /**
     * Reuse the existing v1 page label keys for validator attributes (the
     * per-language title maps to admin.page.title_field, matching the form label).
     *
     * @return array<string, string>
     */
    protected function attributeLabels(): array
    {
        return [
            'form.alias' => 'admin.page.alias',
            'descriptions.*.name' => 'admin.page.title_field',
            'descriptions.*.keyword' => 'admin.page.keyword',
            'descriptions.*.description' => 'admin.page.description',
            'descriptions.*.content' => 'admin.page.content',
        ] + $this->seoAttributeLabels();
    }

    /**
     * Create/update the page and upsert per-language descriptions (delete then
     * reinsert, like the legacy controller). Store ownership is a scalar store_id
     * set on create (1-1).
     *
     * @param array<string, mixed> $data Sanitised form values.
     * @return void
     */
    protected function persist(array $data): void
    {
        $this->persistWithSeo(fn () => $this->persistContent($data));
    }

    /**
     * SEO content-type key of this screen (US-SEO-content-meta).
     *
     * @return string
     */
    protected function seoType(): string
    {
        return 'page';
    }

    /**
     * Save the record itself; the SEO panel is saved right after, in the same
     * transaction (persistWithSeo, ADR-seo-content-meta-table).
     *
     * @param array<string, mixed> $data Sanitised form.
     * @return void
     */
    private function persistContent(array $data): void
    {
        // WHY: mirror legacy — when alias is blank, derive it from the first
        // active language title, slugify and cap at 100 chars.
        $alias = $data['alias'] ?? '';
        if ($alias === '') {
            $firstCode = (string) array_key_first($this->descriptions);
            $alias     = $this->descriptions[$firstCode]['name'] ?? '';
        }
        $alias = gp247_word_limit(gp247_word_format_url($alias), 100);

        $attributes = [
            'image'  => $data['image'] ?? '',
            'alias'  => $alias,
            'status' => empty($data['status']) ? 0 : 1,
        ];

        if ($this->editingId !== null) {
            // Store is immutable on edit — do NOT touch store_id (ADR 1-1).
            // WHY baseQuery(): only a page of the current store scope may be written;
            // one outside it (another store) fails closed with a 404.
            $page = $this->baseQuery()->findOrFail($this->editingId);
            $page->update($attributes);
        } else {
            // WHY: 1-1 ownership — a new page is owned by the store picked on create
            // (root admin) or the current scoped store (store-admin / switcher).
            $attributes['store_id'] = $this->resolveCreateStore();
            $page = FrontPage::create($attributes);
            // ResourcePanel create contract: expose the new id so save() edits it in place.
            $this->editingId = (string) $page->id;
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
                $fields[$field] = in_array($field, self::RICH_DESCRIPTION_FIELDS, true) ? $value : gp247_text_plain($value);
            }
            $rows[] = array_merge(['page_id' => $page->id, 'lang' => $code], $fields);
        }
        FrontPageDescription::create($rows);

        // Store ownership is set on the page row (store_id) above.

        // WHY: FrontPage::getListTitleAdmin() caches page-title lists per store x
        // locale; without this the admin dropdown would serve stale titles until the
        // TTL expires. Version-bump invalidates every variant at once (RISK-TECH-cache-stale-key).
        if (function_exists('gp247_cache_clear')) {
            gp247_cache_clear('cache_page');
        }
    }

    /**
     * Duplicate a page (list row or edit form) and open the copy for editing. Allowed to
     * whoever may create pages; the original is read through baseQuery(), so a store admin
     * only reaches pages of their own store. The stored page is duplicated, not unsaved
     * input on the form.
     *
     * @param int|string $id Page to duplicate.
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When the admin may not create pages.
     *
     * @aidlc-unit front-admin
     * @aidlc-story US-FADM-page-duplicate
     * @aidlc-adr front-admin_page-duplicate
     */
    public function duplicate($id): void
    {
        $this->authorizeAction('store');

        // WHY baseQuery(): $id comes from the browser — a store-admin must not read another store's page.
        $source = $this->baseQuery()->find($id);
        if ($source === null) {
            $this->notify('error', gp247_language_render('admin.display.data_not_found'));

            return;
        }

        try {
            $copy = (new PageDuplicator)->duplicate($source);
        } catch (ValidationException $e) {
            // WHY: stored values of the original that no longer pass today's rules (e.g. an old
            // SEO canonical) — the whole copy was rolled back; tell the admin how to fix it.
            gp247_report(msg: 'Page duplicate rolled back (' . $source->id . '): ' . json_encode($e->errors()), channel: null);
            $this->notify('error', gp247_language_render('admin.page.duplicate_failed'));

            return;
        }

        // WHY: same cached page-title lists as persist() / deleteModel().
        if (function_exists('gp247_cache_clear')) {
            gp247_cache_clear('cache_page');
        }
        $this->editRow((string) $copy->id);
        $this->showRecordPage((string) $copy->id);
        $this->notify('success', gp247_language_render('admin.page.duplicate_success'));
    }

    /**
     * Delete a page of the current store scope; an id outside it is ignored.
     *
     * @param int|string $id Client-supplied page id.
     * @return void
     *
     * @aidlc-story US-admin-shell-store-scoped-resource
     */
    protected function deleteModel($id): void
    {
        // WHY baseQuery(): $id comes from the browser — a store-admin must not delete another store's page.
        $model = $this->baseQuery()->find($id);
        if ($model !== null) {
            $model->delete();
            // WHY: keep the cached page-title dropdown consistent after a delete
            // (same reason as persist()); version-bump clears every store x locale.
            if (function_exists('gp247_cache_clear')) {
                gp247_cache_clear('cache_page');
            }
        }
    }

    /**
     * @return string
     */
    protected function panelView(): string
    {
        return 'gp247-front-admin::page-manager';
    }

    /**
     * @return string
     */
    protected function pageTitle(): string
    {
        return gp247_language_render('admin.page.title');
    }

    /**
     * @return string
     */
    protected function baseRoute(): string
    {
        return 'admin_page.index';
    }

    /**
     * @return View
     */
    public function render(): View
    {
        // WHY: 1-1 ownership — a page has a single owning store (pinned to the
        // current admin store), so no multi-store picker context is injected.
        return view($this->panelView(), [
            'rows'      => $this->rows(),
            'languages' => AdminLanguage::getListActive(),
        ])->layout('gp247-admin::layouts.admin', ['title' => $this->pageTitle()]);
    }
}
