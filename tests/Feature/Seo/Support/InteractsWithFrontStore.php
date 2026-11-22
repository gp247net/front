<?php

namespace GP247\Front\Tests\Feature\Seo\Support;

use GP247\Core\Models\AdminStore;

/**
 * Test helper that temporarily activates the front store so HTTP requests to
 * public front routes are not intercepted by CheckActive (maintenance mode).
 *
 * Usage: add `use InteractsWithFrontStore;` to any Feature test that hits
 * front public routes (sitemap, robots, page detail, redirects).
 *
 * @aidlc-unit seo
 */
trait InteractsWithFrontStore
{
    private bool $storeWasActive = false;
    private ?AdminStore $frontStore = null;

    /**
     * Activate the default store before each test.
     * Called automatically by PHPUnit setup if setUp() calls parent::setUp().
     */
    protected function setUpFrontStore(): void
    {
        $this->frontStore = AdminStore::find(config('app.storeId'))
            ?? AdminStore::first();

        if ($this->frontStore) {
            $this->storeWasActive = (bool) $this->frontStore->active;
            if (!$this->storeWasActive) {
                $this->frontStore->update(['active' => 1]);
                self::clearAdminStoreCache();
            }
        }
    }

    /**
     * Restore the store active flag after each test.
     */
    protected function tearDownFrontStore(): void
    {
        if ($this->frontStore && !$this->storeWasActive) {
            $this->frontStore->update(['active' => 0]);
            self::clearAdminStoreCache();
        }
    }

    /**
     * Reset AdminStore static caches so middleware re-reads DB values.
     */
    private static function clearAdminStoreCache(): void
    {
        $props = [
            'getAll', 'getStoreActive', 'getCodeActive', 'getDomainPartner',
            'getDomainStore', 'getListAllActive', 'arrayStoreId',
            'listStoreId', 'listStoreCode', 'getStoreDomainByCode',
        ];

        foreach ($props as $prop) {
            $ref = new \ReflectionProperty(AdminStore::class, $prop);
            $ref->setValue(null, null);
        }
    }
}
