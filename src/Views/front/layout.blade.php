<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ gp247front_is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <x-gp247-front::meta-head :title="$title ?? null" :description="$description ?? null" :keyword="$keyword ?? null" :og_image="$og_image ?? null" :canonical="$canonical ?? null" :hreflang="$seoHreflang ?? []" />
    {{-- Per-page JSON-LD (Product/Breadcrumb) pushed from screen views --}}
    @stack('jsonld')
    <!--Module header -->
    {!! gp247_render_block('header', $layout_page ?? null) !!}
    <!--//Module header -->

    <link rel="stylesheet" href="{{ gp247_file($GP247TemplateFile.'/css/app.css') }}">

    {{-- Sample CSS for the gp247-* class contract (notice/breadcrumb/language-switcher/...) --}}
    @includeIf($GP247TemplatePath.'.common.gp247-components-css')

    @stack('styles')
    @if(class_exists(\Livewire\Livewire::class))
        @livewireStyles
    @endif
</head>
<body class="bg-white text-ink-900 antialiased">

    {{-- Block block_menu (topbar + header + mobile drawer) --}}
    @section('block_menu')
        @include($GP247TemplatePath.'.layout.block_menu')
    @show
    {{-- //Block block_menu --}}

    {{-- Block top --}}
    @section('block_top')
        <div class="container-x">
            <!--Notice -->
            <x-gp247-front::notice />
            <!--//Notice -->
        </div>

        {{-- Module top --}}
        {!! gp247_render_block('top', $layout_page ?? null) !!}
        {{-- //Module top --}}

        <!--Breadcrumb -->
        @section('breadcrumb')
        <div class="container-x">
            <x-gp247-front::breadcrumb :items="$breadcrumbs ?? []" />
        </div>
        @show
        <!--//Breadcrumb -->
    @show
    {{-- //Block top --}}

    {{-- Block main --}}
    @section('block_main')
        <main class="container-x py-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                @section('block_main_content')
                    @php
                        $left = gp247_render_block('left', $layout_page ?? null);
                    @endphp

                    @if (trim($left) !== '')
                    <!--Block left-->
                    <div class="lg:col-span-3 w-full">
                        @section('block_main_content_left')
                        {!! $left !!}
                        @show
                    </div>
                    <!--//Block left-->
                    @endif

                    <!--Block center-->
                    <div class="{{ trim($left) !== '' ? 'lg:col-span-9' : 'lg:col-span-12' }} w-full">
                    @section('block_main_content_center')
                        {!! gp247_render_block('center', $layout_page ?? null) !!}
                    @show
                    </div>
                    <!--//Block center-->

                    <!--Block right -->
                    @section('block_main_content_right')
                        {!! gp247_render_block('right', $layout_page ?? null) !!}
                    @show
                    <!--//Block right -->

                @show
            </div>
        </main>
    @show
    {{-- //Block main --}}

    {{-- Block bottom (mobile bottom nav) --}}
    @section('block_bottom')
        {!! gp247_render_block('bottom', $layout_page ?? null) !!}
        @include($GP247TemplatePath.'.layout.block_bottom')
    @show
    {{-- //Block bottom --}}

    {{-- Block footer --}}
    @section('block_footer')
        {!! gp247_render_block('footer', $layout_page ?? null) !!}
        @include($GP247TemplatePath.'.layout.block_footer')
    @show
    {{-- //Block footer --}}

    <script src="{{ gp247_file($GP247TemplateFile.'/js/app.js') }}"></script>

    {{-- Sample JS for the gp247-* class contract (.gp247-notice__close dismiss) --}}
    @includeIf($GP247TemplatePath.'.common.gp247-components-js')

    @stack('scripts')
    @if(class_exists(\Livewire\Livewire::class))
        @livewireScripts
    @endif

</body>
</html>
