<!DOCTYPE html>
<html class="wide wow-animation" lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="{{ config('app.name')}}">
    <link rel="canonical" href="{{ request()->url() }}" />
    <meta name="description" content="{{ $description ?? gp247_store_info('description') }}">
    <meta name="keywords" content="{{ $keyword ?? gp247_store_info('keyword') }}">
    <title>{{$title ?? gp247_store_info('title')}}</title>
    <link rel="icon" href="{{ gp247_file(gp247_store_info('icon','GP247/Core/logo/icon.png')) }}" type="image/png" sizes="16x16">
    <meta property="og:image" content="{{ !empty($og_image)?gp247_file($og_image):gp247_file(gp247_store_info('og_image', 'GP247/Core/images/org.jpg')) }}" />
    <meta property="og:url" content="{{ \Request::fullUrl() }}" />
    <meta property="og:type" content="Website" />
    <meta property="og:title" content="{{ $title??gp247_store_info('title') }}" />
    <meta property="og:description" content="{{ $description??gp247_store_info('description') }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Roboto+Condensed:300,400,700%7CLato%7CKalam:300,400,700">

    <!-- css default for item gp247 -->
    @include($GP247TemplatePath.'.common.css')
    <!--//end css defaut -->

    <!--Module header -->
    @includeIf($GP247TemplatePath.'.common.render_block', ['positionBlock' => 'header'])
    <!--//Module header -->

    <link rel="stylesheet" href="{{ gp247_file($GP247TemplateFile.'/css/bootstrap.css')}}">
    <link rel="stylesheet" href="{{ gp247_file($GP247TemplateFile.'/css/fonts.css')}}">
    <link rel="stylesheet" href="{{ gp247_file($GP247TemplateFile.'/css/style.css')}}">

    <style>.ie-panel{display: none;background: #212121;padding: 10px 0;box-shadow: 3px 3px 5px 0 rgba(0,0,0,.3);clear: both;text-align:center;position: relative;z-index: 1;} html.ie-10 .ie-panel, html.lt-ie-10 .ie-panel {display: block;}</style>
    @stack('styles')
  </head>
<body>

    <div class="page">
        {{-- Block header --}}
        @section('block_header')
            @include($GP247TemplatePath.'.block_header')
        @show
        {{--// Block header --}}

        {{-- Block top --}}
        @section('block_top')
            @include($GP247TemplatePath.'.block_top')

            <!--Breadcrumb -->
            @section('breadcrumb')
                @include($GP247TemplatePath.'.common.breadcrumb')
            @show
            <!--//Breadcrumb -->

            <!--Notice -->
            @include($GP247TemplatePath.'.common.notice')
            <!--//Notice -->
        @show
        {{-- //Block top --}}

        {{-- Block main --}}
        @section('block_main')
            <section class="section section-xxl bg-default text-md-left">
                <div class="container">
                    <div class="row row-50">
                        @section('block_main_content')

                        @if (empty($hiddenBlockLeft))
                            <!--Block left-->
                            <div class="col-lg-4 col-xl-3">
                                @section('block_main_content_left')
                                    @include($GP247TemplatePath.'.block_main_content_left')
                                @show
                            </div>
                            <!--//Block left-->

                            <!--Block center-->
                            <div class="col-lg-9 col-xl-9">
                                @section('block_main_content_center')
                                    @include($GP247TemplatePath.'.block_main_content_center')
                                @show
                            </div>
                            <!--//Block center-->
                        @else
                            <!--Block center-->
                            @section('block_main_content_center')
                                @include($GP247TemplatePath.'.block_main_content_center')
                            @show
                            <!--//Block center-->
                        @endif

                        @if (empty($hiddenBlockRight))
                            <!--Block right -->
                            @section('block_main_content_right')
                                @include($GP247TemplatePath.'.block_main_content_right')
                            @show
                            <!--//Block right -->
                        @endif

                        @show
                    </div>
                </div>
            </section>
        @show
        {{-- //Block main --}}

        <!-- Render include view -->
        @include($GP247TemplatePath.'.common.include_view')
        <!--// Render include view -->


        {{-- Block bottom --}}
        @section('block_bottom')
            @include($GP247TemplatePath.'.block_bottom')
        @show
        {{-- //Block bottom --}}

        {{-- Block footer --}}
        @section('block_footer')
            @include($GP247TemplatePath.'.block_footer')
        @show
        {{-- //Block footer --}}

    </div>

    <div id="gp247-loading">
        <div class="gp247-overlay"><i class="fa fa-spinner fa-pulse fa-5x fa-fw "></i></div>
    </div>

    <script src="{{ gp247_file($GP247TemplateFile.'/js/core.min.js')}}"></script>
    <script src="{{ gp247_file($GP247TemplateFile.'/js/script.js')}}"></script>
    
    <!-- js default for item gp247 -->
    @include($GP247TemplatePath.'.common.js')
    <!--//end js defaut -->
    @stack('scripts')

</body>
</html>