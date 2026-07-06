<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\DataTableComponent;
use GP247\Front\Models\FrontLayoutBlock;

/**
 * Layout block list (front-admin Unit) — modern Livewire/TailAdmin port of the
 * legacy AdminLayoutBlockController list: name, type, position, sort and on/off
 * status, with Edit/Delete + bulk delete. Plugs into the core admin shell and
 * reuses the core DataTable base. Gated by `admin_layout_block`.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-004
 * @aidlc-adr ADR-006, ADR-007
 */
class LayoutBlockList extends DataTableComponent
{
    protected ?string $permission = 'admin_layout_block';

    protected ?string $titleKey = 'admin.layout_block.title';

    /**
     * @return FrontLayoutBlock
     */
    protected function query()
    {
        return new FrontLayoutBlock();
    }

    /**
     * Sortable columns; doubles as the sort whitelist.
     *
     * @return array<string, string>
     */
    protected function columns(): array
    {
        return [
            'name' => 'Name',
            'type' => 'Type',
            'position' => 'Position',
            'sort' => 'Sort',
            'status' => 'Status',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['name'];
    }

    /**
     * Default to the brownfield ordering (sort asc).
     *
     * @return array{0: string, 1: string}
     */
    protected function defaultSort(): array
    {
        return ['sort', 'asc'];
    }

    /**
     * @return string
     */
    protected function listView(): string
    {
        return 'gp247-front-admin::layout-block-list';
    }
}
