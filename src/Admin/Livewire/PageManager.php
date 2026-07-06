<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\ResourcePanel;
use GP247\Core\Models\AdminLanguage;
use GP247\Front\Models\FrontPage;
use GP247\Front\Models\FrontPageDescription;
use Illuminate\Contracts\View\View;

/**
 * Page (CMS) manager — two-panel screen (form left, list right) following the
 * ResourcePanel pattern (ADR-005, ADR-007, ui-tailadmin P1). Replaces the separate
 * PageList + PageForm pair. Multi-language descriptions and multi-store pivot sync
 * are preserved. Gated by `admin_page`.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-002
 * @aidlc-adr ADR-001, ADR-005, ADR-006, ADR-007
 */
class PageManager extends ResourcePanel
{
    protected ?string $permission = 'admin_page';

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
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function baseQuery()
    {
        return FrontPage::with('descriptions');
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['alias'];
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
     * Hydrate $form from the model; also sets $descriptions and $stores as side
     * effects so all three stay in sync whenever a row is loaded for editing.
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
        $this->stores       = $model->stores()->pluck('store_id')
            ->map(static fn ($v): int => (int) $v)
            ->all();

        return [
            'image'  => (string) $model->image,
            'alias'  => (string) $model->alias,
            'status' => (int) $model->status,
        ];
    }

    /**
     * Reset form back to create mode, clearing descriptions and stores too.
     *
     * @return void
     */
    public function resetForm(): void
    {
        parent::resetForm();
        $this->stores       = [];
        $this->descriptions = $this->emptyDescriptions();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'form.alias'                   => ['required', 'string', 'max:100'],
            'descriptions.*.name'          => ['required', 'string', 'max:200'],
            'descriptions.*.keyword'       => ['nullable', 'string', 'max:200'],
            'descriptions.*.description'   => ['nullable', 'string', 'max:500'],
            'descriptions.*.content'       => ['nullable', 'string'],
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
            $alias     = $this->descriptions[$firstCode]['name'] ?? '';
        }
        $alias = gp247_word_limit(gp247_word_format_url($alias), 100);

        $attributes = [
            'image'  => $data['image'] ?? '',
            'alias'  => $alias,
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
            $rows[] = [
                'page_id'     => $page->id,
                'lang'        => $code,
                'name'        => $row['name'] ?? '',
                'keyword'     => $row['keyword'] ?? '',
                'description' => $row['description'] ?? '',
                'content'     => $row['content'] ?? '',
            ];
        }
        FrontPageDescription::create($rows);

        // WHY: only sync the store pivot when multistore/partner is active, so a
        // single-store install behaves exactly like the legacy screen (no pivot).
        if (gp247_store_check_multi_partner_installed() || gp247_store_check_multi_store_installed()) {
            $page->stores()->sync($this->stores);
        }
    }

    /**
     * @param int|string $id
     * @return void
     */
    protected function deleteModel($id): void
    {
        $model = FrontPage::find($id);
        if ($model !== null) {
            $model->delete();
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
        $multiStore = gp247_store_check_multi_partner_installed() || gp247_store_check_multi_store_installed();

        return view($this->panelView(), [
            'rows'       => $this->rows(),
            'languages'  => AdminLanguage::getListActive(),
            'multiStore' => $multiStore,
            'storeList'  => $multiStore ? \GP247\Core\Models\AdminStore::pluck('name', 'id')->all() : [],
        ])->layout('gp247-admin::layouts.admin', ['title' => $this->pageTitle()]);
    }
}
