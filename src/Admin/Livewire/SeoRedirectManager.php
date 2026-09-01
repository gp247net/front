<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\HasValidationLabels;
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
    use HasValidationLabels;

    protected ?string $permission = 'admin_seo_redirect';

    /**
     * Keep list state (page/keyword/sort) and the edited record on screen when
     * editing/saving, instead of remounting via route navigation.
     *
     * @var bool
     * @aidlc-story US-AUI-two-panel-state-preservation
     * @aidlc-adr ADR-admin-shell-rbac-two-panel-state-preservation
     */
    protected bool $keepStateOnSave = true;

    /**
     * Store-scoped: pick a store on create (root admin), show it in the list, lock
     * it on edit. Redirect is a leaf entity (from/to are path strings).
     *
     * WHY the store matters here: `FrontRedirectMiddleware`/`FrontRedirect::findActive()`
     * match by the request-time store (`config('app.storeId')`), which equals the store
     * whose domain is being served. Owning a redirect by the store picked on create
     * (root admin) or the current scoped store therefore targets exactly that store's
     * live domain.
     *
     * @return array<string, mixed>|null
     *
     * @aidlc-unit seo
     * @aidlc-story US-SADM-store-content-assignment
     * @aidlc-adr admin-shell_store-scoped-resource-panel
     */
    protected function storeScoped(): ?array
    {
        return ['display' => 'from', 'reset' => []];
    }

    /**
     * Store-scoped redirect query: root admin shows every store's redirects; a scoped
     * context (store-admin/switcher) or single-store install filters to the own store.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function baseQuery()
    {
        $query = FrontRedirect::query();
        if (!($this->storeScopeActive() && $this->isRootScope())) {
            $query->where('store_id', $this->storeContext());
        }

        return $query;
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
        // Store is immutable on edit — expose it for the read-only display.
        $this->formStoreId = (string) $model->store_id;

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
                    ->where('store_id', $this->currentStore())
                    ->ignore($this->editingId),
                // RISK-OPS-008: block direct self-redirect (from === to).
                Rule::notIn([$this->form['to'] ?? '']),
            ],
            'form.to'   => ['required', 'string', 'max:500'],
            'form.code' => ['required', 'integer', 'in:301,302'],
        ];
    }

    /**
     * Reuse the existing v1 SEO-redirect label keys for validator attributes.
     *
     * @return array<string, string>
     */
    protected function attributeLabels(): array
    {
        return [
            'form.from' => 'admin.seo_redirect.from',
            'form.to' => 'admin.seo_redirect.to',
            'form.code' => 'admin.seo_redirect.code',
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
        ];

        if ($this->editingId !== null) {
            // Store is immutable on edit — do NOT touch store_id (ADR 1-1).
            FrontRedirect::where('id', $this->editingId)->update($attributes);
        } else {
            // WHY: 1-1 ownership — a new redirect is owned by the store picked on
            // create (root admin) or the current scoped store (store-admin/switcher),
            // i.e. the store whose live domain the rule will match.
            $attributes['store_id'] = $this->resolveCreateStore();
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
