<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\DataTableComponent;
use GP247\Front\Models\FrontLink;

/**
 * Link list (front-admin Unit) — modern Livewire/TailAdmin port of the legacy
 * AdminLinkController list: name, group, sort and on/off status, with Edit/Delete
 * + bulk delete. Plugs into the core admin shell and reuses the core DataTable
 * base. Gated by `admin_link`.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-003
 * @aidlc-adr ADR-001, ADR-006, ADR-007
 */
class LinkList extends DataTableComponent
{
    protected ?string $permission = 'admin_link';

    protected ?string $titleKey = 'admin.link.title';

    /**
     * @return FrontLink
     */
    protected function query()
    {
        return new FrontLink();
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
            'group' => 'Group',
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
        return 'gp247-front-admin::link-list';
    }
}
