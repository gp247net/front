<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\FormComponent;
use GP247\Front\Models\FrontLink;
use GP247\Front\Models\FrontLinkGroup;
use Illuminate\Contracts\View\View;

/**
 * Link create/edit form (front-admin Unit) — modern port of the legacy
 * AdminLinkController create/edit: name, url, target, group (link-group code),
 * collection (parent link) and type (single|collection), sort and status, plus
 * multi-store assignment when multistore/partner is installed. Domain unchanged
 * (FrontLink). Gated by `admin_link`.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-003
 * @aidlc-adr ADR-001, ADR-006, ADR-007
 */
class LinkForm extends FormComponent
{
    protected ?string $permission = 'admin_link';

    /** @var array<string, mixed> */
    public array $form = [
        'name' => '',
        'url' => '',
        'target' => '_self',
        'group' => '',
        'collection_id' => '',
        'type' => '',
        'sort' => 0,
        'status' => 1,
    ];

    /** @var array<int, int> Store ids assigned to the link (multistore). */
    public array $stores = [];

    /**
     * @param string|null $id Link id to edit; null to create.
     * @return void
     */
    public function mount(?string $id = null): void
    {
        parent::mount();

        if ($id !== null) {
            $link = FrontLink::findOrFail($id);
            $this->editingId = (string) $link->id;
            $this->form = [
                'name' => (string) $link->name,
                'url' => (string) $link->url,
                'target' => $link->target ?: '_self',
                'group' => (string) $link->group,
                'collection_id' => (string) $link->collection_id,
                'type' => (string) $link->type,
                'sort' => (int) $link->sort,
                'status' => (int) $link->status,
            ];
            $this->stores = $link->stores()->pluck('store_id')->map(static fn ($v): int => (int) $v)->all();
        }
    }

    /**
     * Validation rules. url/target are required only for single links; a
     * collection link carries no destination (mirrors the legacy controller,
     * which skips url/target when type == 'collection').
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $isCollection = ($this->form['type'] ?? '') === 'collection';

        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.group' => ['required', 'string', 'max:255'],
            'form.url' => $isCollection ? ['nullable', 'string', 'max:255'] : ['required', 'string', 'max:255'],
            'form.target' => $isCollection ? ['nullable', 'in:_self,_blank'] : ['required', 'in:_self,_blank'],
            'form.collection_id' => ['nullable', 'string', 'max:255'],
            'form.sort' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @param array<string, mixed> $data Sanitised form values.
     * @return void
     */
    protected function persist(array $data): void
    {
        $isCollection = ($data['type'] ?? '') === 'collection';

        $attributes = [
            'name' => $data['name'],
            'group' => $data['group'] ?? '',
            'sort' => (int) ($data['sort'] ?? 0),
            'status' => empty($data['status']) ? 0 : 1,
        ];

        if ($isCollection) {
            // WHY: a collection link is a folder with no real destination — the
            // legacy controller stores a sentinel url/type. `target` is NOT NULL
            // with no DB default, so set a benign value (mirrors legacy).
            $attributes['url'] = 'collection';
            $attributes['type'] = 'collection';
            $attributes['target'] = '_self';
            $attributes['collection_id'] = null;
        } else {
            $attributes['url'] = $data['url'] ?? '';
            $attributes['target'] = $data['target'] ?? '_self';
            $attributes['type'] = '';
            $attributes['collection_id'] = ($data['collection_id'] ?? '') !== '' ? $data['collection_id'] : null;
        }

        if ($this->editingId !== null) {
            $link = FrontLink::findOrFail($this->editingId);
            $link->update($attributes);
        } else {
            $link = FrontLink::create($attributes);
        }

        // WHY: only sync the store pivot when multistore/partner is active, so a
        // single-store install behaves exactly like the legacy screen (no pivot).
        if (gp247_store_check_multi_partner_installed() || gp247_store_check_multi_store_installed()) {
            $link->stores()->sync($this->stores);
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

        session()->flash('gp247_admin_success', gp247_language_render('admin.core.save_success'));
        $this->redirectRoute('admin_link.index', navigate: true);
    }

    /**
     * @return array{name: string, url: string}
     */
    protected function listCrumb(): array
    {
        return ['name' => gp247_language_render('admin.link.title'), 'url' => route('admin_link.index')];
    }

    /**
     * @return View
     */
    public function render(): View
    {
        $multiStore = gp247_store_check_multi_partner_installed() || gp247_store_check_multi_store_installed();

        return view('gp247-front-admin::link-form', [
            'groups' => FrontLinkGroup::orderBy('name')->get(),
            'collections' => FrontLink::where('type', 'collection')->orderBy('name')->get(),
            'multiStore' => $multiStore,
            'storeList' => $multiStore ? \GP247\Core\Models\AdminStore::pluck('name', 'id')->all() : [],
        ])->layout('gp247-admin::layouts.admin', [
            'title' => gp247_language_render($this->editingId !== null ? 'action.edit' : 'admin.link.add_new'),
            'breadcrumb' => $this->listCrumb(),
        ]);
    }
}
