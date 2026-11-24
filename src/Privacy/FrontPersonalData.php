<?php

namespace GP247\Front\Privacy;

use GP247\Core\PersonalData\Application\PersonalDataRegistry;
use GP247\Core\PersonalData\Domain\PersonalDataSource;
use GP247\Core\PersonalData\Domain\SubjectLink;

/**
 * Where gp247/front keeps personal data of customers: messages sent through the contact form
 * and newsletter sign-ups, both found by email and deleted when the customer is erased. Does
 * nothing on a core without the personal data registry, so this package keeps running there.
 *
 * Labels are given by locale here, so no language row has to be added for them.
 *
 * @aidlc-unit customer-privacy
 * @aidlc-story US-customer-privacy-declare-shop-data
 * @aidlc-adr personal-data-registry_source-declaration
 */
final class FrontPersonalData
{
    public const OWNER = 'gp247/front';

    /**
     * @return void
     */
    public static function register(): void
    {
        if (!class_exists(PersonalDataRegistry::class)) {
            return;
        }
        PersonalDataRegistry::declare(
            PersonalDataSource::table('front_contact')
                ->link(SubjectLink::email('email'))
                ->deleteRows()
                ->identity(['name', 'phone'])
                ->export(['name', 'email', 'phone', 'title', 'content', 'created_at'])
                ->order(60)
                ->describe(['en' => 'Contact form messages', 'vi' => 'Tin nhắn liên hệ', 'ja' => 'お問い合わせ'], self::OWNER)
        );
        PersonalDataRegistry::declare(
            PersonalDataSource::table('front_subscribe')
                ->link(SubjectLink::email('email'))
                ->deleteRows()
                ->export(['email', 'phone', 'created_at'])
                ->order(60)
                ->describe(['en' => 'Newsletter sign-ups', 'vi' => 'Đăng ký nhận tin', 'ja' => 'ニュースレター登録'], self::OWNER)
        );
    }
}
