<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\FormComponent;
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

    protected ?string $permission = 'admin_banner';

    /** @var array<string, mixed> */
    public array $form = [
        'code' => '',
        'name' => '',
    ];

    /**
     * @param string|null $id Banner-type id to edit; null to create.
     * @return void
     */
    public function mount(?string $id = null): void
    {
        parent::mount();

        if ($id !== null) {
            $row = FrontBannerType::findOrFail($id);
            $this->editingId = (string) $row->id;
            $this->form = [
                'code' => $row->code,
                'name' => $row->name,
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.code' => [
                'required',
                'string',
                'max:100',
                Rule::unique((new FrontBannerType())->getTable(), 'code')->ignore($this->editingId),
            ],
        ];
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
            FrontBannerType::where('id', $this->editingId)->update($attributes);

            return;
        }

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
