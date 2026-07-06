{{--
    404 (F04, không có demo tương ứng — tự thiết kế đơn giản). Cùng cấu trúc
    với Default/screen/404.blade.php (đã là Tailwind sẵn), đổi màu theo bộ
    token brand/ink của GP247Front.

    @aidlc-unit frontend-template-dev
    @aidlc-story US-TPL-009
    @aidlc-adr ADR-014
--}}
@extends($GP247TemplatePath.'.layout')

@section('block_main')
<div class="min-h-[60vh] flex items-center justify-center">
    <div class="text-center">
        <h1 class="text-9xl font-bold text-ink-800">404</h1>
        <h2 class="text-2xl font-semibold text-ink-600 mt-4">{{ gp247_language_render('front.404') }}</h2>
        <p class="text-ink-500 mt-4 mb-8">{{ $msg ?? '' }}</p>
        <a href="{{ gp247_route_front('front.home') }}" class="btn-primary">
            {{ gp247_language_render('front.backhome') }}
        </a>
    </div>
</div>
@endsection
