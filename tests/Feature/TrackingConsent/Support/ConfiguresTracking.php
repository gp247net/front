<?php

namespace GP247\Front\Tests\Feature\TrackingConsent\Support;

use GP247\Core\Models\AdminConfig;
use GP247\Core\Models\AdminStore;
use GP247\Core\Models\AdminUser;
use GP247\Front\Models\FrontTrackingSnippet;
use GP247\Front\Tracking\Application\SnippetService;
use GP247\Front\Tracking\Application\TrackingSettingsService;
use GP247\Front\Tracking\Infrastructure\TrackingInstaller;
use Illuminate\Support\Facades\DB;
use GP247\Core\Testing\ReopensTransactionAfterDdl;

/**
 * Shared set-up of the tracking-consent Feature-Tests: the two tables exist (created once
 * on the test database, then the test transaction is reopened), ROOT starts from the
 * install defaults with no code block, and every static memo / cache that would hide a
 * fresh write is reset.
 *
 * @aidlc-unit tracking-consent
 */
trait ConfiguresTracking
{
    use ReopensTransactionAfterDdl;

    /**
     * @return void
     */
    protected function setUpTracking(): void
    {
        TrackingInstaller::createTables();
        $this->reopenTransactionAfterDdl();

        AdminConfig::where('group', TrackingSettingsService::GROUP)->delete();
        FrontTrackingSnippet::query()->delete();
        TrackingInstaller::seedConfig();
        $this->resetTrackingCaches();
    }

    /**
     * Forget every per-request memo and the code cache.
     *
     * @return void
     */
    protected function resetTrackingCaches(): void
    {
        AdminConfig::forgetMemo();
        foreach (AdminStore::query()->pluck('id') as $id) {
            SnippetService::forgetCache((string) $id);
        }
        SnippetService::forgetCache(TrackingSettingsService::rootId());
        foreach (['getAll', 'getStoreActive', 'getCodeActive', 'getDomainPartner', 'getDomainStore', 'getListAllActive', 'arrayStoreId', 'listStoreId', 'listStoreCode', 'getStoreDomainByCode'] as $prop) {
            (new \ReflectionProperty(AdminStore::class, $prop))->setValue(null, null);
        }
    }

    /**
     * Write a ROOT (or store) setting directly.
     *
     * @param string          $key
     * @param string          $value
     * @param int|string|null $storeId Null = ROOT.
     * @return void
     */
    protected function setTracking(string $key, string $value, $storeId = null): void
    {
        AdminConfig::updateOrCreate(
            ['key' => $key, 'store_id' => (string) ($storeId ?? TrackingSettingsService::rootId())],
            ['group' => TrackingSettingsService::GROUP, 'code' => TrackingSettingsService::CODE, 'value' => $value, 'sort' => 0, 'detail' => 'admin.tracking.config.' . $key],
        );
        $this->resetTrackingCaches();
    }

    /**
     * Add one code block straight to the table.
     *
     * @param string          $content
     * @param string          $region
     * @param string          $category
     * @param int|string|null $storeId Null = ROOT.
     * @param int             $status
     * @return void
     */
    protected function addSnippetRow(string $content, string $region = 'head', string $category = 'marketing', $storeId = null, int $status = 1): void
    {
        FrontTrackingSnippet::create([
            'store_id' => (string) ($storeId ?? TrackingSettingsService::rootId()),
            'region' => $region,
            'category' => $category,
            'label' => 'test',
            'content' => $content,
            'sort' => 0,
            'status' => $status,
        ]);
        $this->resetTrackingCaches();
    }

    /**
     * Make the site multi-store for this test (rolled back with the transaction).
     *
     * @return string Id of a new sub-store.
     */
    protected function makeSubStore(): string
    {
        AdminConfig::updateOrCreate(
            ['key' => 'MultiStore', 'store_id' => (string) GP247_STORE_ID_GLOBAL],
            ['group' => 'Plugins', 'code' => 'Other', 'value' => '1', 'sort' => 0, 'detail' => 'test'],
        );
        $suffix = uniqid();
        $id = (string) AdminStore::create([
            'language' => 'en', 'currency' => 'USD', 'template' => 'GP247Front',
            'code' => 'tc' . substr($suffix, -10), 'domain' => 'tc-' . $suffix . '.local',
        ])->id;
        $this->resetTrackingCaches();

        return $id;
    }

    /**
     * A real admin on the `admin` guard (needed by the operation log), plus the contract.
     *
     * @param bool $administrator
     * @return AdminUser
     */
    protected function signInAdmin(bool $administrator = true): AdminUser
    {
        $suffix = substr(uniqid(), -6);
        $user = AdminUser::create([
            'id' => 'AU-TC' . $suffix,
            'username' => 'tc_' . $suffix,
            'password' => bcrypt('secret-' . $suffix),
            'name' => 'Tracking Probe',
            'email' => 'tc-' . $suffix . '@test.local',
            'status' => 1,
        ]);
        $this->actingAs($user, 'admin');
        $this->asUser(administrator: $administrator, viewAll: !$administrator);

        return $user;
    }

    /**
     * Number of rows in one of the tracking tables.
     *
     * @param string $table
     * @return int
     */
    protected function trackingRows(string $table): int
    {
        return DB::connection(GP247_DB_CONNECTION)->table(GP247_DB_PREFIX . $table)->count();
    }
}
