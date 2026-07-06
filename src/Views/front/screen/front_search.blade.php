@php
/*
$layout_page = front_search
**Variables:**
- $itemsList: paginate
Use paginate: $itemsList->appends(request()->except(['page','_token']))->links()
*/
@endphp

{{--
    Front search (F03, map trực tiếp từ ecommerce-template/search.html —
    dùng chung layout kết quả với S03). Overrides block_main_content_center
    thay vì block_main để giữ nguyên grid layout mặc định của
    layout.blade.php (khớp với cách Default override cùng section).

    @aidlc-unit frontend-template-dev
    @aidlc-story US-TPL-009
    @aidlc-adr ADR-014
--}}
@extends($GP247TemplatePath.'.layout')

@section('block_main_content_center')
<div class="lg:col-span-12 w-full">
    <h1 class="section-title mb-4">{{ $title }}</h1>

    @if ($itemsList->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($itemsList as $item)
                @php
                    $item['thumb'] = $item->getThumb();
                    $item['url'] = $item->getUrl();
                    $item['title'] = $item->name;
                @endphp
                @include($GP247TemplatePath.'.common.item_single', ['item' => $item])
            @endforeach
        </div>

        @include($GP247TemplatePath.'.common.pagination', ['items' => $itemsList])
    @else
        <div class="text-center py-20">
            <p class="text-lg font-semibold text-ink-700">{!! gp247_language_render('front.no_item') !!}</p>
        </div>
    @endif
</div>
@endsection

@push('styles')
{{-- Your css style --}}
@endpush

@push('scripts')
{{-- //script here --}}
@endpush
