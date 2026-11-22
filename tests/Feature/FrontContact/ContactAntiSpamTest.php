<?php

namespace GP247\Front\Tests\Feature\FrontContact;

use GP247\Core\Mail\SendMail;
use GP247\Front\Controllers\ContactController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Group;
use GP247\Front\Tests\Feature\FrontContact\Support\FakeContactCaptcha;
use GP247\Front\Tests\Feature\FrontContact\Support\InteractsWithContactForm;
use GP247\Front\Tests\TestCase;

/**
 * The public contact form keeps bots out: a filled honeypot is dropped silently,
 * bursts are throttled per IP, and the captcha is enforced when the owner ticked
 * the "Contact" page on the Captcha tab.
 *
 * @aidlc-unit front-contact
 * @aidlc-story US-front-contact-anti-spam
 * @aidlc-adr front-contact_contact-form-in-front
 */
#[Group('front-contact')]
class ContactAntiSpamTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithContactForm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->activateRootStore();
        $this->enableSystemMail();
        $this->setContactConfig(['contact_status' => 1, 'contact_to_admin' => 1, 'contact_to_customer' => 1]);
    }

    /**
     * Point the shop captcha settings at the fake captcha (or switch captcha off).
     *
     * @param bool $onContactPage Whether "contact" is in the captcha page list.
     * @return void
     */
    private function configureCaptcha(bool $onContactPage): void
    {
        $this->setCoreConfig('captcha_mode', 1);
        $this->setCoreConfig('captcha_method', FakeContactCaptcha::class);
        $this->setCoreConfig('captcha_page', json_encode($onContactPage ? ['contact'] : ['register']));
        $this->setCoreConfig(FakeContactCaptcha::CONFIG_KEY, 1);
        view()->addNamespace('zz-fake-contact-captcha', __DIR__ . '/Support/views');
    }

    public function test_a_bot_filling_the_hidden_field_is_thanked_but_nothing_is_saved_or_mailed(): void
    {
        Mail::fake();

        $this->post($this->contactUrl(), $this->validMessage([ContactController::HONEYPOT => 'http://spam.example']))
            ->assertRedirect($this->contactUrl())
            ->assertSessionHas('contact_sent');

        $this->assertCount(0, $this->testMessages());
        Mail::assertNotSent(SendMail::class);
    }

    public function test_more_than_five_messages_a_minute_from_one_address_are_blocked(): void
    {
        Mail::fake();
        for ($i = 0; $i < 5; $i++) {
            $this->post($this->contactUrl(), $this->validMessage())->assertRedirect();
        }

        $this->post($this->contactUrl(), $this->validMessage())->assertStatus(429);

        $this->assertCount(5, $this->testMessages());
    }

    public function test_with_captcha_on_the_contact_page_a_wrong_answer_is_refused(): void
    {
        Mail::fake();
        $this->configureCaptcha(onContactPage: true);

        $this->get($this->contactUrl())->assertSee('ZZ-FAKE-CAPTCHA', false);
        $this->post($this->contactUrl(), $this->validMessage([FakeContactCaptcha::FIELD => 'wrong']))
            ->assertSessionHasErrors('captcha_field');
        $this->assertCount(0, $this->testMessages());

        $this->post($this->contactUrl(), $this->validMessage([FakeContactCaptcha::FIELD => FakeContactCaptcha::ANSWER]))
            ->assertSessionHasNoErrors();
        $this->assertCount(1, $this->testMessages());
    }

    public function test_captcha_ticked_for_other_pages_only_does_not_block_the_contact_form(): void
    {
        Mail::fake();
        $this->configureCaptcha(onContactPage: false);

        $this->post($this->contactUrl(), $this->validMessage())->assertSessionHasNoErrors();

        $this->assertCount(1, $this->testMessages());
    }
}
