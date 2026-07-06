{{--
    Single search-result card — Tailwind port of Default/common/item_single.blade.php.
    Same field contract ($item['thumb']/['url']/['title']), reused by
    screen/front_search.blade.php (F03).

    @aidlc-unit frontend-template-dev
    @aidlc-story US-TPL-009
    @aidlc-adr ADR-014
--}}
@php
    $thumb = $item['thumb'] ?? '';
    $url = $item['url'] ?? '';
    $title = $item['title'] ?? '';
@endphp
<article class="product-card group">
    <a href="{{ $url }}" class="block relative aspect-square overflow-hidden bg-ink-50">
        <img src="{{ gp247_file($thumb) }}" alt="{{ $title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
    </a>
    <div class="p-3">
        <a href="{{ $url }}" class="text-sm font-medium text-ink-800 clamp-2 hover:text-brand-600">{{ $title }}</a>
    </div>
</article>
