<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\DataTableComponent;
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
    protected ?string $permission = 'admin_banner';

    protected ?string $titleKey = 'admin.banner_type.title';

    /**
     * @return FrontBannerType
     */
    protected function query()
    {
        return new FrontBannerType();
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
