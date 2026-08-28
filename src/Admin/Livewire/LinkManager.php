<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\HasValidationLabels;
use GP247\Core\AdminShell\Infrastructure\ResourcePanel;
use GP247\Front\Models\FrontLink;
use GP247\Front\Models\FrontLinkGroup;
use Illuminate\Contracts\View\View;

/**
 * Link manager — two-panel screen (form left, list right) following the
 * ResourcePanel pattern (ADR-005, ADR-007, ui-tailadmin P1). Replaces the separate
 * LinkList + LinkForm pair. Store ownership is 1-1 (scalar store_id). Gated by `admin_link`.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-003
 * @aidlc-adr ADR-001, ADR-005, ADR-006, ADR-007, multi-store_one-to-one-store-ownership
 */
class LinkManager extends ResourcePanel
{
    use HasValidationLabels;

    protected ?string $permission = 'admin_link';

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
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function baseQuery()
    {
        // Eager-load the parent collection so the list can show its name
        // without an N+1 query per row. WHY: 1-1 ownership — eager-load the
        // single owning store (store.descriptions).
        return FrontLink::query()->with(['collection', 'store.descriptions']);
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
     * Reuse the existing v1 link label keys for validator attributes.
     *
     * @return array<string, string>
     */
    protected function attributeLabels(): array
    {
        return [
            'form.name' => 'admin.link.name',
            'form.group' => 'admin.link.group',
            'form.url' => 'admin.link.url',
            'form.target' => 'admin.link.target',
            'form.collection_id' => 'admin.link.collection',
            'form.sort' => 'admin.sort',
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

            // WHY: 1-1 ownership — a link may only point at a collection owned by
            // its own store. The dropdown is store-scoped, but a crafted payload
            // could bind a foreign id, so reject it server-side
            // (RISK-TECH-store-same-store-ref).
            $this->assertSameStoreCollection($attributes['collection_id']);
        }

        if ($this->editingId !== null) {
            $link = FrontLink::findOrFail($this->editingId);
            $link->update($attributes);
        } else {
            // WHY: 1-1 ownership — a new link is owned by the current admin store
            // (pinned to root in admin); set its scalar store_id on create.
            $attributes['store_id'] = $this->currentStoreId();
            $link = FrontLink::create($attributes);
        }

        // Store ownership is set on the link row (store_id) above.
    }

    /**
     * The current admin store id, falling back to the root store.
     *
     * @return int|string
     */
    private function currentStoreId()
    {
        return session('adminStoreId', defined('GP247_STORE_ID_ROOT') ? GP247_STORE_ID_ROOT : 1);
    }

    /**
     * Reject a save whose collection_id points at a link owned by another store.
     * A null/empty value passes (no collection selected); only a non-empty id
     * that does not resolve to a link owned by the current store is rejected.
     *
     * @param int|string|null $collectionId Submitted collection link id.
     * @return void
     * @throws \Illuminate\Validation\ValidationException When the collection is cross-store.
     *
     * @aidlc-adr multi-store_one-to-one-store-ownership
     */
    private function assertSameStoreCollection($collectionId): void
    {
        if ($collectionId === null || $collectionId === '') {
            return;
        }

        $exists = FrontLink::where('id', $collectionId)
            ->where('store_id', $this->currentStoreId())
            ->exists();

        if (!$exists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'form.collection_id' => gp247_language_render('admin.link.collection') . ': invalid store reference',
            ]);
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
        // WHY: 1-1 ownership — a link has a single owning store (pinned to the
        // current admin store), so no multi-store picker context is injected.
        // WHY: 1-1 ownership — only offer this store's groups/collections so an
        // admin cannot reference another store's row (RISK-TECH-store-same-store-ref).
        $storeId = $this->currentStoreId();

        return view($this->panelView(), [
            'rows'        => $this->rows(),
            'groups'      => FrontLinkGroup::where('store_id', $storeId)->orderBy('name')->get(),
            'collections' => FrontLink::where('store_id', $storeId)->where('type', 'collection')->orderBy('name')->get(),
        ])->layout('gp247-admin::layouts.admin', ['title' => $this->pageTitle()]);
    }
}
