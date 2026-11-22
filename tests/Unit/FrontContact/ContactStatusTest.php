<?php

namespace GP247\Front\Tests\Unit\FrontContact;

use GP247\Front\Support\ContactStatus;
use PHPUnit\Framework\TestCase;

/**
 * Status vocabulary of a contact message: three fixed values, a language key per
 * value, and a strict parser for the admin list filter (anything else is "no filter").
 *
 * @aidlc-unit front-contact
 */
class ContactStatusTest extends TestCase
{
    public function test_the_three_statuses_keep_their_stored_values(): void
    {
        $this->assertSame(0, ContactStatus::New->value);
        $this->assertSame(1, ContactStatus::Read->value);
        $this->assertSame(2, ContactStatus::Handled->value);
    }

    public function test_each_status_has_its_own_label_key(): void
    {
        $keys = array_map(static fn (ContactStatus $s): string => $s->labelKey(), ContactStatus::cases());

        $this->assertSame(['admin.contact.status_new', 'admin.contact.status_read', 'admin.contact.status_handled'], $keys);
    }

    public function test_the_filter_parser_accepts_only_known_values(): void
    {
        $this->assertSame(ContactStatus::Handled, ContactStatus::fromFilter('2'));
        $this->assertSame(ContactStatus::New, ContactStatus::fromFilter('0'));
        $this->assertNull(ContactStatus::fromFilter(''));
        $this->assertNull(ContactStatus::fromFilter('9'));
        $this->assertNull(ContactStatus::fromFilter('1 OR 1=1'));
        $this->assertNull(ContactStatus::fromFilter('abc'));
    }
}
