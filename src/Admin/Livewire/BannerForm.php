<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\FormComponent;
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

    protected ?string $permission = 'admin_banner';

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

        if ($id !== null) {
            $banner = FrontBanner::findOrFail($id);
            $this->editingId = (string) $banner->id;
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
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:200'],
            'form.url' => ['nullable', 'string', 'max:255'],
            'form.type' => ['nullable', 'string', 'max:255'],
            'form.target' => ['required', 'in:_self,_blank'],
            'form.sort' => ['required', 'numeric', 'min:0'],
        ];
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
            $banner = FrontBanner::findOrFail($this->editingId);
            $banner->update($attributes);
        } else {
            // WHY: 1-1 ownership — a new banner is owned by the current admin store
            // (pinned to root in admin); set its scalar store_id on create.
            $attributes['store_id'] = $this->currentStoreId();
            $banner = FrontBanner::create($attributes);
        }

        // Store ownership is set on the banner row (store_id) above.
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
     * Reject a save whose banner-type code belongs to another store. An empty
     * type passes (no type selected); only a non-empty code that does not resolve
     * to a type owned by the current store is rejected.
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

        $exists = FrontBannerType::where('code', $code)
            ->where('store_id', $this->currentStoreId())
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
        // WHY: 1-1 ownership — a banner has a single owning store (pinned to the
        // current admin store), so no multi-store picker context is injected.
        return view('gp247-front-admin::banner-form', [
            // WHY: 1-1 ownership — only offer banner types owned by the current store
            // so an admin cannot pick another store's type (RISK-TECH-store-same-store-ref).
            'types' => FrontBannerType::where('store_id', $this->currentStoreId())->orderBy('name')->get(),
        ])->layout('gp247-admin::layouts.admin', [
            'title' => gp247_language_render($this->editingId !== null ? 'action.edit' : 'admin.banner.add_new'),
            'breadcrumb' => $this->listCrumb(),
        ]);
    }
}
