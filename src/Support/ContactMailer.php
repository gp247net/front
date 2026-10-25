<?php

namespace GP247\Front\Support;

use GP247\Front\Models\FrontContact;

/**
 * Mails sent after a contact message is stored: an alert to the store (reply-to
 * the visitor) and, when chosen, a generic confirmation to the visitor. Delivery
 * goes through gp247_mail_send (system mail on/off, queue, empty-recipient guard).
 * Nothing here may break the submission — the message is already saved
 * (NFR-AVAIL-contact-message-durable) — so every failure is reported and swallowed.
 *
 * @aidlc-unit front-contact
 * @aidlc-story US-front-contact-notify-mail
 * @aidlc-adr front-contact_contact-form-in-front
 */
final class ContactMailer
{
    /**
     * @param FrontContact $contact Stored message.
     * @return void
     */
    public function send(FrontContact $contact): void
    {
        try {
            $this->sendAll($contact);
        } catch (\Throwable $e) {
            gp247_report('[front-contact] mail for message ' . $contact->id . ' not sent: ' . $e->getMessage());
        }
    }

    /**
     * @param FrontContact $contact
     * @return void
     */
    private function sendAll(FrontContact $contact): void
    {
        $storeEmail = (string) gp247_store_info('email', '', $contact->store_id);
        // WHY plain, pre-rendered data: the queue path serialises it into a job and
        // renders in a worker that knows neither the visitor's store nor language.
        $common = ['storeTitle' => (string) gp247_store_info('title', '', $contact->store_id)];

        if (ContactSettings::get('contact_to_admin', $contact->store_id)) {
            $this->deliver('email.front_contact_to_admin', $common + [
                'contact' => $contact->only(['name', 'email', 'phone', 'title', 'content']),
                'intro' => gp247_language_render('front.contact.mail_admin_intro'),
                'labels' => [
                    'name' => gp247_language_render('contact.name'),
                    'email' => gp247_language_render('contact.email'),
                    'phone' => gp247_language_render('contact.phone'),
                    'title' => gp247_language_render('contact.subject'),
                ],
            ], [
                'to' => $storeEmail,
                'replyTo' => $contact->email,
                'subject' => gp247_language_render('front.contact.mail_subject_to_admin', ['title' => self::headerSafe($contact->title)]),
            ]);
        }

        if (ContactSettings::get('contact_to_customer', $contact->store_id)) {
            // WHY generic: the address was typed by an anonymous visitor, so nothing
            // they wrote is echoed back (RISK-SEC-contact-spam-relay).
            $this->deliver('email.front_contact_to_customer', $common + [
                'intro' => gp247_language_render('front.contact.mail_customer_received'),
            ], [
                'to' => $contact->email,
                'replyTo' => $storeEmail,
                'subject' => gp247_language_render('front.contact.mail_subject_to_customer'),
            ]);
        }
    }

    /**
     * @param string               $subPath Mail view inside the template.
     * @param array<string, mixed> $data    View data.
     * @param array<string, mixed> $config  to / replyTo / subject.
     * @return void
     */
    private function deliver(string $subPath, array $data, array $config): void
    {
        try {
            gp247_mail_send(gp247_front_view($subPath), $data, $config);
        } catch (\Throwable $e) {
            gp247_report('[front-contact] ' . $subPath . ' not sent: ' . $e->getMessage());
        }
    }

    /**
     * Visitor text going into a mail header: line breaks and tabs become spaces.
     *
     * @param string $value
     * @return string
     */
    private static function headerSafe(string $value): string
    {
        return trim((string) preg_replace('/[\r\n\t]+/', ' ', $value));
    }
}
