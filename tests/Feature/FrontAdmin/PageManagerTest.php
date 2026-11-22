<?php

namespace GP247\Front\Tests\Feature\FrontAdmin;

use GP247\Core\Models\AdminLanguage;
use GP247\Front\Admin\Livewire\PageManager;
use GP247\Front\Models\FrontPage;
use GP247\Front\Models\FrontPageDescription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use GP247\Core\Testing\InteractsWithAdminUser;
use GP247\Front\Tests\TestCase;

/**
 * Feature tests for the front-admin page (CMS) screen — the single routed panel
 * (PageManager) after the cutover. Ported from the retired PageForm/PageList pair:
 * list, create/edit validation, delete, Layer-2 authorization, plus page-title
 * cache invalidation on save/delete. Multi-language descriptions use the `name`
 * column (core 2.0 schema). Domain unchanged (FrontPage + FrontPageDescription).
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-002
 * @aidlc-adr ADR-001, ADR-005, ADR-006, ADR-007
 */
class PageManagerTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAdminUser;

    /**
     * Create a page with a description (name) in every active language.
     *
     * @param array<string, mixed> $overrides Page attribute overrides.
     * @param string               $name      Name used for each description row.
     * @return FrontPage
     */
    private function makePage(array $overrides = [], string $name = 'ZZ Seed Page'): FrontPage
    {
        $page = FrontPage::create(array_merge([
            'image' => '',
            'alias' => 'zz-seed-page-' . uniqid(),
            'status' => 1,
        ], $overrides));

        foreach (AdminLanguage::getListActive() as $code => $language) {
            FrontPageDescription::create([
                'page_id' => $page->id,
                'lang' => $code,
                'name' => $name,
                'keyword' => '',
                'description' => '',
                'content' => '',
            ]);
        }

        return $page;
    }

    public function test_lists_existing_pages(): void
    {
        $this->asUser(slugs: ['admin_page']);
        $this->makePage(['alias' => 'zz-list-page'], 'ZZ List Page');

        Livewire::test(PageManager::class)->assertSee('zz-list-page');
    }

    /**
     * The screen and its list card are titled like the menu item ("Page manager"), not with
     * the legacy column label admin.page.title ("Title").
     */
    public function test_screen_title_uses_the_menu_label(): void
    {
        $this->asUser(slugs: ['admin_page']);

        $component = Livewire::test(PageManager::class)
            ->assertSee(gp247_language_render('admin.menu_titles.page_manager'));

        $title = (new \ReflectionMethod(PageManager::class, 'pageTitle'))->invoke($component->instance());
        $this->assertSame(gp247_language_render('admin.menu_titles.page_manager'), $title);
        $this->assertNotSame(gp247_language_render('admin.page.title'), $title);
    }

    public function test_creates_page(): void
    {
        $this->asUser(slugs: ['admin_page']);

        $component = Livewire::test(PageManager::class)->set('form.alias', 'zz-created-page');
        foreach (AdminLanguage::getListActive() as $code => $language) {
            $component->set('descriptions.' . $code . '.name', 'ZZ Created Page');
        }

        $component->call('save')
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $row = FrontPage::where('alias', 'zz-created-page')->first();
        $this->assertNotNull($row);
        $this->assertTrue($row->descriptions()->where('name', 'ZZ Created Page')->exists());
    }

    public function test_create_requires_alias_and_name(): void
    {
        $this->asUser(slugs: ['admin_page']);

        $firstCode = (string) array_key_first(AdminLanguage::getListActive()->toArray());

        Livewire::test(PageManager::class)
            ->set('form.alias', '')
            ->set('descriptions.' . $firstCode . '.name', '')
            ->call('save')
            ->assertHasErrors(['form.alias', 'descriptions.' . $firstCode . '.name']);
    }

    public function test_edit_loads_and_updates(): void
    {
        $this->asUser(slugs: ['admin_page']);
        $page = $this->makePage(['alias' => 'zz-edit-page'], 'ZZ Edit Page');
        $firstCode = (string) array_key_first(AdminLanguage::getListActive()->toArray());

        Livewire::test(PageManager::class, ['id' => $page->id])
            ->assertSet('form.alias', 'zz-edit-page')
            ->assertSet('descriptions.' . $firstCode . '.name', 'ZZ Edit Page')
            ->set('descriptions.' . $firstCode . '.name', 'ZZ Edited Page')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(
            FrontPage::find($page->id)->descriptions()->where('name', 'ZZ Edited Page')->exists()
        );
    }

    public function test_delete_removes_row(): void
    {
        $this->asUser(slugs: ['admin_page']);
        $page = $this->makePage(['alias' => 'zz-delete-page'], 'ZZ Delete Page');

        Livewire::test(PageManager::class)->call('delete', $page->id);

        $this->assertNull(FrontPage::find($page->id));
    }

    public function test_save_denied_for_view_all(): void
    {
        $this->asUser(viewAll: true);

        $component = Livewire::test(PageManager::class)->set('form.alias', 'nope-page');
        foreach (AdminLanguage::getListActive() as $code => $language) {
            $component->set('descriptions.' . $code . '.name', 'Nope Page');
        }

        $this->assertDenied(fn () => $component->call('save'));
        $this->assertFalse(FrontPage::where('alias', 'nope-page')->exists());
    }

    /**
     * FrontPage::getListTitleAdmin() caches page titles per store x locale. Saving a
     * page must bump the `page` cache group so every cached variant is invalidated.
     *
     * @aidlc-story US-AUI-cache-config-hardening
     */
    public function test_save_invalidates_page_cache(): void
    {
        $this->asUser(slugs: ['admin_page']);

        $before = gp247_cache_version('page');

        $component = Livewire::test(PageManager::class)->set('form.alias', 'zz-cache-bump-page');
        foreach (AdminLanguage::getListActive() as $code => $language) {
            $component->set('descriptions.' . $code . '.name', 'ZZ Cache Bump Page');
        }
        $component->call('save')->assertHasNoErrors();

        $this->assertGreaterThan($before, gp247_cache_version('page'));
    }

    /**
     * Deleting a page must invalidate the cached title dropdown too.
     *
     * @aidlc-story US-AUI-cache-config-hardening
     */
    public function test_delete_invalidates_page_cache(): void
    {
        $this->asUser(slugs: ['admin_page']);
        $page = FrontPage::create([
            'image' => '',
            'alias' => 'zz-cache-del-page-' . uniqid(),
            'status' => 1,
        ]);

        $before = gp247_cache_version('page');

        Livewire::test(PageManager::class)->call('delete', $page->id);

        $this->assertGreaterThan($before, gp247_cache_version('page'));
    }
}
