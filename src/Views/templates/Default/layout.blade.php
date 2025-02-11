@if (gp247_store_info('active') == '1'  || (gp247_store_info('active') == '0' && auth()->guard('admin')->user()))
    {{-- Admin logged can view the website content under maintenance --}}
    @if (gp247_store_info('active') == '0' && auth()->guard('admin')->user())
        @includeIf($GP247TemplatePath . '.maintenance_note')
    @endif
    @include($GP247TemplatePath . '.main')
@else
    @include($GP247TemplatePath . '.maintenance')
@endif