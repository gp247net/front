<?php

namespace GP247\Front\Tests\Unit\Tracking;

use GP247\Front\Tracking\Domain\CodeSnippet;
use GP247\Front\Tracking\Domain\ConsentCategory;
use GP247\Front\Tracking\Domain\InvalidTrackingSetting;
use GP247\Front\Tracking\Domain\SnippetRegion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Rules of a custom code block: kept verbatim, region and group from a fixed list, size limits.
 *
 * @aidlc-unit tracking-consent
 * @aidlc-adr tracking-consent_config-storage-and-authority
 */
#[Group('tracking-consent')]
class CodeSnippetTest extends TestCase
{
    public function test_content_is_kept_exactly_as_typed(): void
    {
        $code = "  <script>var a = '</script>'; {{ \$x }} @php echo 1; @endphp</script>\n";

        $snippet = CodeSnippet::fromInput(['region' => 'body_close', 'category' => 'analytics', 'label' => ' Clarity ', 'content' => $code, 'sort' => '3', 'status' => true]);

        $this->assertSame($code, $snippet->content);
        $this->assertSame('Clarity', $snippet->label);
        $this->assertSame(SnippetRegion::BODY_CLOSE, $snippet->region);
        $this->assertSame(ConsentCategory::ANALYTICS, $snippet->category);
        $this->assertSame(3, $snippet->sort);
        $this->assertTrue($snippet->active);
    }

    public function test_marketing_is_the_group_when_none_is_given(): void
    {
        $snippet = CodeSnippet::fromInput(['region' => 'head', 'content' => '<script></script>']);

        $this->assertSame(ConsentCategory::MARKETING, $snippet->category);
    }

    public function test_unknown_region_group_or_empty_content_are_refused_per_field(): void
    {
        try {
            CodeSnippet::listFromInput([
                ['region' => 'footer', 'category' => 'necessary', 'content' => ' '],
            ]);
            $this->fail('expected InvalidTrackingSetting');
        } catch (InvalidTrackingSetting $e) {
            $this->assertSame([
                'snippets.0.region' => 'admin.tracking.error_region',
                'snippets.0.category' => 'admin.tracking.error_category',
                'snippets.0.content' => 'admin.tracking.error_content_required',
            ], $e->errors());
        }
    }

    public function test_oversized_content_and_label_are_refused(): void
    {
        try {
            CodeSnippet::listFromInput([
                ['region' => 'head', 'label' => str_repeat('x', CodeSnippet::MAX_LABEL + 1), 'content' => str_repeat('a', CodeSnippet::MAX_CONTENT + 1)],
            ]);
            $this->fail('expected InvalidTrackingSetting');
        } catch (InvalidTrackingSetting $e) {
            $this->assertSame('admin.tracking.error_label_long', $e->errors()['snippets.0.label']);
            $this->assertSame('admin.tracking.error_content_long', $e->errors()['snippets.0.content']);
        }
    }

    public function test_a_new_block_needs_at_least_one_html_tag(): void
    {
        foreach (['11111111111111', "gtag('config', 'AW-123');", '<!-- only a comment -->', 'a < b'] as $content) {
            try {
                CodeSnippet::listFromInput([['region' => 'head', 'content' => $content]]);
                $this->fail('expected InvalidTrackingSetting for ' . $content);
            } catch (InvalidTrackingSetting $e) {
                $this->assertSame(['snippets.0.content' => 'admin.tracking.error_content_no_tag'], $e->errors(), $content);
            }
        }

        foreach (['<script>t()</script>', '<img src="https://x.test/p.gif">', "<!-- Clarity -->\n<SCRIPT async src=\"https://x.test/c.js\"></SCRIPT>", '<meta name="x" content="y">'] as $content) {
            $this->assertCount(1, CodeSnippet::listFromInput([['region' => 'head', 'content' => $content]]), $content);
        }
    }

    public function test_a_stored_block_without_tags_can_still_be_read_back(): void
    {
        $snippet = CodeSnippet::fromInput(['region' => 'body_open', 'content' => '11111111111111']);

        $this->assertSame('11111111111111', $snippet->content);
    }

    public function test_more_blocks_than_the_limit_are_refused(): void
    {
        $rows = array_fill(0, CodeSnippet::MAX_PER_STORE + 1, ['region' => 'head', 'content' => '<script></script>']);

        $this->expectException(InvalidTrackingSetting::class);
        CodeSnippet::listFromInput($rows);
    }

    public function test_blocks_longer_than_the_store_total_are_refused(): void
    {
        $block = ['region' => 'head', 'content' => '<p>' . str_repeat('a', CodeSnippet::MAX_CONTENT - 3)];

        try {
            CodeSnippet::listFromInput([$block, $block, $block]);
            $this->fail('expected InvalidTrackingSetting');
        } catch (InvalidTrackingSetting $e) {
            $this->assertSame(['snippets' => 'admin.tracking.error_total_long'], $e->errors());
        }
        $this->assertCount(2, CodeSnippet::listFromInput([$block, $block]));
    }

    public function test_a_block_round_trips_through_its_array_form(): void
    {
        $snippet = CodeSnippet::fromInput(['region' => 'body_open', 'category' => 'marketing', 'label' => 'TikTok', 'content' => '<script>t()</script>', 'sort' => 2, 'status' => false]);

        $this->assertEquals($snippet, CodeSnippet::fromInput($snippet->toArray()));
    }
}
