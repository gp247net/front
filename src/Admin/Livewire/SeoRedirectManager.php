<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\ResourcePanel;
use GP247\Front\Models\FrontRedirect;
use Illuminate\Validation\Rule;

/**
 * "Redirect 301" admin screen — two-panel screen (form left, list right) on the
 * shared core ResourcePanel base (ADR-005). Third screen under the "SEO" menu
 * group, cạnh "Meta & JSON-LD" (`SeoMetaSettings`) / "Sitemap.xml"
 * (`SeoSitemapSettings`) — same split-permission pattern (modification
 * `20260711T154553`). CRUD on `FrontRedirect`/`gp247_front_redirects`
 * (model + `FrontRedirectMiddleware` already existed from the original
 * code-gen, US-SEO-006, but had no admin UI until this screen). Gated by
 * `admin_seo_redirect`.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-006
 * @aidlc-adr ADR-001, ADR-005, ADR-006, ADR-007
 */
class SeoRedirectManager extends ResourcePanel
{
    protected ?string $permission = 'admin_seo_redirect';

    /**
     * Scope to the store `FrontRedirectMiddleware`/`FrontRedirect::findActive()`
     * resolve at request time (`config('app.storeId')`) — mirrors
     * `SeoMetaSettings::storeId()`. Using a different key (e.g.
     * `session('adminStoreId')`) would let an admin save a rule that never
     * matches the live site.
     *
     * @return mixed Store UUID.
     */
    private function storeId()
    {
        return config('app.storeId');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function baseQuery()
    {
        return FrontRedirect::query()->where('store_id', $this->storeId());
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['from', 'to'];
    }

    /**
     * @return array<int, string>
     */
    protected function sortableColumns(): array
    {
        return ['from', 'to', 'code', 'status'];
    }

    /**
     * @return string
     */
    protected function panelView(): string
    {
        return 'gp247-front-admin::seo-redirect-manager';
    }

    /**
     * @return string
     */
    protected function pageTitle(): string
    {
        return gp247_language_render('admin.seo_redirect.title');
    }

    /**
     * @return string
     */
    protected function baseRoute(): string
    {
        return 'admin_seo_redirect.index';
    }

    /**
     * @return array<string, mixed>
     */
    protected function formDefaults(): array
    {
        return ['from' => '', 'to' => '', 'code' => 301, 'status' => 1];
    }

    /**
     * @param FrontRedirect $model
     * @return array<string, mixed>
     */
    protected function fillForm($model): array
    {
        return [
            'from'   => (string) $model->from,
            'to'     => (string) $model->to,
            'code'   => (int) $model->code,
            'status' => (int) $model->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            // WHY: FrontRedirectMiddleware matches '/' . ltrim($pathInfo, '/') — a
            // 'from' not starting with '/' could never match a live request.
            'form.from' => [
                'required',
                'string',
                'max:500',
                'regex:/^\//',
                Rule::unique((new FrontRedirect())->getTable(), 'from')
                    ->where('store_id', $this->storeId())
                    ->ignore($this->editingId),
                // RISK-OPS-008: block direct self-redirect (from === to).
                Rule::notIn([$this->form['to'] ?? '']),
            ],
            'form.to'   => ['required', 'string', 'max:500'],
            'form.code' => ['required', 'integer', 'in:301,302'],
        ];
    }

    /**
     * @param array<string, mixed> $data Sanitised form values.
     * @return void
     */
    protected function persist(array $data): void
    {
        $attributes = [
            'from'     => $data['from'],
            'to'       => $data['to'],
            'code'     => (int) $data['code'],
            'status'   => empty($data['status']) ? 0 : 1,
            'store_id' => $this->storeId(),
        ];

        if ($this->editingId !== null) {
            FrontRedirect::where('id', $this->editingId)->update($attributes);
        } else {
            FrontRedirect::create($attributes);
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
