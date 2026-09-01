<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\FormComponent;
use GP247\Core\AdminShell\Infrastructure\HasStoreScopeUi;
use GP247\Core\AdminShell\Infrastructure\HasValidationLabels;
use GP247\Front\Models\FrontBanner;
use GP247\Front\Models\FrontBannerType;
use Illuminate\Contracts\View\View;

/**
 * Banner create/edit form (front-admin Unit) — modern port of the legacy
 * AdminBannerController create/edit: image (LFM), url, title, html (rich text),
 * type (banner-type), target, sort and status. Store ownership is 1-1 (scalar
 * store_id, pinned to the current admin store). Domain unchanged (FrontBanner).
 * Gated by `admin_banner`.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-001
 * @aidlc-adr ADR-001, ADR-006, ADR-007, multi-store_one-to-one-store-ownership
 */
class BannerForm extends FormComponent
{
    use HasValidationLabels;
    use HasStoreScopeUi;

    protected ?string $permission = 'admin_banner';

    /**
     * Opt into store scoping (pick a store on create, lock on edit, scope the
     * banner-type dropdown to that store).
     *
     * @return bool
     *
     * @aidlc-story US-SADM-store-content-assignment
     * @aidlc-adr admin-shell_store-scoped-resource-panel
     */
    protected function storeScopeOptIn(): bool
    {
        return true;
    }

    /**
     * @var array<int, string> `html` holds admin-authored HTML markup (the
     * banner's rich-text block); it must not be htmlspecialchars-escaped by
     * the shared save() boundary sanitization.
     */
    protected array $richFields = ['html'];

    /** @var array<string, mixed> */
    public array $form = [
        'image' => '',
        'url' => '',
        'name' => '',
        'html' => '',
        'type' => '',
        'target' => '_self',
        'sort' => 0,
        'status' => 1,
    ];

    /**
     * @param string|null $id Banner id to edit; null to create.
     * @return void
     */
    public function mount(?string $id = null): void
    {
        parent::mount();

        if ($id === null) {
            // Create: default the picker to the current context store (ROOT at root
            // admin, the bound store in a scoped context) — parity with
            // ResourcePanel::resetForm() so a store is always set on create.
            if ($this->storeScopeActive()) {
                $this->formStoreId = (string) $this->storeContext();
            }

            return;
        }

        $banner = FrontBanner::findOrFail($id);
        $this->editingId = (string) $banner->id;
        // Store is immutable on edit — expose it for the read-only display + to
        // scope the banner-type dropdown to the record's own store.
        $this->formStoreId = (string) $banner->store_id;
        $this->form = [
                'image' => (string) $banner->image,
                'url' => (string) $banner->url,
                'name' => (string) $banner->name,
                'html' => (string) $banner->html,
                'type' => (string) $banner->type,
                'target' => $banner->target ?: '_self',
                'sort' => (int) $banner->sort,
                'status' => (int) $banner->status,
        ];
    }

    /**
     * The store the form is bound to, for scoping the banner-type options + guard:
     * on edit the record's own store (read from the DB, tamper-proof); on create at
     * root the picked store (null until chosen); otherwise the current context.
     *
     * @return int|string|null
     */
    private function currentStore()
    {
        if (!$this->storeScopeActive()) {
            return $this->storeContext();
        }
        if ($this->editingId !== null && $this->editingId !== '') {
            $recStore = FrontBanner::whereKey($this->editingId)->value('store_id');

            return $recStore !== null ? $recStore : $this->storeContext();
        }
        if (!$this->isRootScope()) {
            return $this->storeContext();
        }

        return $this->formStoreId !== '' ? $this->formStoreId : null;
    }

    /**
     * Livewire hook: when the create picker changes the store, clear the store-
     * dependent banner type so a stale cross-store code cannot linger, and notify.
     *
     * @return void
     */
    public function updatedFormStoreId(): void
    {
        if (!$this->storeScopeActive() || $this->editingId !== null) {
            return;
        }
        $this->form['type'] = '';
        $this->resetValidation();
        $this->notify('info', gp247_language_render('admin.store.store_changed_notice'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return array_merge([
            'form.name' => ['required', 'string', 'max:200'],
            'form.url' => ['nullable', 'string', 'max:255'],
            'form.type' => ['nullable', 'string', 'max:255'],
            'form.target' => ['required', 'in:_self,_blank'],
            'form.sort' => ['required', 'numeric', 'min:0'],
        ], $this->storeScopeCreateRules());
    }

    /**
     * Localised validator messages (store-required on scoped create).
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return $this->storeScopeMessages();
    }

    /**
     * Reuse the existing v1 banner label keys for validator attributes.
     *
     * @return array<string, string>
     */
    protected function attributeLabels(): array
    {
        return [
            'form.name' => 'admin.banner.title',
            'form.url' => 'admin.banner.url',
            'form.type' => 'admin.banner.type',
            'form.target' => 'admin.banner.target',
            'form.sort' => 'admin.sort',
        ];
    }

    /**
     * @param array<string, mixed> $data Sanitised form values.
     * @return void
     */
    protected function persist(array $data): void
    {
        // WHY: 1-1 ownership — the type dropdown is already store-scoped, but a
        // crafted Livewire payload could still submit another store's type code,
        // so reject a cross-store reference server-side (RISK-TECH-store-same-store-ref).
        $this->assertSameStoreType($data);

        $attributes = [
            'image' => $data['image'] ?? '',
            'url' => $data['url'] ?? '',
            'name' => $data['name'],
            'html' => $data['html'] ?? '',
            'type' => $data['type'] ?? '',
            'target' => $data['target'] ?? '_self',
            'sort' => (int) ($data['sort'] ?? 0),
            'status' => empty($data['status']) ? 0 : 1,
        ];

        if ($this->editingId !== null) {
            // Store is immutable on edit — do NOT touch store_id (ADR 1-1).
            $banner = FrontBanner::findOrFail($this->editingId);
            $banner->update($attributes);
        } else {
            // WHY: 1-1 ownership — a new banner is owned by the store picked on create
            // (root admin) or the current scoped store (store-admin / switcher).
            $attributes['store_id'] = $this->resolveCreateStore();
            $banner = FrontBanner::create($attributes);
        }

        // Store ownership is set on the banner row (store_id) above.
    }

    /**
     * Reject a save whose banner-type code belongs to another store. An empty
     * type passes (no type selected); only a non-empty code that does not resolve
     * to a type owned by the record's store is rejected.
     *
     * @param array<string, mixed> $data Sanitised form.
     * @return void
     * @throws \Illuminate\Validation\ValidationException When the type is cross-store.
     *
     * @aidlc-adr multi-store_one-to-one-store-ownership
     */
    private function assertSameStoreType(array $data): void
    {
        $code = (string) ($data['type'] ?? '');
        if ($code === '') {
            return;
        }

        // The record's store: picked store on create, own store on edit (immutable).
        $exists = FrontBannerType::where('code', $code)
            ->where('store_id', $this->currentStore())
            ->exists();

        if (!$exists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'form.type' => gp247_language_render('admin.banner.type') . ': invalid store reference',
            ]);
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
        $this->redirectRoute('admin_banner.index', navigate: true);
    }

    /**
     * @return array{name: string, url: string}
     */
    protected function listCrumb(): array
    {
        return ['name' => gp247_language_render('admin.banner.title'), 'url' => route('admin_banner.index')];
    }

    /**
     * @return View
     */
    public function render(): View
    {
        // WHY: 1-1 ownership — only offer the record's store banner types so an admin
        // cannot pick another store's type (RISK-TECH-store-same-store-ref). On create
        // at root before a store is picked, currentStore() is null → empty options
        // (the store picker gates the rest of the form).
        $store = $this->currentStore();
        $noStore = $this->storeScopeActive() && ($store === null || $store === '');

        return view('gp247-front-admin::banner-form', [
            'types' => $noStore ? collect() : FrontBannerType::where('store_id', $store)->orderBy('name')->get(),
        ])->layout('gp247-admin::layouts.admin', [
            'title' => gp247_language_render($this->editingId !== null ? 'action.edit' : 'admin.banner.add_new'),
            'breadcrumb' => $this->listCrumb(),
        ]);
    }
}
