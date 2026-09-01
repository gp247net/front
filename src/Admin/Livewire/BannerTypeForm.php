<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\FormComponent;
use GP247\Core\AdminShell\Infrastructure\HasStoreScopeUi;
use GP247\Core\AdminShell\Infrastructure\HasValidationLabels;
use GP247\Front\Models\FrontBannerType;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;

/**
 * Banner-type create/edit form (front-admin Unit) — modern port of the legacy
 * AdminBannerTypeController create/edit. Code (unique, url-formatted) + name.
 * Gated by `admin_banner`.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-001
 * @aidlc-adr ADR-001, ADR-006, ADR-007
 */
class BannerTypeForm extends FormComponent
{
    use HasValidationLabels;
    use HasStoreScopeUi;

    protected ?string $permission = 'admin_banner';

    /** @var array<string, mixed> */
    public array $form = [
        'code' => '',
        'name' => '',
    ];

    /**
     * Opt into store scoping (pick a store on create, lock on edit, scope the
     * unique code check to that store).
     *
     * @return bool
     */
    protected function storeScopeOptIn(): bool
    {
        return true;
    }

    /**
     * @param string|null $id Banner-type id to edit; null to create.
     * @return void
     */
    public function mount(?string $id = null): void
    {
        parent::mount();

        if ($id === null) {
            // Create: default the picker to the current context store (ROOT at root
            // admin) — parity with ResourcePanel::resetForm() so a store is always
            // set on create.
            if ($this->storeScopeActive()) {
                $this->formStoreId = (string) $this->storeContext();
            }

            return;
        }

        $row = FrontBannerType::findOrFail($id);
        $this->editingId = (string) $row->id;
        // Store is immutable on edit — expose it for the read-only display.
        $this->formStoreId = (string) $row->store_id;
        $this->form = [
            'code' => $row->code,
            'name' => $row->name,
        ];
    }

    /**
     * The store the form is bound to (for the per-store unique code check): on edit
     * the record's own store (DB, tamper-proof); on create at root the picked store
     * (null until chosen); otherwise the current context.
     *
     * @return int|string|null
     */
    private function currentStore()
    {
        if (!$this->storeScopeActive()) {
            return $this->storeContext();
        }
        if ($this->editingId !== null && $this->editingId !== '') {
            $recStore = FrontBannerType::whereKey($this->editingId)->value('store_id');

            return $recStore !== null ? $recStore : $this->storeContext();
        }
        if (!$this->isRootScope()) {
            return $this->storeContext();
        }

        return $this->formStoreId !== '' ? $this->formStoreId : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return array_merge([
            'form.name' => ['required', 'string', 'max:255'],
            'form.code' => [
                'required',
                'string',
                'max:100',
                // Code is unique per store (a new banner-type code may repeat across stores).
                Rule::unique((new FrontBannerType())->getTable(), 'code')
                    ->ignore($this->editingId)
                    ->where('store_id', $this->currentStore()),
            ],
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
     * Reuse the existing v1 banner-type label keys for validator attributes.
     *
     * @return array<string, string>
     */
    protected function attributeLabels(): array
    {
        return [
            'form.name' => 'admin.banner_type.name',
            'form.code' => 'admin.banner_type.code',
        ];
    }

    /**
     * @param array<string, mixed> $data Sanitised form values.
     * @return void
     */
    protected function persist(array $data): void
    {
        $attributes = [
            // WHY: mirror the legacy url-safe code normalization (gp247_word_format_url).
            'code' => mb_substr(gp247_word_format_url($data['code']), 0, 100),
            'name' => $data['name'],
        ];

        if ($this->editingId !== null) {
            // Store is immutable on edit — do NOT touch store_id (ADR 1-1).
            FrontBannerType::where('id', $this->editingId)->update($attributes);

            return;
        }

        // WHY: 1-1 ownership — a new banner type is owned by the store picked on
        // create (root admin) or the current scoped store (store-admin / switcher).
        $attributes['store_id'] = $this->resolveCreateStore();
        FrontBannerType::create($attributes);
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
        $this->redirectRoute('admin_banner_type.index', navigate: true);
    }

    /**
     * @return array{name: string, url: string}
     */
    protected function listCrumb(): array
    {
        return ['name' => gp247_language_render('admin.banner_type.title'), 'url' => route('admin_banner_type.index')];
    }

    /**
     * @return View
     */
    public function render(): View
    {
        return view('gp247-front-admin::banner-type-form')->layout('gp247-admin::layouts.admin', [
            'title' => gp247_language_render($this->editingId !== null ? 'action.edit' : 'admin.banner_type.add_new'),
            'breadcrumb' => $this->listCrumb(),
        ]);
    }
}
