<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\ResourcePanel;
use GP247\Front\Models\FrontLayoutBlock;
use GP247\Front\Models\FrontPage;
use Illuminate\Contracts\View\View;

/**
 * Layout block manager — two-panel screen (form left, list right) following the
 * ResourcePanel pattern (ADR-005, ADR-007, ui-tailadmin P1). Replaces the separate
 * LayoutBlockList + LayoutBlockForm pair. Gated by `admin_layout_block`.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-004
 * @aidlc-adr ADR-001, ADR-005, ADR-006, ADR-007
 */
class LayoutBlockManager extends ResourcePanel
{
    protected ?string $permission = 'admin_layout_block';

    /**
     * `text` carries admin-authored rich HTML (TinyMCE, type=html) or a raw view
     * name (type=view) — either way it must survive save() unescaped. See
     * ResourcePanel::$richFields.
     *
     * @var array<int, string>
     */
    protected array $richFields = ['text'];

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function baseQuery()
    {
        return FrontLayoutBlock::query();
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['name'];
    }

    /**
     * @return array<int, string>
     */
    protected function sortableColumns(): array
    {
        return ['name', 'type', 'position', 'sort', 'status'];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function defaultSort(): array
    {
        return ['sort', 'asc'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function formDefaults(): array
    {
        return [
            'name'     => '',
            'position' => '',
            'page'     => [],
            'text'     => '',
            'type'     => 'html',
            'sort'     => 0,
            'status'   => 1,
        ];
    }

    /**
     * @param FrontLayoutBlock $model
     * @return array<string, mixed>
     */
    protected function fillForm($model): array
    {
        return [
            'name'     => (string) $model->name,
            'position' => (string) $model->position,
            'page'     => $model->page === '*' ? ['*'] : array_values(array_filter(explode(',', (string) $model->page))),
            'text'     => (string) $model->text,
            'type'     => $model->type ?: 'html',
            'sort'     => (int) $model->sort,
            'status'   => (int) $model->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'form.name'     => ['required', 'string', 'max:255'],
            'form.position' => ['required', 'string', 'max:255'],
            'form.page'     => ['required', 'array', 'min:1'],
            'form.text'     => ['required', 'string'],
            'form.type'     => ['required', 'in:html,view,page'],
            'form.sort'     => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    protected function persist(array $data): void
    {
        $attributes = [
            'name'      => $data['name'],
            'position'  => $data['position'],
            'page'      => in_array('*', (array) ($data['page'] ?? [])) ? '*' : implode(',', (array) ($data['page'] ?? [])),
            'text'      => $data['text'],
            'type'      => $data['type'],
            'sort'      => (int) ($data['sort'] ?? 0),
            'status'    => empty($data['status']) ? 0 : 1,
            // WHY: mirror legacy store binding — scope to active admin store or root.
            'store_id'  => session('adminStoreId') ?? 0,
            // WHY: NOT NULL with no default; bind the active store's template.
            'template'  => function_exists('gp247_store_info') ? (string) gp247_store_info('template') : '',
        ];

        if ($this->editingId !== null) {
            FrontLayoutBlock::findOrFail($this->editingId)->update($attributes);
        } else {
            FrontLayoutBlock::create($attributes);
        }
    }

    /**
     * @param int|string $id
     * @return void
     */
    protected function deleteModel($id): void
    {
        $model = FrontLayoutBlock::find($id);
        if ($model !== null) {
            $model->delete();
        }
    }

    /**
     * Set block type and reset text to avoid stale content across type switches.
     *
     * @param string $type One of html|view|page.
     * @return void
     */
    public function selectType(string $type): void
    {
        $this->form['type'] = in_array($type, ['html', 'view', 'page'], true) ? $type : 'html';
        $this->form['text'] = '';
    }

    /**
     * @return string
     */
    protected function panelView(): string
    {
        return 'gp247-front-admin::layout-block-manager';
    }

    /**
     * @return string
     */
    protected function pageTitle(): string
    {
        return gp247_language_render('admin.layout_block.title');
    }

    /**
     * @return string
     */
    protected function baseRoute(): string
    {
        return 'admin_layout_block.index';
    }

    /**
     * @return array<string, string>
     */
    protected function getListViewBlock(): array
    {
        $storeId = session('adminStoreId');
        $template = function_exists('gp247_store_info') ? (string) gp247_store_info(key: 'template', storeId: $storeId) : '';
        $arrView = [];
        foreach (glob(app_path() . '/GP247/Templates/' . $template . '/blocks/*.blade.php') ?: [] as $file) {
            $name = substr(basename($file), 0, -10);
            $arrView[$name] = $name;
        }
        return $arrView;
    }

    /**
     * @return array<int, string>
     */
    protected function getListPageBlock(): array
    {
        return (new FrontPage)->getListPageAlias(session('adminStoreId'));
    }

    /**
     * @return array<int, array{id: string, label: string}>
     */
    protected function positionOptions(): array
    {
        $opts = [];
        foreach (config('gp247-config.front.layout_position', []) as $code => $langKey) {
            $opts[] = ['id' => (string) $code, 'label' => gp247_language_render($langKey)];
        }
        return $opts;
    }

    /**
     * @return array<int, array{id: string, label: string}>
     */
    protected function pageOptions(): array
    {
        $opts = [['id' => '*', 'label' => gp247_language_render('admin.layout_block_page.all')]];
        foreach ($this->getListPageBlock() as $alias) {
            $opts[] = ['id' => $alias, 'label' => $alias];
        }
        return $opts;
    }

    /**
     * @return array<int, array{id: string, label: string}>
     */
    protected function pageViewOptions(): array
    {
        return array_map(fn($alias) => ['id' => $alias, 'label' => $alias], $this->getListPageBlock());
    }

    /**
     * @return View
     */
    public function render(): View
    {
        return view($this->panelView(), [
            'rows'            => $this->rows(),
            'types'           => ['html' => 'Html', 'view' => 'View', 'page' => 'Page'],
            'positionOptions' => $this->positionOptions(),
            'viewBlocks'      => $this->getListViewBlock(),
            'pageOptions'     => $this->pageOptions(),
            'pageViewOptions' => $this->pageViewOptions(),
        ])->layout('gp247-admin::layouts.admin', ['title' => $this->pageTitle()]);
    }
}
