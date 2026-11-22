<?php

namespace GP247\Front\Tests\Feature\FrontendTemplateDev;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\View;
use GP247\Front\Tests\TestCase;

/**
 * Feature-Test — a storefront list pages with a real pager.
 *
 * The partial used to hand the job to Laravel's own paginator view. That view is
 * styled with Tailwind classes that live inside the framework, which this
 * template's bundle never scanned — the CSS is compiled ahead of time and there
 * is no JIT at runtime. The classes resolved to nothing and
 * every list on the storefront ended in a bare column of page numbers.
 *
 * These cases pin the markup the template owns: one row, a marked current page,
 * previous and next, and the query string carried across pages.
 *
 * @aidlc-unit frontend-template-dev
 * @aidlc-story US-TPL-009
 * @aidlc-adr ADR-014
 */
class PaginationPartialTest extends TestCase
{
    private function render(int $total, int $perPage, int $currentPage, array $query = []): string
    {
        request()->merge($query);

        $items = new LengthAwarePaginator(
            range(1, min($perPage, $total)),
            $total,
            $perPage,
            $currentPage,
            ['path' => 'http://localhost/list']
        );

        return View::make('GP247TemplatePath::GP247Front.common.pagination', ['items' => $items])->render();
    }

    public function test_a_list_that_spans_several_pages_gets_one_row_of_page_links(): void
    {
        $html = $this->render(total: 90, perPage: 12, currentPage: 1);

        $this->assertStringContainsString('data-testid="front-pagination"', $html);
        $this->assertStringContainsString('flex flex-wrap items-center justify-center', $html, 'the pager must lay out as a row, not a column');
        $this->assertStringContainsString('data-testid="front-pagination-next"', $html);
        // Page 1 of 8: the first page has nowhere to go back to.
        $this->assertStringNotContainsString('data-testid="front-pagination-prev"', $html);
        $this->assertStringContainsString('aria-current="page"', $html, 'the page being read must be marked');
        $this->assertStringContainsString('>8</a>', $html, 'the last page stays one click away on a long list');
    }

    public function test_a_middle_page_offers_both_directions(): void
    {
        $html = $this->render(total: 90, perPage: 12, currentPage: 4);

        $this->assertStringContainsString('data-testid="front-pagination-prev"', $html);
        $this->assertStringContainsString('data-testid="front-pagination-next"', $html);
    }

    public function test_a_single_page_list_shows_no_pager_at_all(): void
    {
        $this->assertSame('', trim($this->render(total: 9, perPage: 12, currentPage: 1)));
    }

    public function test_filters_survive_a_page_change(): void
    {
        $html = $this->render(total: 90, perPage: 12, currentPage: 1, query: ['q' => 'hammer', 'cat' => 'VC-1']);

        $this->assertStringContainsString('q=hammer', $html, 'changing page must not drop the search');
        $this->assertStringContainsString('cat=VC-1', $html, 'changing page must not drop the category filter');
    }
}
