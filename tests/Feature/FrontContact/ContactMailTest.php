<?php

namespace GP247\Front\Tests\Feature\FrontContact;

use GP247\Core\Mail\SendMail;
use GP247\Front\Models\FrontContact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Group;
use GP247\Front\Tests\Feature\FrontContact\Support\InteractsWithContactForm;
use GP247\Front\Tests\TestCase;

/**
 * After a message is stored the owner is mailed (contact_to_admin) and, when chosen,
 * the visitor gets a confirmation (contact_to_customer); mail being off or failing
 * never loses the message.
 *
 * @aidlc-unit front-contact
 * @aidlc-story US-front-contact-notify-mail
 * @aidlc-adr front-contact_contact-form-in-front
 */
#[Group('front-contact')]
class ContactMailTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithContactForm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->activateRootStore();
        $this->enableSystemMail();
    }

    public function test_the_store_owner_is_mailed_about_a_new_message_with_reply_to_the_visitor(): void
    {
        Mail::fake();
        $this->setContactConfig(['contact_status' => 1, 'contact_to_admin' => 1, 'contact_to_customer' => 0]);

        $this->post($this->contactUrl(), $this->validMessage())->assertSessionHasNoErrors();

        Mail::assertSent(SendMail::class, 1);
        Mail::assertSent(SendMail::class, fn (SendMail $m) => $m->config['to'] === 'shop-owner@example.test'
            && $m->config['replyTo'] === 'ana.visitor@example.test'
            && str_ends_with($m->view, 'email.front_contact_to_admin'));
    }

    public function test_the_visitor_gets_a_confirmation_when_the_owner_chose_it(): void
    {
        Mail::fake();
        $this->setContactConfig(['contact_status' => 1, 'contact_to_admin' => 0, 'contact_to_customer' => 1]);

        $this->post($this->contactUrl(), $this->validMessage())->assertSessionHasNoErrors();

        Mail::assertSent(SendMail::class, 1);
        Mail::assertSent(SendMail::class, fn (SendMail $m) => $m->config['to'] === 'ana.visitor@example.test'
            && $m->config['replyTo'] === 'shop-owner@example.test'
            && str_ends_with($m->view, 'email.front_contact_to_customer'));
    }

    public function test_the_confirmation_to_the_visitor_repeats_nothing_the_visitor_typed(): void
    {
        Mail::fake();
        $this->setContactConfig(['contact_status' => 1, 'contact_to_admin' => 0, 'contact_to_customer' => 1]);
        $payload = $this->validMessage(['name' => 'ZZ-NAME http://spam.example', 'title' => 'ZZ contact test ZZ-TITLE-LINK', 'content' => 'ZZ-SECRET-BODY']);

        $this->post($this->contactUrl(), $payload)->assertSessionHasNoErrors();

        Mail::assertSent(SendMail::class, function (SendMail $m) {
            $html = view($m->view, $m->dataView)->render();

            return !str_contains($html, 'ZZ-NAME') && !str_contains($html, 'ZZ-TITLE-LINK')
                && !str_contains($html, 'ZZ-SECRET-BODY') && !str_contains($m->config['subject'], 'ZZ-');
        });
    }

    public function test_with_system_mail_off_nothing_is_sent_but_the_message_is_kept(): void
    {
        Mail::fake();
        $this->setCoreConfig('email_action_mode', 0);
        $this->setContactConfig(['contact_status' => 1, 'contact_to_admin' => 1, 'contact_to_customer' => 1]);
        $payload = $this->validMessage();

        $this->post($this->contactUrl(), $payload)->assertSessionHas('contact_sent');

        Mail::assertNothingSent();
        $this->assertTrue(FrontContact::where('title', $payload['title'])->exists());
    }

    public function test_a_failing_mail_server_does_not_lose_the_message(): void
    {
        Mail::shouldReceive('send')->andThrow(new \RuntimeException('smtp down'));
        $this->setContactConfig(['contact_status' => 1, 'contact_to_admin' => 1, 'contact_to_customer' => 1]);
        $payload = $this->validMessage();

        $this->post($this->contactUrl(), $payload)->assertSessionHas('contact_sent');

        $this->assertTrue(FrontContact::where('title', $payload['title'])->exists());
    }
}
