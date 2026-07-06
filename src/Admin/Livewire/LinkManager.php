<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\ResourcePanel;
use GP247\Front\Models\FrontLink;
use GP247\Front\Models\FrontLinkGroup;
use Illuminate\Contracts\View\View;

/**
 * Link manager — two-panel screen (form left, list right) following the
 * ResourcePanel pattern (ADR-005, ADR-007, ui-tailadmin P1). Replaces the separate
 * LinkList + LinkForm pair. Multi-store pivot sync is preserved. Gated by `admin_link`.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-003
 * @aidlc-adr ADR-001, ADR-005, ADR-006, ADR-007
 */
class LinkManager extends ResourcePanel
{
    protected ?string $permission = 'admin_link';

    /** @var array<int, int> Store ids assigned to the link (multistore). */
    public array $stores = [];

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function baseQuery()
    {
        return FrontLink::query();
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['name'];
    }

    /**
     * @return array<int, string>
     */
    protected function sortableColumns(): array
    {
        return ['name', 'group', 'sort', 'status'];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function defaultSort(): array
    {
        return ['sort', 'asc'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function formDefaults(): array
    {
        return [
            'name'          => '',
            'url'           => '',
            'target'        => '_self',
            'group'         => '',
            'collection_id' => '',
            'type'          => '',
            'sort'          => 0,
            'status'        => 1,
        ];
    }

    /**
     * @param FrontLink $model
     * @return array<string, mixed>
     */
    protected function fillForm($model): array
    {
        // WHY: also reset stores so the pivot reflects the current record on edit.
        $this->stores = $model->stores()->pluck('store_id')->map(static fn($v): int => (int) $v)->all();

        return [
            'name'          => (string) $model->name,
            'url'           => (string) $model->url,
            'target'        => $model->target ?: '_self',
            'group'         => (string) $model->group,
            'collection_id' => (string) $model->collection_id,
            'type'          => (string) $model->type,
            'sort'          => (int) $model->sort,
            'status'        => (int) $model->status,
        ];
    }

    /**
     * @return void
     */
    public function resetForm(): void
    {
        parent::resetForm();
        $this->stores = [];
    }

    /**
     * Validation: url/target required only for non-collection links.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $isCollection = ($this->form['type'] ?? '') === 'collection';

        return [
            'form.name'          => ['required', 'string', 'max:255'],
            'form.group'         => ['required', 'string', 'max:255'],
            'form.url'           => $isCollection ? ['nullable', 'string', 'max:255'] : ['required', 'string', 'max:255'],
            'form.target'        => $isCollection ? ['nullable', 'in:_self,_blank'] : ['required', 'in:_self,_blank'],
            'form.collection_id' => ['nullable', 'string', 'max:255'],
            'form.sort'          => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    protected function persist(array $data): void
    {
        $isCollection = ($data['type'] ?? '') === 'collection';

        $attributes = [
            'name'   => $data['name'],
            'group'  => $data['group'] ?? '',
            'sort'   => (int) ($data['sort'] ?? 0),
            'status' => empty($data['status']) ? 0 : 1,
        ];

        if ($isCollection) {
            // WHY: a collection link is a folder with no real destination — sentinel values.
            $attributes['url']           = 'collection';
            $attributes['type']          = 'collection';
            $attributes['target']        = '_self';
            $attributes['collection_id'] = null;
        } else {
            $attributes['url']           = $data['url'] ?? '';
            $attributes['target']        = $data['target'] ?? '_self';
            $attributes['type']          = '';
            $attributes['collection_id'] = ($data['collection_id'] ?? '') !== '' ? $data['collection_id'] : null;
        }

        if ($this->editingId !== null) {
            $link = FrontLink::findOrFail($this->editingId);
            $link->update($attributes);
        } else {
            $link = FrontLink::create($attributes);
        }

        // WHY: sync store pivot only when multistore is active, preserving
        // single-store install behaviour from the legacy controller.
        if (gp247_store_check_multi_partner_installed() || gp247_store_check_multi_store_installed()) {
            $link->stores()->sync($this->stores);
        }
    }

    /**
     * @param int|string $id
     * @return void
     */
    protected function deleteModel($id): void
    {
        $model = FrontLink::find($id);
        if ($model !== null) {
            $model->delete();
        }
    }

    /**
     * @return string
     */
    protected function panelView(): string
    {
        return 'gp247-front-admin::link-manager';
    }

    /**
     * @return string
     */
    protected function pageTitle(): string
    {
        return gp247_language_render('admin.link.title');
    }

    /**
     * @return string
     */
    protected function baseRoute(): string
    {
        return 'admin_link.index';
    }

    /**
     * @return View
     */
    public function render(): View
    {
        $multiStore = gp247_store_check_multi_partner_installed() || gp247_store_check_multi_store_installed();

        return view($this->panelView(), [
            'rows'        => $this->rows(),
            'groups'      => FrontLinkGroup::orderBy('name')->get(),
            'collections' => FrontLink::where('type', 'collection')->orderBy('name')->get(),
            'multiStore'  => $multiStore,
            'storeList'   => $multiStore ? \GP247\Core\Models\AdminStore::pluck('name', 'id')->all() : [],
        ])->layout('gp247-admin::layouts.admin', ['title' => $this->pageTitle()]);
    }
}
