<?php

namespace GP247\Front\Tests\Feature\FrontAdmin;

use GP247\Front\Admin\Livewire\LayoutBlockManager;
use GP247\Front\Models\FrontLayoutBlock;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use GP247\Core\Testing\InteractsWithAdminUser;
use GP247\Front\Tests\TestCase;

/**
 * Feature tests for the front-admin layout block screen — the single routed panel
 * (LayoutBlockManager) after the cutover. Ported from the retired LayoutBlockForm/
 * LayoutBlockList pair: list, create/edit validation, delete, and Layer-2
 * authorization. Domain unchanged (FrontLayoutBlock).
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-004
 * @aidlc-adr ADR-001, ADR-005, ADR-006, ADR-007
 */
class LayoutBlockManagerTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAdminUser;

    private function makeBlock(array $overrides = []): FrontLayoutBlock
    {
        return FrontLayoutBlock::create(array_merge([
            'name' => 'ZZ Seed Block',
            'position' => 'top',
            'page' => '*',
            'text' => '<p>seed</p>',
            'type' => 'html',
            'sort' => 0,
            'status' => 1,
            // WHY: a block belongs to one store and the screen only sees the admin's store
            // (ROOT here); store_id 0 is shown nowhere, so it cannot be listed, edited or deleted.
            'store_id' => GP247_STORE_ID_ROOT,
            'template' => '',
        ], $overrides));
    }

    public function test_lists_existing_blocks(): void
    {
        $this->asUser(slugs: ['admin_layout_block']);
        $this->makeBlock(['name' => 'ZZ List Block']);

        Livewire::test(LayoutBlockManager::class)->assertSee('ZZ List Block');
    }

    public function test_creates_block(): void
    {
        $this->asUser(slugs: ['admin_layout_block']);

        Livewire::test(LayoutBlockManager::class)
            ->set('form.name', 'ZZ Created Block')
            ->set('form.position', 'footer')
            ->set('form.page', ['front_home', 'front_contact'])
            ->set('form.text', '<p>hello</p>')
            ->set('form.type', 'html')
            ->set('form.sort', 3)
            ->set('form.status', 1)
            ->call('save')
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $row = FrontLayoutBlock::where('name', 'ZZ Created Block')->first();
        $this->assertNotNull($row);
        $this->assertSame('footer', $row->position);
        $this->assertSame('front_home,front_contact', $row->page);
        $this->assertSame('html', $row->type);
        $this->assertSame(3, (int) $row->sort);
    }

    public function test_create_requires_fields_and_valid_type(): void
    {
        $this->asUser(slugs: ['admin_layout_block']);

        Livewire::test(LayoutBlockManager::class)
            ->set('form.name', '')
            ->set('form.position', '')
            ->set('form.page', '')
            ->set('form.text', '')
            ->set('form.type', 'bogus')
            ->set('form.sort', -1)
            ->call('save')
            ->assertHasErrors([
                'form.name',
                'form.position',
                'form.page',
                'form.text',
                'form.type',
                'form.sort',
            ]);
    }

    public function test_edit_loads_and_updates(): void
    {
        $this->asUser(slugs: ['admin_layout_block']);
        $row = $this->makeBlock(['name' => 'ZZ Edit Block']);

        Livewire::test(LayoutBlockManager::class, ['id' => $row->id])
            ->assertSet('form.name', 'ZZ Edit Block')
            ->set('form.name', 'ZZ Edited Block')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('ZZ Edited Block', FrontLayoutBlock::find($row->id)->name);
    }

    public function test_delete_removes_row(): void
    {
        $this->asUser(slugs: ['admin_layout_block']);
        $row = $this->makeBlock(['name' => 'ZZ Delete Block']);

        Livewire::test(LayoutBlockManager::class)->call('delete', $row->id);

        $this->assertNull(FrontLayoutBlock::find($row->id));
    }

    public function test_save_denied_for_view_all(): void
    {
        $this->asUser(viewAll: true);

        $component = Livewire::test(LayoutBlockManager::class)
            ->set('form.name', 'Nope Block')
            ->set('form.position', 'top')
            ->set('form.page', '*')
            ->set('form.text', '<p>x</p>')
            ->set('form.type', 'html')
            ->set('form.sort', 0);

        $this->assertDenied(fn () => $component->call('save'));
        $this->assertFalse(FrontLayoutBlock::where('name', 'Nope Block')->exists());
    }
}
