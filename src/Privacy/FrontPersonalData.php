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

    /** @var array<string, string> Front tables that look personal by column names but hold no customer data. */
    public const NOT_CUSTOMER_DATA = [
        'front_banner' => 'banners of the shop',
        'front_banner_type' => 'list of banner places',
        'front_country' => 'list of countries',
        'front_layout_block' => 'page blocks of the shop',
        'front_link' => 'menu links of the shop',
        'front_link_group' => 'list of menu groups',
        'front_page_description' => 'page texts written by the shop',
        'front_tracking_revision' => 'changes made by administrators',
        'front_tracking_snippet' => 'tracking code of the shop',
    ];

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
        // WHY method_exists: a core without the undeclared-source detection does not have it.
        if (method_exists(PersonalDataRegistry::class, 'notCustomerData')) {
            foreach (self::NOT_CUSTOMER_DATA as $table => $reason) {
                PersonalDataRegistry::notCustomerData($table, $reason);
            }
        }
    }
}
