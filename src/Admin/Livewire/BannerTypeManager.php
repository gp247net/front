<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\ResourcePanel;
use GP247\Front\Models\FrontBannerType;
use Illuminate\Validation\Rule;

/**
 * Banner-type manager (front-admin Unit) — two-panel screen (add/edit form left,
 * list right) on the shared core ResourcePanel base, matching the GP247 admin
 * pattern (rule ui-tailadmin P1). Code (unique, url-formatted) + name. Replaces
 * the separate BannerTypeForm + BannerTypeList pair. Gated by `admin_banner`.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-001
 * @aidlc-adr ADR-001, ADR-005, ADR-006, ADR-007
 */
class BannerTypeManager extends ResourcePanel
{
    protected ?string $permission = 'admin_banner';

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function baseQuery()
    {
        return FrontBannerType::query();
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
        return 'gp247-front-admin::banner-type-manager';
    }

    /**
     * @return string
     */
    protected function pageTitle(): string
    {
        return gp247_language_render('admin.banner_type.title');
    }

    /**
     * @return string
     */
    protected function baseRoute(): string
    {
        return 'admin_banner_type.index';
    }

    /**
     * @return array<string, mixed>
     */
    protected function formDefaults(): array
    {
        return ['code' => '', 'name' => ''];
    }

    /**
     * @param FrontBannerType $model
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
                Rule::unique((new FrontBannerType())->getTable(), 'code')->ignore($this->editingId),
            ],
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
        } else {
            FrontBannerType::create($attributes);
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
