@php
/*
$layout_page = front_page_detail
**Variables:**
- $page: no paginate
*/
@endphp

{{--
    Static page detail (F02, không có demo tương ứng trong ecommerce-template
    — tự thiết kế đơn giản theo Logical Design: 1 khối nội dung + breadcrumb,
    không có logic nghiệp vụ riêng). Breadcrumb đã render sẵn ở block_top
    của layout.blade.php (từ $breadcrumbs do HomeController::_pageDetail()
    truyền vào) nên ở đây chỉ cần nội dung trang.

    @aidlc-unit frontend-template-dev
    @aidlc-story US-TPL-009
    @aidlc-adr ADR-014
--}}
@extends($GP247TemplatePath.'.layout')

@section('block_main')
<main class="container-x py-8">
    <article class="max-w-none text-ink-700 leading-relaxed">
        {!! gp247_html_render($page->content ?? '') !!}
    </article>
</main>
@endsection

@push('styles')
{{-- Your css style --}}
@endpush

@push('scripts')
{{-- //script here --}}
@endpush
