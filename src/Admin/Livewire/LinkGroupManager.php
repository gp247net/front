<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\HasValidationLabels;
use GP247\Core\AdminShell\Infrastructure\ResourcePanel;
use GP247\Front\Models\FrontLinkGroup;
use Illuminate\Validation\Rule;

/**
 * Link-group manager (front-admin Unit) — two-panel screen (add/edit form left,
 * list right) on the shared core ResourcePanel base. Code (unique,
 * url-formatted) + name. Replaces the separate LinkGroupForm + LinkGroupList
 * pair. Gated by `admin_link`.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-003
 * @aidlc-adr ADR-001, ADR-005, ADR-006, ADR-007
 */
class LinkGroupManager extends ResourcePanel
{
    use HasValidationLabels;

    protected ?string $permission = 'admin_link';

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function baseQuery()
    {
        return FrontLinkGroup::query();
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['code', 'name'];
    }

    /**
     * @return array<int, string>
     */
    protected function sortableColumns(): array
    {
        return ['code', 'name'];
    }

    /**
     * @return string
     */
    protected function panelView(): string
    {
        return 'gp247-front-admin::link-group-manager';
    }

    /**
     * @return string
     */
    protected function pageTitle(): string
    {
        return gp247_language_render('admin.link_group.title');
    }

    /**
     * @return string
     */
    protected function baseRoute(): string
    {
        return 'admin_link_group.index';
    }

    /**
     * @return array<string, mixed>
     */
    protected function formDefaults(): array
    {
        return ['code' => '', 'name' => ''];
    }

    /**
     * @param FrontLinkGroup $model
     * @return array<string, mixed>
     */
    protected function fillForm($model): array
    {
        return [
            'code' => (string) $model->code,
            'name' => (string) $model->name,
        ];
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
        } else {
            FrontLinkGroup::create($attributes);
        }
    }

    /**
     * @param int|string $id
     * @return void
     */
    protected function deleteModel($id): void
    {
        $model = $this->baseQuery()->find($id);
        if ($model !== null) {
            $model->delete();
        }
    }
}
