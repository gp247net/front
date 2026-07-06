<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\DataTableComponent;
use GP247\Front\Models\FrontLinkGroup;

/**
 * Link-group list (front-admin Unit) — the modern Livewire/TailAdmin port of the
 * legacy AdminLinkGroupController list. Code + name, with Edit/Delete and bulk
 * delete. Plugs into the core admin shell; reuses the core DataTable base. Gated
 * by `admin_link` (shared with the link module).
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-003
 * @aidlc-adr ADR-001, ADR-006, ADR-007
 */
class LinkGroupList extends DataTableComponent
{
    protected ?string $permission = 'admin_link';

    protected ?string $titleKey = 'admin.link_group.title';

    /**
     * @return FrontLinkGroup
     */
    protected function query()
    {
        return new FrontLinkGroup();
    }

    /**
     * @return array<string, string>
     */
    protected function columns(): array
    {
        return [
            'code' => 'Code',
            'name' => 'Name',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['code', 'name'];
    }

    /**
     * @return string
     */
    protected function listView(): string
    {
        return 'gp247-front-admin::link-group-list';
    }
}
