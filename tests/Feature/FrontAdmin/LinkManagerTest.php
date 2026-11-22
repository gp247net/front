<?php

namespace GP247\Front\Tests\Feature\FrontAdmin;

use GP247\Front\Admin\Livewire\LinkManager;
use GP247\Front\Models\FrontLink;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use GP247\Core\Testing\InteractsWithAdminUser;
use GP247\Front\Tests\TestCase;

/**
 * Feature tests for the front-admin link screen — the single routed panel
 * (LinkManager) after the cutover. Ported from the retired LinkForm/LinkList pair:
 * list, create/edit validation, collection links, delete, and Layer-2
 * authorization. Domain unchanged (FrontLink).
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-003
 * @aidlc-adr ADR-001, ADR-005, ADR-006, ADR-007
 */
class LinkManagerTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAdminUser;

    private function makeLink(array $overrides = []): FrontLink
    {
        return FrontLink::create(array_merge([
            'name' => 'ZZ Seed Link',
            'url' => 'https://example.test',
            'target' => '_self',
            'group' => 'zz_group',
            'type' => '',
            'sort' => 0,
            'status' => 1,
        ], $overrides));
    }

    public function test_lists_existing_links(): void
    {
        $this->asUser(slugs: ['admin_link']);
        $this->makeLink(['name' => 'ZZ List Link']);

        Livewire::test(LinkManager::class)->assertSee('ZZ List Link');
    }

    public function test_creates_link(): void
    {
        $this->asUser(slugs: ['admin_link']);

        Livewire::test(LinkManager::class)
            ->set('form.name', 'ZZ Created Link')
            ->set('form.url', 'https://example.test/x')
            ->set('form.target', '_blank')
            ->set('form.group', 'zz_group')
            ->set('form.sort', 3)
            ->set('form.status', 1)
            ->call('save')
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $row = FrontLink::where('name', 'ZZ Created Link')->first();
        $this->assertNotNull($row);
        $this->assertSame('_blank', $row->target);
        $this->assertSame(3, (int) $row->sort);
    }

    public function test_create_requires_name_url_group_and_valid_target(): void
    {
        $this->asUser(slugs: ['admin_link']);

        Livewire::test(LinkManager::class)
            ->set('form.name', '')
            ->set('form.url', '')
            ->set('form.group', '')
            ->set('form.target', 'bogus')
            ->set('form.sort', -1)
            ->call('save')
            ->assertHasErrors(['form.name', 'form.url', 'form.group', 'form.target', 'form.sort']);
    }

    public function test_collection_link_does_not_require_url(): void
    {
        $this->asUser(slugs: ['admin_link']);

        Livewire::test(LinkManager::class)
            ->set('form.type', 'collection')
            ->set('form.name', 'ZZ Collection Link')
            ->set('form.group', 'zz_group')
            ->set('form.sort', 0)
            ->call('save')
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $row = FrontLink::where('name', 'ZZ Collection Link')->first();
        $this->assertNotNull($row);
        $this->assertSame('collection', $row->type);
        $this->assertSame('collection', $row->url);
    }

    public function test_edit_loads_and_updates(): void
    {
        $this->asUser(slugs: ['admin_link']);
        $row = $this->makeLink(['name' => 'ZZ Edit Link']);

        Livewire::test(LinkManager::class, ['id' => $row->id])
            ->assertSet('form.name', 'ZZ Edit Link')
            ->set('form.name', 'ZZ Edited Link')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('ZZ Edited Link', FrontLink::find($row->id)->name);
    }

    public function test_delete_removes_row(): void
    {
        $this->asUser(slugs: ['admin_link']);
        $row = $this->makeLink(['name' => 'ZZ Delete Link']);

        Livewire::test(LinkManager::class)->call('delete', $row->id);

        $this->assertNull(FrontLink::find($row->id));
    }

    public function test_save_denied_for_view_all(): void
    {
        $this->asUser(viewAll: true);

        $component = Livewire::test(LinkManager::class)
            ->set('form.name', 'Nope Link')
            ->set('form.url', 'https://example.test')
            ->set('form.target', '_self')
            ->set('form.group', 'zz_group')
            ->set('form.sort', 0);

        $this->assertDenied(fn () => $component->call('save'));
        $this->assertFalse(FrontLink::where('name', 'Nope Link')->exists());
    }
}
