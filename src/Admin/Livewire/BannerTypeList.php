<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\DataTableComponent;
use GP247\Core\AdminShell\Infrastructure\HasStoreScopeUi;
use GP247\Front\Models\FrontBannerType;

/**
 * Banner-type list (front-admin Unit) — the modern Livewire/TailAdmin port of the
 * legacy AdminBannerTypeController list. Code + name, with Edit/Delete and bulk
 * delete. Plugs into the core admin shell; reuses the core DataTable base. Gated
 * by `admin_banner` (shared with the banner module).
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-001
 * @aidlc-adr ADR-001, ADR-006, ADR-007
 */
class BannerTypeList extends DataTableComponent
{
    use HasStoreScopeUi;

    protected ?string $permission = 'admin_banner';

    protected ?string $titleKey = 'admin.banner_type.title';

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
     * @return FrontBannerType
     */
    protected function query()
    {
        return new FrontBannerType();
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
     * Store-scoped list: root admin shows every store's banner types; a scoped
     * context or single-store install filters to the own store.
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
        return 'gp247-front-admin::banner-type-list';
    }
}
