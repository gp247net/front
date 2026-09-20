{{--
    Pagination for every storefront list.

    WHY the markup is written out instead of calling ->links(): Laravel's own
    paginator view is styled with Tailwind classes that live in the framework's
    views, which this template's compiled bundle never scanned (rule gp247.md
    §3b — the CSS is built ahead of time, there is no JIT at runtime). The
    classes therefore had no rules behind them and the pager rendered as a bare
    vertical list of numbers. Everything below is in the bundle.

    Variables: $items (paginator).

    @aidlc-unit frontend-template-dev
    @aidlc-story US-TPL-009
    @aidlc-adr ADR-014
--}}
@php
    $items = $items->appends(request()->except(['page', '_token']));
@endphp

@if ($items->hasPages())
    <nav class="flex flex-wrap items-center justify-center gap-1 mt-8" role="navigation" aria-label="{{ gp247_language_render('front.pagination') ?: 'Pagination' }}" data-testid="front-pagination">
        {{-- Previous --}}
        @if ($items->onFirstPage())
            <span class="inline-flex items-center rounded-lg border border-ink-100 px-3 py-2 text-sm text-ink-300" aria-disabled="true">&lsaquo;</span>
        @else
            <a href="{{ $items->previousPageUrl() }}" rel="prev" class="inline-flex items-center rounded-lg border border-ink-200 px-3 py-2 text-sm text-ink-700 transition hover:bg-ink-50" data-testid="front-pagination-prev">&lsaquo;</a>
        @endif

        {{-- Page numbers --}}
        @foreach ($items->getUrlRange(max(1, $items->currentPage() - 2), min($items->lastPage(), $items->currentPage() + 2)) as $page => $url)
            @if ($page == $items->currentPage())
                <span class="inline-flex items-center rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white" aria-current="page">{{ $page }}</span>
            @else
                <a href="{{ $url }}" class="inline-flex items-center rounded-lg border border-ink-200 px-3 py-2 text-sm text-ink-700 transition hover:bg-ink-50">{{ $page }}</a>
            @endif
        @endforeach

        {{-- The last page stays reachable in one click on a long list. --}}
        @if ($items->currentPage() + 2 < $items->lastPage())
            <span class="px-1 text-sm text-ink-400">…</span>
            <a href="{{ $items->url($items->lastPage()) }}" class="inline-flex items-center rounded-lg border border-ink-200 px-3 py-2 text-sm text-ink-700 transition hover:bg-ink-50">{{ $items->lastPage() }}</a>
        @endif

        {{-- Next --}}
        @if ($items->hasMorePages())
            <a href="{{ $items->nextPageUrl() }}" rel="next" class="inline-flex items-center rounded-lg border border-ink-200 px-3 py-2 text-sm text-ink-700 transition hover:bg-ink-50" data-testid="front-pagination-next">&rsaquo;</a>
        @else
            <span class="inline-flex items-center rounded-lg border border-ink-100 px-3 py-2 text-sm text-ink-300" aria-disabled="true">&rsaquo;</span>
        @endif
    </nav>
@endif
