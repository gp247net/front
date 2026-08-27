<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\DataTableComponent;
use GP247\Front\Models\FrontBanner;

/**
 * Banner list (front-admin Unit) — modern Livewire/TailAdmin port of the legacy
 * AdminBannerController list: image thumb, title, url, type, sort and on/off
 * status, with Edit/Delete + bulk delete. Plugs into the core admin shell and
 * reuses the core DataTable base. Gated by `admin_banner`.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-001
 * @aidlc-adr ADR-001, ADR-006, ADR-007
 */
class BannerList extends DataTableComponent
{
    protected ?string $permission = 'admin_banner';

    protected ?string $titleKey = 'admin.banner.title';

    /**
     * @return FrontBanner
     */
    protected function query()
    {
        return new FrontBanner();
    }

    /**
     * Eager-load stores to display per-row store badges without N+1.
     *
     * @return array<int, string>
     */
    protected function relations(): array
    {
        return ['stores.descriptions'];
    }

    /**
     * Pass multi-store flag to the list view so store badges can be rendered.
     *
     * @return array<string, mixed>
     */
    protected function viewData(): array
    {
        return [
            'multiStore' => gp247_store_check_multi_partner_installed() || gp247_store_check_multi_store_installed(),
        ];
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
