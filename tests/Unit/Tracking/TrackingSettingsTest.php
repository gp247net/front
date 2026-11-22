<?php

namespace GP247\Front\Tests\Unit\Tracking;

use GP247\Front\Tracking\Domain\ConsentCategory;
use GP247\Front\Tracking\Domain\TrackingSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Format rules of the GA4 / GTM / Meta Pixel ids and how stored values are read back.
 *
 * @aidlc-unit tracking-consent
 * @aidlc-adr tracking-consent_config-storage-and-authority
 */
#[Group('tracking-consent')]
class TrackingSettingsTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function validIds(): array
    {
        return [
            'ga4' => ['ga4', 'G-ABC123XYZ9'],
            'gtm' => ['gtm', 'GTM-5XK2P7Q'],
            'pixel' => ['pixel', '123456789012345'],
        ];
    }

    #[DataProvider('validIds')]
    public function test_a_well_formed_id_passes(string $field, string $id): void
    {
        $this->assertSame([], TrackingSettings::validateIds([$field => $id]));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidIds(): array
    {
        return [
            'ga4 universal analytics id' => ['ga4', 'UA-12345-1'],
            'ga4 too short' => ['ga4', 'G-AB'],
            'ga4 with a script' => ['ga4', 'G-ABCD"><script>'],
            'gtm without prefix' => ['gtm', '5XK2P7Q'],
            'gtm with space inside' => ['gtm', 'GTM-5XK 2P7'],
            'pixel with letters' => ['pixel', '12345abc'],
            'pixel too short' => ['pixel', '1234'],
            'pixel too long' => ['pixel', str_repeat('9', 21)],
        ];
    }

    #[DataProvider('invalidIds')]
    public function test_a_malformed_id_is_refused_with_a_message_for_that_field(string $field, string $id): void
    {
        $errors = TrackingSettings::validateIds([$field => $id]);

        $this->assertSame(['admin.tracking.error_' . $field], array_values($errors));
        $this->assertSame([$field], array_keys($errors));
    }

    public function test_empty_ids_are_allowed(): void
    {
        $this->assertSame([], TrackingSettings::validateIds(['ga4' => '', 'gtm' => '  ', 'pixel' => '']));
    }

    public function test_surrounding_spaces_and_lower_case_letters_are_normalised(): void
    {
        $this->assertSame('G-ABC123', TrackingSettings::normalizeId('ga4', '  g-abc123 '));
        $this->assertSame('GTM-ABCD12', TrackingSettings::normalizeId('gtm', 'gtm-abcd12'));
        $this->assertSame('1234567', TrackingSettings::normalizeId('pixel', ' 1234567 '));
    }

    public function test_the_gtm_group_must_be_analytics_or_marketing(): void
    {
        $this->assertSame(['gtm_category' => 'admin.tracking.error_category'], TrackingSettings::validateIds(['gtm_category' => 'necessary']));
        $this->assertSame([], TrackingSettings::validateIds(['gtm_category' => 'analytics']));
    }

    public function test_defaults_are_empty_switched_on_and_banner_off(): void
    {
        $settings = TrackingSettings::defaults();

        $this->assertTrue($settings->enabled);
        $this->assertSame('', $settings->ga4Id);
        $this->assertSame(ConsentCategory::MARKETING, $settings->gtmCategory);
        $this->assertSame(TrackingSettings::MODE_OFF, $settings->bannerMode);
        $this->assertFalse($settings->hasAnyService());
    }

    public function test_a_stored_value_that_breaks_the_rules_is_read_as_empty(): void
    {
        $settings = TrackingSettings::fromStored([
            'tracking_ga4_id' => 'G-<b>bad',
            'tracking_pixel_id' => '1234567',
            'tracking_gtm_category' => 'necessary',
            'tracking_banner_mode' => 'something',
        ]);

        $this->assertSame('', $settings->ga4Id);
        $this->assertSame('1234567', $settings->pixelId);
        $this->assertSame(ConsentCategory::MARKETING, $settings->gtmCategory);
        $this->assertSame(TrackingSettings::MODE_OFF, $settings->bannerMode);
        $this->assertTrue($settings->hasAnyService());
    }

    public function test_the_sample_text_of_the_id_fields_is_never_taken_for_a_real_id(): void
    {
        $this->assertSame(
            ['ga4' => 'admin.tracking.error_placeholder', 'gtm' => 'admin.tracking.error_placeholder'],
            TrackingSettings::validateIds(['ga4' => 'G-XXXXXXXXXX', 'gtm' => 'GTM-XXXXXXX']),
        );
        $this->assertSame([], TrackingSettings::validateIds(['ga4' => 'G-XXXX1234', 'gtm' => 'GTM-XX12XXX']));

        $stored = TrackingSettings::fromStored(['tracking_ga4_id' => 'G-XXXXXXXXXX', 'tracking_gtm_id' => 'GTM-XXXXXXX']);
        $this->assertSame('', $stored->ga4Id, 'a sample id saved before this rule prints nothing');
        $this->assertSame('', $stored->gtmId);
    }

    public function test_the_policy_page_and_the_banner_text_per_language_are_read_back(): void
    {
        $settings = TrackingSettings::fromStored([
            'tracking_policy_page' => 'cookie-policy',
            'tracking_banner_text' => json_encode(['vi' => 'Chúng tôi dùng cookie', 'en' => '  ', 'ja' => ['x'], 'xx' => 'ok']),
        ]);

        $this->assertSame('cookie-policy', $settings->policyPage);
        $this->assertSame(['vi' => 'Chúng tôi dùng cookie', 'xx' => 'ok'], $settings->bannerText);
        $this->assertSame('Chúng tôi dùng cookie', $settings->bannerTextFor('vi'));
        $this->assertSame('', $settings->bannerTextFor('en'));
    }

    public function test_a_broken_banner_text_or_page_is_read_as_empty(): void
    {
        $settings = TrackingSettings::fromStored(['tracking_banner_text' => '{not json', 'tracking_policy_page' => 'bad alias/../x']);

        $this->assertSame([], $settings->bannerText);
        $this->assertSame('', $settings->policyPage);
    }

    public function test_banner_settings_are_checked_field_by_field(): void
    {
        $errors = TrackingSettings::validateBanner(
            ['mode' => 'always', 'page' => 'not-a-page', 'text' => ['vi' => str_repeat('a', TrackingSettings::MAX_BANNER_TEXT + 1), 'en' => 'fine']],
            ['cookie-policy'],
        );

        $this->assertSame([
            'mode' => 'admin.tracking.error_banner_mode',
            'page' => 'admin.tracking.error_policy_page',
            'text.vi' => 'admin.tracking.error_banner_text_long',
        ], $errors);
        $this->assertSame([], TrackingSettings::validateBanner(['mode' => 'optin', 'page' => '', 'text' => ['vi' => 'ok']], []));
        $this->assertSame([], TrackingSettings::validateBanner(['mode' => 'notice', 'page' => 'cookie-policy', 'text' => []], ['cookie-policy']));
    }

    public function test_switching_off_keeps_every_other_value(): void
    {
        $on = TrackingSettings::fromStored(['tracking_ga4_id' => 'G-ABCD1234', 'tracking_banner_mode' => 'optin', 'tracking_policy_page' => 'p', 'tracking_banner_text' => '{"vi":"x"}']);
        $off = $on->switchedOff();

        $this->assertFalse($off->enabled);
        $this->assertSame('G-ABCD1234', $off->ga4Id);
        $this->assertSame('optin', $off->bannerMode);
        $this->assertSame('p', $off->policyPage);
        $this->assertSame(['vi' => 'x'], $off->bannerText);
    }
}
