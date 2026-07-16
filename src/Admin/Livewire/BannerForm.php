<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\FormComponent;
use GP247\Front\Models\FrontBanner;
use GP247\Front\Models\FrontBannerType;
use Illuminate\Contracts\View\View;

/**
 * Banner create/edit form (front-admin Unit) — modern port of the legacy
 * AdminBannerController create/edit: image (LFM), url, title, html (rich text),
 * type (banner-type), target, sort and status, plus multi-store assignment when
 * multistore/partner is installed. Domain unchanged (FrontBanner). Gated by
 * `admin_banner`.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-001
 * @aidlc-adr ADR-001, ADR-006, ADR-007
 */
class BannerForm extends FormComponent
{
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

    /** @var array<int, int> Store ids assigned to the banner (multistore). */
    public array $stores = [];

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
            $this->stores = $banner->stores()->pluck('store_id')->map(static fn ($v): int => (int) $v)->all();
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
     * @param array<string, mixed> $data Sanitised form values.
     * @return void
     */
    protected function persist(array $data): void
    {
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
            $banner = FrontBanner::create($attributes);
        }

        // WHY: only sync the store pivot when multistore/partner is active, so a
        // single-store install behaves exactly like the legacy screen (no pivot).
        if (gp247_store_check_multi_partner_installed() || gp247_store_check_multi_store_installed()) {
            $banner->stores()->sync($this->stores);
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
        $multiStore = gp247_store_check_multi_partner_installed() || gp247_store_check_multi_store_installed();

        return view('gp247-front-admin::banner-form', [
            'types' => FrontBannerType::orderBy('name')->get(),
            'multiStore' => $multiStore,
            'storeList' => $multiStore ? \GP247\Core\Models\AdminStore::pluck('name', 'id')->all() : [],
        ])->layout('gp247-admin::layouts.admin', [
            'title' => gp247_language_render($this->editingId !== null ? 'action.edit' : 'admin.banner.add_new'),
            'breadcrumb' => $this->listCrumb(),
        ]);
    }
}
