{{--
    Item not found — nền tảng GP247 (RootFrontController::itemNotFound()),
    không thuộc 25 màn ecommerce-template nhưng Default đã có sẵn nên
    GP247Front phải có tương đương (nguyên tắc "cái nào có trên template cũ,
    phải có trên template mới"). Cùng cấu trúc với screen/404.blade.php,
    chỉ khác message.

    @aidlc-unit frontend-template-dev
    @aidlc-story US-TPL-009
    @aidlc-adr ADR-014
--}}
@extends($GP247TemplatePath.'.layout')

@section('block_main')
<div class="min-h-[60vh] flex items-center justify-center">
    <div class="text-center">
        <h2 class="text-2xl font-semibold text-ink-600">{{ gp247_language_render('front.notfound') }}</h2>
        <p class="text-ink-500 mt-4 mb-8">{{ $msg ?? '' }}</p>
        <a href="{{ gp247_route_front('front.home') }}" class="btn-primary">
            {{ gp247_language_render('front.backhome') }}
        </a>
    </div>
</div>
@endsection
