@php
/*
$layout_page = front_home
*/
@endphp

{{--
    Home (F01, "map trực tiếp" từ ecommerce-template/index.html).

    Mirrors Default/screen/home.blade.php's empty block_main on purpose:
    homepage content (hero banner, featured sections, ...) is entirely
    admin-configured through the layout-block system, not hardcoded here.
    layout.blade.php's block_top/block_footer/etc. already call
    gp247_render_block(<region>, 'front_home') for every screen — this file
    only needs to blank out the generic block_main so home doesn't inherit
    another screen's default center-column content. See the Phase 2 plan's
    "layout block" design decision (aidlc-docs/plans/
    code_generation_frontend-template-dev_gp247front-phase2_plan.md) for why
    a populated blocks/banner_image.blade.php was built instead of a
    hardcoded hero: it lets an admin assign a real Tailwind hero slider to
    the `top` region for `front_home` without any screen-level code.

    @aidlc-unit frontend-template-dev
    @aidlc-story US-TPL-009
    @aidlc-adr ADR-014
--}}
@extends($GP247TemplatePath.'.layout')

@section('block_main')
@endsection

@push('styles')
{{-- Your css style --}}
@endpush

@push('scripts')
{{-- //script here --}}
@endpush
