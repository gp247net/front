<?php

namespace GP247\Front\Tests\Feature\FrontendTemplateDev;

use App\GP247\Templates\GP247Front\AppConfig;
use GP247\Core\Models\AdminStore;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use GP247\Front\Tests\TestCase;

/**
 * Verifies AppConfig::setupStore() (app/GP247/Templates/GP247Front/AppConfig.php)
 * assigns the GP247Front template to the given store's admin_store.template
 * column, and leaves it untouched when no store id is given.
 *
 * Only ever operates on a store row created inside this test's own
 * transaction (never the real root store, id GP247_STORE_ID_ROOT) — the
 * transaction rolls back after each test, so nothing persists to the dev DB.
 * This is distinct from switching the live site's active template, which
 * remains an explicit, separately-approved cutover action.
 *
 * @aidlc-unit frontend-template-dev
 * @aidlc-story US-TPL-009
 * @aidlc-adr ADR-014
 */
class AppConfigSetupStoreTest extends TestCase
{
    use DatabaseTransactions;

    private function seedStore(): AdminStore
    {
        return AdminStore::create([
            'id' => (string) Str::uuid(),
            'code' => 'zzrtl_'.Str::random(8),
            'template' => 'Default',
            'language' => 'en',
            'currency' => 'USD',
            'status' => 1,
            'active' => 1,
        ]);
    }

    public function test_setup_store_switches_the_given_store_template_to_gp247front(): void
    {
        $store = $this->seedStore();

        (new AppConfig())->setupStore($store->id);

        $this->assertSame('GP247Front', $store->fresh()->template);
    }

    public function test_setup_store_does_nothing_without_a_store_id(): void
    {
        $store = $this->seedStore();

        $this->assertNull((new AppConfig())->setupStore(null));
        $this->assertSame('Default', $store->fresh()->template);
    }
}
