<?php

namespace GP247\Front\Tests\Feature\Seo\Support;

use GP247\Core\Models\AdminConfig;

/**
 * Set per-store SEO switches in admin_config (rolled back by DatabaseTransactions)
 * and drop the static config cache so the next read sees them.
 *
 * @aidlc-unit seo
 */
trait ConfiguresSeoStore
{
    /**
     * @param string          $key     admin_config key (e.g. seo.log_404).
     * @param string          $value
     * @param int|string|null $storeId Null = current store.
     */
    private function setSeoConfig(string $key, string $value, $storeId = null): void
    {
        AdminConfig::updateOrCreate(
            ['key' => $key, 'store_id' => $storeId ?? config('app.storeId')],
            ['group' => 'seo', 'code' => 'seo_meta_settings', 'value' => $value, 'sort' => 0, 'detail' => '']
        );
        $this->resetSeoConfigCache();
    }

    private function resetSeoConfigCache(): void
    {
        (new \ReflectionProperty(AdminConfig::class, 'getAllConfigOfStore'))->setValue(null, []);
    }
}
