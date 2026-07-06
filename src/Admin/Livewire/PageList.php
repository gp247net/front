<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\DataTableComponent;
use GP247\Front\Models\FrontPage;

/**
 * Page (CMS) list (front-admin Unit) — modern Livewire/TailAdmin port of the
 * legacy AdminPageController list: image thumb, multilingual title (from the
 * description of the current locale), alias and on/off status, with Edit/Delete
 * + bulk delete. Plugs into the core admin shell and reuses the core DataTable
 * base. Gated by `admin_page`.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-002
 * @aidlc-adr ADR-006, ADR-007
 */
class PageList extends DataTableComponent
{
    protected ?string $permission = 'admin_page';

    protected ?string $titleKey = 'admin.page.title';

    /**
     * @return FrontPage
     */
    protected function query()
    {
        return new FrontPage();
    }

    /**
     * Eager-load descriptions so the per-locale title resolves without N+1.
     *
     * @return array<int, string>
     */
    protected function relations(): array
    {
        return ['descriptions'];
    }

    /**
     * Sortable columns; doubles as the sort whitelist.
     *
     * @return array<string, string>
     */
    protected function columns(): array
    {
        return [
            'alias' => 'Alias',
            'status' => 'Status',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['alias'];
    }

    /**
     * @return string
     */
    protected function listView(): string
    {
        return 'gp247-front-admin::page-list';
    }
}
