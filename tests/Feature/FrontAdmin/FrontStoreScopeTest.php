<?php

namespace GP247\Front\Tests\Feature\FrontAdmin;

use GP247\Core\Models\AdminConfig;
use GP247\Core\Models\AdminLanguage;
use GP247\Core\Models\AdminStore;
use GP247\Front\Admin\Livewire\LayoutBlockManager;
use GP247\Front\Admin\Livewire\LinkManager;
use GP247\Front\Admin\Livewire\PageManager;
use GP247\Front\Admin\Livewire\SeoRedirectManager;
use GP247\Front\Models\FrontLayoutBlock;
use GP247\Front\Models\FrontLink;
use GP247\Front\Models\FrontPage;
use GP247\Front\Models\FrontRedirect;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use GP247\Core\Testing\InteractsWithAdminUser;
use GP247\Front\Tests\TestCase;

/**
 * Store-scoping contract for the front-admin ResourcePanel screens (Page / Link /
 * LayoutBlock / SeoRedirect): create at root assigns the picked store, the store is
 * immutable on edit, and a cross-store related reference (Link → collection) is
 * rejected server-side.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-SADM-store-content-assignment
 * @aidlc-adr admin-shell_store-scoped-resource-panel, multi-store_one-to-one-store-ownership
 */
class FrontStoreScopeTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAdminUser;

    /**
     * Turn multi-store on for every test of this class.
     *
     * WHY: the store picker only takes effect when gp247_store_check_multi_domain_installed()
     * is true; without this the tests passed or failed depending on whether the local
     * test DB still had a multi-store plugin installed. The surrounding transaction
     * removes the flag again.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        AdminConfig::updateOrCreate(
            ['group' => 'Plugins', 'key' => 'MultiStore', 'store_id' => GP247_STORE_ID_GLOBAL],
            ['code' => 'Other', 'sort' => 0, 'value' => '1', 'detail' => 'test'],
        );
        $this->resetConfigCaches();
    }

    /**
     * Drop AdminConfig's static caches so the flag written in setUp() is read.
     *
     * @return void
     */
    private function resetConfigCaches(): void
    {
        foreach (['getAllGlobal' => null, 'getAllConfigOfStore' => []] as $name => $empty) {
            $prop = new \ReflectionProperty(AdminConfig::class, $name);
            $prop->setAccessible(true);
            $prop->setValue(null, $empty);
        }
    }

    /**
     * @return AdminStore A fresh sub-store.
     */
    private function makeStore(): AdminStore
    {
        $suffix = uniqid();

        return AdminStore::create([
            'language' => 'en', 'currency' => 'USD', 'template' => 'GP247Front',
            'code' => 'tf' . substr($suffix, -10), 'domain' => 'tf-' . $suffix . '.local', 'status' => 1,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function langCodes(): array
    {
        return array_keys(AdminLanguage::getListActive()->all());
    }

    public function test_page_create_assigns_picked_store_and_locks_on_edit(): void
    {
        $this->asUser(slugs: ['admin_page']);
        $store = $this->makeStore();
        $alias = 'zz-page-' . substr(uniqid(), -6);

        $c = Livewire::test(PageManager::class)
            ->set('formStoreId', (string) $store->id)
            ->set('form.alias', $alias);
        foreach ($this->langCodes() as $code) {
            $c->set("descriptions.{$code}.name", 'ZZ Page');
        }
        $c->call('save')->assertHasNoErrors();

        $row = FrontPage::where('alias', $alias)->first();
        $this->assertNotNull($row);
        $this->assertSame((string) $store->id, (string) $row->store_id);

        Livewire::test(PageManager::class, ['id' => $row->id])
            ->set('formStoreId', '1')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame((string) $store->id, (string) FrontPage::find($row->id)->store_id);
    }

    public function test_link_rejects_cross_store_collection(): void
    {
        $this->asUser(slugs: ['admin_link']);
        $storeA = $this->makeStore();

        // A collection link owned by store A.
        $collection = FrontLink::create([
            'name' => 'ZZ Coll', 'url' => 'collection', 'type' => 'collection', 'target' => '_self',
            'group' => 'main', 'sort' => 0, 'status' => 1, 'store_id' => $storeA->id,
        ]);

        // Create a link at ROOT that points at store A's collection → rejected.
        Livewire::test(LinkManager::class)
            ->set('formStoreId', '1')
            ->set('form.name', 'ZZ Link')
            ->set('form.group', 'main')
            ->set('form.url', '/x')
            ->set('form.target', '_self')
            ->set('form.collection_id', (string) $collection->id)
            ->call('save')
            ->assertHasErrors('form.collection_id');
    }

    public function test_layout_block_create_assigns_picked_store_and_locks_on_edit(): void
    {
        $this->asUser(slugs: ['admin_layout_block']);
        $store = $this->makeStore();
        $name = 'ZZ Block ' . substr(uniqid(), -6);

        Livewire::test(LayoutBlockManager::class)
            ->set('formStoreId', (string) $store->id)
            ->set('form.name', $name)
            ->set('form.position', 'GP247_TOP')
            ->set('form.page', ['*'])
            ->set('form.type', 'html')
            ->set('form.text', '<p>hi</p>')
            ->set('form.sort', 0)
            ->call('save')
            ->assertHasNoErrors();

        $row = FrontLayoutBlock::where('name', $name)->first();
        $this->assertNotNull($row);
        $this->assertSame((string) $store->id, (string) $row->store_id);

        Livewire::test(LayoutBlockManager::class, ['id' => $row->id])
            ->set('formStoreId', '1')
            ->set('form.sort', 3)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame((string) $store->id, (string) FrontLayoutBlock::find($row->id)->store_id);
    }

    public function test_seo_redirect_create_assigns_picked_store_and_locks_on_edit(): void
    {
        $this->asUser(slugs: ['admin_seo_redirect']);
        $store = $this->makeStore();
        $from = '/zz-' . substr(uniqid(), -6);

        Livewire::test(SeoRedirectManager::class)
            ->set('formStoreId', (string) $store->id)
            ->set('form.from', $from)
            ->set('form.to', '/target')
            ->set('form.code', 301)
            ->call('save')
            ->assertHasNoErrors();

        $row = FrontRedirect::where('from', $from)->where('store_id', $store->id)->first();
        $this->assertNotNull($row);
        $this->assertSame((string) $store->id, (string) $row->store_id);

        Livewire::test(SeoRedirectManager::class, ['id' => $row->id])
            ->set('formStoreId', '1')
            ->set('form.to', '/target2')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame((string) $store->id, (string) FrontRedirect::find($row->id)->store_id);
    }
}
