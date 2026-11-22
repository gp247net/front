<?php

namespace GP247\Front\Tests\Feature\FrontContact;

use GP247\Front\Models\FrontContact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Group;
use GP247\Front\Tests\Feature\FrontContact\Support\InteractsWithContactForm;
use GP247\Front\Tests\TestCase;

/**
 * A visitor sends a contact message; a valid message is stored for the store of the
 * domain (status "new") before anything else happens, invalid input is refused.
 *
 * @aidlc-unit front-contact
 * @aidlc-story US-front-contact-submit
 * @aidlc-adr front-contact_contact-form-in-front
 */
#[Group('front-contact')]
class ContactSubmitTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithContactForm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->activateRootStore();
        $this->setContactConfig(['contact_status' => 1, 'contact_to_admin' => 0, 'contact_to_customer' => 0]);
    }

    public function test_a_valid_message_is_saved_for_the_store_as_new_and_the_visitor_is_thanked(): void
    {
        $payload = $this->validMessage();

        $this->post($this->contactUrl(), $payload)
            ->assertRedirect($this->contactUrl())
            ->assertSessionHas('contact_sent');

        $message = FrontContact::where('title', $payload['title'])->first();
        $this->assertNotNull($message);
        $this->assertSame($this->rootStoreId(), (string) $message->store_id);
        $this->assertSame(FrontContact::STATUS_NEW, $message->status);
        $this->assertSame('ana.visitor@example.test', $message->email);
        $this->assertSame('Do you ship to Hanoi?', $message->content);
    }

    public function test_the_phone_number_is_optional(): void
    {
        $payload = $this->validMessage(['phone' => '']);

        $this->post($this->contactUrl(), $payload)->assertSessionHasNoErrors();

        $this->assertNull(FrontContact::where('title', $payload['title'])->value('phone'));
    }

    public function test_missing_or_too_long_fields_are_refused_and_nothing_is_saved(): void
    {
        $this->post($this->contactUrl(), $this->validMessage(['name' => '', 'email' => 'not-an-email', 'content' => str_repeat('x', 5001)]))
            ->assertSessionHasErrors(['name', 'email', 'content']);

        $this->post($this->contactUrl(), $this->validMessage(['title' => str_repeat('t', 201)]))
            ->assertSessionHasErrors(['title']);

        $this->assertCount(0, $this->testMessages());
    }

    public function test_store_and_status_sent_by_the_visitor_are_ignored(): void
    {
        $payload = $this->validMessage(['store_id' => 'ZZ-OTHER-STORE', 'status' => '2']);

        $this->post($this->contactUrl(), $payload)->assertSessionHasNoErrors();

        $message = FrontContact::where('title', $payload['title'])->first();
        $this->assertSame($this->rootStoreId(), (string) $message->store_id);
        $this->assertSame(FrontContact::STATUS_NEW, $message->status);
    }

    public function test_markup_in_a_message_is_stored_as_typed(): void
    {
        $payload = $this->validMessage(['content' => '<script>alert(1)</script> & co']);

        $this->post($this->contactUrl(), $payload)->assertSessionHasNoErrors();

        $this->assertSame('<script>alert(1)</script> & co', FrontContact::where('title', $payload['title'])->value('content'));
    }
}
