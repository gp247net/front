<?php

namespace GP247\Front\Support;

/**
 * Handling status of a contact message (column front_contact.status).
 *
 * @aidlc-unit front-contact
 * @aidlc-story US-front-contact-inbox
 * @aidlc-adr front-contact_contact-form-in-front
 */
enum ContactStatus: int
{
    case New = 0;
    case Read = 1;
    case Handled = 2;

    /**
     * @return string Language key of the status label.
     */
    public function labelKey(): string
    {
        return 'admin.contact.status_' . strtolower($this->name);
    }

    /**
     * Parse the admin list filter. WHY strict: the value comes from the browser and
     * feeds a query, so only an exact known status counts; anything else means "all".
     *
     * @param string $value Raw filter value.
     * @return self|null The status, or null for "no filter".
     */
    public static function fromFilter(string $value): ?self
    {
        if (!ctype_digit($value)) {
            return null;
        }

        return self::tryFrom((int) $value);
    }
}
