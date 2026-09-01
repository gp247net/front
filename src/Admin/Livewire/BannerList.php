<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\DataTableComponent;
use GP247\Core\AdminShell\Infrastructure\HasStoreScopeUi;
use GP247\Front\Models\FrontBanner;

/**
 * Banner list (front-admin Unit) — modern Livewire/TailAdmin port of the legacy
 * AdminBannerController list: image thumb, title, url, type, sort and on/off
 * status, with Edit/Delete + bulk delete. Plugs into the core admin shell and
 * reuses the core DataTable base. Store ownership is 1-1 (scalar store_id).
 * Gated by `admin_banner`.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-001
 * @aidlc-adr ADR-001, ADR-006, ADR-007, multi-store_one-to-one-store-ownership
 */
class BannerList extends DataTableComponent
{
    use HasStoreScopeUi;

    protected ?string $permission = 'admin_banner';

    protected ?string $titleKey = 'admin.banner.title';

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
     * @return FrontBanner
     */
    protected function query()
    {
        return new FrontBanner();
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
     * Store-scoped list: root admin shows every store's banners (each labelled by
     * its store); a scoped context (store-admin/switcher) or single-store install
     * filters to the own store.
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
     * Sortable columns; doubles as the sort whitelist.
     *
     * @return array<string, string>
     */
    protected function columns(): array
    {
        return [
            'name' => 'Title',
            'type' => 'Type',
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
        return 'gp247-front-admin::banner-list';
    }
}
