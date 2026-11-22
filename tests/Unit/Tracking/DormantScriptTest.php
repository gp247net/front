<?php

namespace GP247\Front\Tests\Unit\Tracking;

use GP247\Front\Tracking\Domain\ConsentCategory;
use GP247\Front\Tracking\Domain\DormantScript;
use GP247\Front\Tracking\Domain\ServiceTagFactory;
use GP247\Front\Tracking\Domain\SnippetRegion;
use GP247\Front\Tracking\Domain\TrackingSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Code is printed so the browser never runs it on its own, and comes back unchanged
 * when the loader reads it (decision D1 of the TC-1 plan).
 *
 * @aidlc-unit tracking-consent
 * @aidlc-adr tracking-consent_deferred-script-activation
 */
#[Group('tracking-consent')]
class DormantScriptTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function awkwardCode(): array
    {
        return [
            'closing script tag' => ['<script>document.write("</script><script>alert(1)</script>")</script>'],
            'escaped closing tag idiom' => ["<script>document.write('<script src=x><\\/script>')</script>"],
            'closing template tag' => ['</template><script>alert(1)</script>'],
            'html comment opener' => ['<!--<script>x()</script>-->'],
            'quotes and ampersands' => ['<img src="a?b=1&c=2" alt=\'x\'>'],
            'unicode and line separators' => ["<script>var s='Tiếng Việt 日本語 \u{2028}';</script>"],
        ];
    }

    #[DataProvider('awkwardCode')]
    public function test_code_cannot_leave_its_dormant_tag_and_reads_back_unchanged(string $code): void
    {
        $html = DormantScript::wrap($code, ConsentCategory::MARKETING, SnippetRegion::HEAD);

        $this->assertMatchesRegularExpression('#^<script type="text/plain" data-gp247-consent="marketing" data-gp247-region="head">[^<]*</script>$#u', $html);
        preg_match('#^<script[^>]*>(.*)</script>$#su', $html, $m);
        $this->assertSame($code, json_decode($m[1], true, 512, JSON_THROW_ON_ERROR));
    }

    public function test_the_service_is_named_on_the_tag(): void
    {
        $html = DormantScript::wrap('<script></script>', ConsentCategory::ANALYTICS, SnippetRegion::HEAD, 'ga4');

        $this->assertStringStartsWith('<script type="text/plain" data-gp247-consent="analytics" data-gp247-region="head" data-gp247-service="ga4">', $html);
    }

    public function test_preset_services_get_their_standard_code_and_cookie_group(): void
    {
        $settings = TrackingSettings::fromStored([
            'tracking_ga4_id' => 'G-ABC123',
            'tracking_gtm_id' => 'GTM-XYZ789',
            'tracking_gtm_category' => 'analytics',
            'tracking_pixel_id' => '1234567890',
        ]);

        $tags = ServiceTagFactory::forSettings($settings);
        $byService = [];
        foreach ($tags as $tag) {
            $byService[$tag->service] = $tag;
        }

        $this->assertSame(['ga4', 'gtm', 'pixel'], array_keys($byService));
        $this->assertSame(ConsentCategory::ANALYTICS, $byService['ga4']->category);
        $this->assertSame(ConsentCategory::ANALYTICS, $byService['gtm']->category);
        $this->assertSame(ConsentCategory::MARKETING, $byService['pixel']->category);
        $this->assertStringContainsString('https://www.googletagmanager.com/gtag/js?id=G-ABC123', $byService['ga4']->html);
        $this->assertStringContainsString('gtag(\'config\',"G-ABC123")', $byService['ga4']->html);
        $this->assertStringContainsString('"GTM-XYZ789"', $byService['gtm']->html);
        $this->assertStringContainsString('fbq(\'init\',"1234567890")', $byService['pixel']->html);
        $this->assertStringNotContainsString('<noscript', implode('', array_map(static fn ($t) => $t->html, $tags)));
    }

    public function test_no_preset_code_when_every_id_is_empty(): void
    {
        $this->assertSame([], ServiceTagFactory::forSettings(TrackingSettings::defaults()));
    }
}
