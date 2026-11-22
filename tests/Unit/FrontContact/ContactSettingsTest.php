<?php

namespace GP247\Front\Tests\Unit\FrontContact;

use GP247\Front\Support\ContactSettings;
use PHPUnit\Framework\TestCase;

/**
 * Resolution of a contact setting: the store's own row wins, then the root store's
 * row, then the built-in default (form off, owner mailed, visitor not mailed).
 *
 * @aidlc-unit front-contact
 */
class ContactSettingsTest extends TestCase
{
    public function test_the_store_value_wins_over_the_root_value(): void
    {
        $this->assertFalse(ContactSettings::resolve('contact_status', ['contact_status' => '0'], ['contact_status' => '1']));
        $this->assertTrue(ContactSettings::resolve('contact_status', ['contact_status' => '1'], ['contact_status' => '0']));
    }

    public function test_a_store_without_a_row_follows_the_root_store(): void
    {
        $this->assertTrue(ContactSettings::resolve('contact_to_customer', [], ['contact_to_customer' => '1']));
    }

    public function test_without_any_row_the_defaults_apply(): void
    {
        $this->assertFalse(ContactSettings::resolve('contact_status', [], []));
        $this->assertTrue(ContactSettings::resolve('contact_to_admin', [], []));
        $this->assertFalse(ContactSettings::resolve('contact_to_customer', [], []));
    }

    public function test_an_unknown_key_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ContactSettings::resolve('captcha_mode', [], []);
    }
}
