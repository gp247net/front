<?php

namespace GP247\Front\Tests\Feature\FrontAdmin;

use GP247\Front\Admin\Livewire\LinkGroupForm;
use GP247\Front\Admin\Livewire\LinkGroupList;
use GP247\Front\Models\FrontLinkGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use GP247\Core\Testing\InteractsWithAdminUser;
use GP247\Front\Tests\TestCase;

/**
 * E2E for the front-admin link-group screen (ADR-006/007): list, create/edit
 * validation + uniqueness, delete, and Layer-2 authorization. Reuses the core
 * admin-shell test helpers (FakeAdminUser).
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-003
 * @aidlc-adr ADR-001, ADR-006, ADR-007
 */
class LinkGroupTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAdminUser;

    private function makeGroup(array $overrides = []): FrontLinkGroup
    {
        return FrontLinkGroup::create(array_merge([
            'code' => 'zz_seed_group',
            'name' => 'ZZ Seed Group',
        ], $overrides));
    }

    public function test_lists_existing_groups(): void
    {
        $this->asUser(slugs: ['admin_link']);
        $this->makeGroup(['code' => 'zz_list_one', 'name' => 'ZZ List One']);

        Livewire::test(LinkGroupList::class)->assertSee('ZZ List One');
    }

    public function test_creates_group(): void
    {
        $this->asUser(slugs: ['admin_link']);

        Livewire::test(LinkGroupForm::class)
            ->set('form.name', 'ZZ Created Group')
            ->set('form.code', 'zzcreatedgroup')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin_link_group.index'));

        $this->assertTrue(FrontLinkGroup::where('name', 'ZZ Created Group')->exists());
    }

    public function test_create_requires_name_and_code(): void
    {
        $this->asUser(slugs: ['admin_link']);

        Livewire::test(LinkGroupForm::class)
            ->set('form.name', '')
            ->set('form.code', '')
            ->call('save')
            ->assertHasErrors(['form.name', 'form.code']);
    }

    public function test_create_rejects_duplicate_code(): void
    {
        $this->asUser(slugs: ['admin_link']);
        $this->makeGroup(['code' => 'zzdupcode', 'name' => 'ZZ Dup']);

        Livewire::test(LinkGroupForm::class)
            ->set('form.name', 'Another')
            ->set('form.code', 'zzdupcode')
            ->call('save')
            ->assertHasErrors('form.code');
    }

    public function test_edit_loads_and_updates(): void
    {
        $this->asUser(slugs: ['admin_link']);
        $row = $this->makeGroup(['code' => 'zz_edit_group', 'name' => 'ZZ Old']);

        Livewire::test(LinkGroupForm::class, ['id' => $row->id])
            ->assertSet('form.name', 'ZZ Old')
            ->set('form.name', 'ZZ New')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('ZZ New', FrontLinkGroup::find($row->id)->name);
    }

    public function test_delete_removes_row(): void
    {
        $this->asUser(slugs: ['admin_link']);
        $row = $this->makeGroup(['code' => 'zz_del_group']);

        Livewire::test(LinkGroupList::class)->call('delete', $row->id);

        $this->assertNull(FrontLinkGroup::find($row->id));
    }

    public function test_save_denied_for_view_all(): void
    {
        $this->asUser(viewAll: true);

        $component = Livewire::test(LinkGroupForm::class)
            ->set('form.name', 'Nope')
            ->set('form.code', 'zznope');

        $this->assertDenied(fn () => $component->call('save'));
        $this->assertFalse(FrontLinkGroup::where('code', 'zznope')->exists());
    }
}
