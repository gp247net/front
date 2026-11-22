<?php

namespace GP247\Front\Tests\Feature\FrontContact;

use GP247\Front\Admin\Livewire\ContactManager;
use GP247\Front\Models\FrontContact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use GP247\Core\Testing\InteractsWithAdminUser;
use GP247\Front\Tests\Feature\FrontContact\Support\InteractsWithContactForm;
use GP247\Front\Tests\TestCase;

/**
 * Admins read and handle contact messages in the admin: only their store's
 * messages, opening one marks it read, it can be marked handled or new again and
 * deleted, and what visitors typed is shown as text.
 *
 * @aidlc-unit front-contact
 * @aidlc-story US-front-contact-inbox
 * @aidlc-adr front-contact_contact-form-in-front
 */
#[Group('front-contact')]
class ContactInboxTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAdminUser;
    use InteractsWithContactForm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetConfigCaches();
    }

    /**
     * @param array<string, string> $overrides
     * @param string|null           $storeId
     * @return FrontContact
     */
    private function makeMessage(array $overrides = [], ?string $storeId = null): FrontContact
    {
        return FrontContact::receive($this->validMessage($overrides), $storeId ?? $this->rootStoreId());
    }

    /**
     * Act as an admin bound to one store (the context a store-admin works in).
     *
     * @param string $storeId
     * @return void
     */
    private function asStoreAdmin(string $storeId): void
    {
        $this->asUser(slugs: ['admin_contact']);
        session(['adminStoreId' => $storeId]);
    }

    public function test_a_store_admin_sees_only_the_messages_of_their_store(): void
    {
        $this->asStoreAdmin('ZZ-STORE-A');
        $this->makeMessage(['title' => 'ZZ contact test MINE'], 'ZZ-STORE-A');
        $this->makeMessage(['title' => 'ZZ contact test OTHER STORE'], 'ZZ-OTHER-STORE');

        Livewire::test(ContactManager::class)
            ->assertSee('ZZ contact test MINE')
            ->assertDontSee('ZZ contact test OTHER STORE');
    }

    public function test_the_list_can_be_filtered_by_status_and_searched(): void
    {
        $this->asUser(slugs: ['admin_contact']);
        $this->makeMessage(['title' => 'ZZ contact test FRESH']);
        $this->makeMessage(['title' => 'ZZ contact test DONE'])->markHandled();

        Livewire::test(ContactManager::class)
            ->set('statusFilter', (string) FrontContact::STATUS_HANDLED)
            ->assertSee('ZZ contact test DONE')
            ->assertDontSee('ZZ contact test FRESH')
            ->set('statusFilter', '')
            ->set('keyword', 'FRESH')
            ->assertSee('ZZ contact test FRESH')
            ->assertDontSee('ZZ contact test DONE');
    }

    public function test_opening_a_new_message_marks_it_read_and_shows_the_text_as_typed(): void
    {
        $this->asUser(slugs: ['admin_contact']);
        $message = $this->makeMessage(['content' => '<script>alert(1)</script>']);

        Livewire::test(ContactManager::class)
            ->call('open', $message->id)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->assertSame(FrontContact::STATUS_READ, $message->fresh()->status);
    }

    public function test_a_message_can_be_marked_handled_and_back_to_new(): void
    {
        $this->asUser(slugs: ['admin_contact']);
        $message = $this->makeMessage();

        $component = Livewire::test(ContactManager::class)->call('markHandled', $message->id);
        $this->assertSame(FrontContact::STATUS_HANDLED, $message->fresh()->status);

        $component->call('markNew', $message->id);
        $this->assertSame(FrontContact::STATUS_NEW, $message->fresh()->status);
    }

    public function test_a_message_can_be_deleted(): void
    {
        $this->asUser(slugs: ['admin_contact']);
        $message = $this->makeMessage();

        Livewire::test(ContactManager::class)->call('delete', $message->id);

        $this->assertNull(FrontContact::find($message->id));
    }

    public function test_a_store_admin_cannot_open_or_change_a_message_of_another_store(): void
    {
        $this->asStoreAdmin('ZZ-STORE-A');
        $other = $this->makeMessage(['content' => 'ZZ-OTHER-STORE-BODY'], 'ZZ-OTHER-STORE');

        Livewire::test(ContactManager::class)
            ->call('open', $other->id)
            ->assertDontSee('ZZ-OTHER-STORE-BODY')
            ->call('markHandled', $other->id)
            ->call('delete', $other->id);

        $fresh = $other->fresh();
        $this->assertNotNull($fresh);
        $this->assertSame(FrontContact::STATUS_NEW, $fresh->status);
    }

    public function test_an_admin_without_the_permission_cannot_open_the_screen(): void
    {
        $this->asUser(slugs: ['admin_banner']);

        $this->assertDenied(fn () => Livewire::test(ContactManager::class));
    }
}
