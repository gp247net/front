<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\DataTableComponent;
use GP247\Core\AdminShell\Infrastructure\HasStoreScopeUi;
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
    use HasStoreScopeUi;

    protected ?string $permission = 'admin_link';

    protected ?string $titleKey = 'admin.link_group.title';

    /**
     * Opt into store scoping (filter the list by store, label each row by its store).
     *
     * @return bool
     */
    protected function storeScopeOptIn(): bool
    {
        return true;
    }

    /**
     * @return FrontLinkGroup
     */
    protected function query()
    {
        return new FrontLinkGroup();
    }

    /**
     * Eager-load the owning store so the list can label each row without an N+1.
     *
     * @return array<int, string>
     */
    protected function relations(): array
    {
        return ['store'];
    }

    /**
     * Store-scoped list: root admin shows every store's link groups; a scoped context
     * or single-store install filters to the own store.
     *
     * @param mixed $query
     * @return void
     */
    protected function constrain($query): void
    {
        if (!($this->storeScopeActive() && $this->isRootScope())) {
            $query->where('store_id', $this->storeContext());
        }
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
