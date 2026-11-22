<?php

namespace GP247\Front\Tests\Feature\FrontAdmin;

use GP247\Front\Admin\Livewire\BannerForm;
use GP247\Front\Admin\Livewire\BannerList;
use GP247\Front\Models\FrontBanner;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use GP247\Core\Testing\InteractsWithAdminUser;
use GP247\Front\Tests\TestCase;

/**
 * E2E for the front-admin banner screen (ADR-006/007): list, create/edit
 * validation, delete, and Layer-2 authorization. Domain unchanged (FrontBanner).
 *
 * The headline field is LABELLED "Title" in the admin (BannerForm maps
 * form.name => admin.banner.title) but the column and the Livewire property are
 * both `name` — front_banner has no `title` column at all. This suite used to
 * assert against `title`, so every case that touched the field died on
 * "Unknown column 'title'" instead of testing anything.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-001
 * @aidlc-adr ADR-001, ADR-006, ADR-007
 */
class BannerTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAdminUser;

    private function makeBanner(array $overrides = []): FrontBanner
    {
        return FrontBanner::create(array_merge([
            // WHY store_id: banners became store-owned (scalar store_id) with the
            // 1-1 store migration, and BannerList is store-scoped. A fixture that
            // leaves it null is invisible to the list, so the assertion failed for
            // a reason that had nothing to do with what the case is about.
            'store_id' => config('app.storeId'),
            'name' => 'ZZ Seed Banner',
            'url' => 'https://example.test',
            'type' => 'banner',
            'target' => '_self',
            'sort' => 0,
            'status' => 1,
        ], $overrides));
    }

    public function test_lists_existing_banners(): void
    {
        $this->asUser(slugs: ['admin_banner']);
        $this->makeBanner(['name' => 'ZZ List Banner']);

        // WHY filtered rather than asserting against the bare first page: a root
        // admin sees every store's banners, so on an install with more than one
        // page of them the seeded row lands on page 2 and the case fails for a
        // reason that has nothing to do with listing.
        Livewire::test(BannerList::class)
            ->set('keyword', 'ZZ List Banner')
            ->assertSee('ZZ List Banner');
    }

    public function test_creates_banner(): void
    {
        $this->asUser(slugs: ['admin_banner']);

        Livewire::test(BannerForm::class)
            ->set('form.name', 'ZZ Created Banner')
            ->set('form.url', 'https://example.test/x')
            ->set('form.type', 'banner')
            ->set('form.target', '_blank')
            ->set('form.sort', 3)
            ->set('form.status', 1)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin_banner.index'));

        $row = FrontBanner::where('name', 'ZZ Created Banner')->first();
        $this->assertNotNull($row);
        $this->assertSame('_blank', $row->target);
        $this->assertSame(3, (int) $row->sort);
    }

    public function test_create_requires_title_and_valid_target(): void
    {
        $this->asUser(slugs: ['admin_banner']);

        Livewire::test(BannerForm::class)
            ->set('form.name', '')
            ->set('form.target', 'bogus')
            ->set('form.sort', -1)
            ->call('save')
            ->assertHasErrors(['form.name', 'form.target', 'form.sort']);
    }

    public function test_edit_loads_and_updates(): void
    {
        $this->asUser(slugs: ['admin_banner']);
        $row = $this->makeBanner(['name' => 'ZZ Edit Banner']);

        Livewire::test(BannerForm::class, ['id' => $row->id])
            ->assertSet('form.name', 'ZZ Edit Banner')
            ->set('form.name', 'ZZ Edited Banner')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('ZZ Edited Banner', FrontBanner::find($row->id)->name);
    }

    public function test_delete_removes_row(): void
    {
        $this->asUser(slugs: ['admin_banner']);
        $row = $this->makeBanner(['name' => 'ZZ Delete Banner']);

        Livewire::test(BannerList::class)->call('delete', $row->id);

        $this->assertNull(FrontBanner::find($row->id));
    }

    public function test_save_denied_for_view_all(): void
    {
        $this->asUser(viewAll: true);

        $component = Livewire::test(BannerForm::class)
            ->set('form.name', 'Nope Banner')
            ->set('form.target', '_self')
            ->set('form.sort', 0);

        $this->assertDenied(fn () => $component->call('save'));
        $this->assertFalse(FrontBanner::where('name', 'Nope Banner')->exists());
    }
}
