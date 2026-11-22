<?php

namespace GP247\Front\Tests\Feature\FrontAdmin;

use GP247\Front\Admin\Livewire\BannerTypeForm;
use GP247\Front\Admin\Livewire\BannerTypeList;
use GP247\Front\Models\FrontBannerType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use GP247\Core\Testing\InteractsWithAdminUser;
use GP247\Front\Tests\TestCase;

/**
 * E2E for the front-admin banner-type screen (ADR-006/007): list, create/edit
 * validation + uniqueness, delete, and Layer-2 authorization. Reuses the core
 * admin-shell test helpers (FakeAdminUser).
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-001
 * @aidlc-adr ADR-001, ADR-006, ADR-007
 */
class BannerTypeTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAdminUser;

    private function makeType(array $overrides = []): FrontBannerType
    {
        return FrontBannerType::create(array_merge([
            'code' => 'zz_seed_type',
            'name' => 'ZZ Seed Type',
        ], $overrides));
    }

    public function test_lists_existing_types(): void
    {
        $this->asUser(slugs: ['admin_banner']);
        $this->makeType(['code' => 'zz_list_one', 'name' => 'ZZ List One']);

        Livewire::test(BannerTypeList::class)->assertSee('ZZ List One');
    }

    public function test_creates_type(): void
    {
        $this->asUser(slugs: ['admin_banner']);

        Livewire::test(BannerTypeForm::class)
            ->set('form.name', 'ZZ Created Type')
            ->set('form.code', 'zzcreatedtype')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin_banner_type.index'));

        $this->assertTrue(FrontBannerType::where('name', 'ZZ Created Type')->exists());
    }

    public function test_create_requires_name_and_code(): void
    {
        $this->asUser(slugs: ['admin_banner']);

        Livewire::test(BannerTypeForm::class)
            ->set('form.name', '')
            ->set('form.code', '')
            ->call('save')
            ->assertHasErrors(['form.name', 'form.code']);
    }

    public function test_create_rejects_duplicate_code(): void
    {
        $this->asUser(slugs: ['admin_banner']);
        $this->makeType(['code' => 'zzdupcode', 'name' => 'ZZ Dup']);

        Livewire::test(BannerTypeForm::class)
            ->set('form.name', 'Another')
            ->set('form.code', 'zzdupcode')
            ->call('save')
            ->assertHasErrors('form.code');
    }

    public function test_edit_loads_and_updates(): void
    {
        $this->asUser(slugs: ['admin_banner']);
        $row = $this->makeType(['code' => 'zz_edit_type', 'name' => 'ZZ Old']);

        Livewire::test(BannerTypeForm::class, ['id' => $row->id])
            ->assertSet('form.name', 'ZZ Old')
            ->set('form.name', 'ZZ New')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('ZZ New', FrontBannerType::find($row->id)->name);
    }

    public function test_delete_removes_row(): void
    {
        $this->asUser(slugs: ['admin_banner']);
        $row = $this->makeType(['code' => 'zz_del_type']);

        Livewire::test(BannerTypeList::class)->call('delete', $row->id);

        $this->assertNull(FrontBannerType::find($row->id));
    }

    public function test_save_denied_for_view_all(): void
    {
        $this->asUser(viewAll: true);

        $component = Livewire::test(BannerTypeForm::class)
            ->set('form.name', 'Nope')
            ->set('form.code', 'zznope');

        $this->assertDenied(fn () => $component->call('save'));
        $this->assertFalse(FrontBannerType::where('code', 'zznope')->exists());
    }
}
