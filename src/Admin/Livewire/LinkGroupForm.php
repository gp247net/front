<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\FormComponent;
use GP247\Core\AdminShell\Infrastructure\HasValidationLabels;
use GP247\Front\Models\FrontLinkGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;

/**
 * Link-group create/edit form (front-admin Unit) — modern port of the legacy
 * AdminLinkGroupController create/edit. Code (unique, url-formatted) + name.
 * Gated by `admin_link`.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-003
 * @aidlc-adr ADR-001, ADR-006, ADR-007
 */
class LinkGroupForm extends FormComponent
{
    use HasValidationLabels;

    protected ?string $permission = 'admin_link';

    /** @var array<string, mixed> */
    public array $form = [
        'code' => '',
        'name' => '',
    ];

    /**
     * @param string|null $id Link-group id to edit; null to create.
     * @return void
     */
    public function mount(?string $id = null): void
    {
        parent::mount();

        if ($id !== null) {
            $row = FrontLinkGroup::findOrFail($id);
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
                Rule::unique((new FrontLinkGroup())->getTable(), 'code')->ignore($this->editingId),
            ],
        ];
    }

    /**
     * Reuse the existing v1 link-group label keys for validator attributes.
     *
     * @return array<string, string>
     */
    protected function attributeLabels(): array
    {
        return [
            'form.name' => 'admin.link_group.name',
            'form.code' => 'admin.link_group.code',
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
            FrontLinkGroup::where('id', $this->editingId)->update($attributes);

            return;
        }

        FrontLinkGroup::create($attributes);
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
        $this->redirectRoute('admin_link_group.index', navigate: true);
    }

    /**
     * @return array{name: string, url: string}
     */
    protected function listCrumb(): array
    {
        return ['name' => gp247_language_render('admin.link_group.title'), 'url' => route('admin_link_group.index')];
    }

    /**
     * @return View
     */
    public function render(): View
    {
        return view('gp247-front-admin::link-group-form')->layout('gp247-admin::layouts.admin', [
            'title' => gp247_language_render($this->editingId !== null ? 'action.edit' : 'admin.link_group.add_new'),
            'breadcrumb' => $this->listCrumb(),
        ]);
    }
}
