{{--
    Package default view (ADR-013, amended 2026-07-03 — modification `20260703T023638`).
    Framework-neutral markup using a fixed `gp247-*` class contract — see
    language-switcher.blade.php in this directory for the full rationale.
    The Bootstrap-only `<section>`/`.container` wrapper is dropped here: page
    chrome/spacing is the template's job, not this component's. `Default`'s
    own published copy keeps its original breadcrumbs-custom markup (including
    the wrapper) and is unaffected by this change.
--}}
@if (count($items))
<nav class="gp247-breadcrumb">
    <ul class="gp247-breadcrumb__path">
        <li class="gp247-breadcrumb__item"><a class="gp247-breadcrumb__link" href="{{ gp247_route_front('front.home') }}">{{ gp247_language_render('front.home') }}</a></li>
        @foreach ($items as $item)
            @if ($item['active'])
            <li class="gp247-breadcrumb__item gp247-breadcrumb__item--active">{{ $item['title'] }}</li>
            @else
            <li class="gp247-breadcrumb__item"><a class="gp247-breadcrumb__link" href="{{ $item['url'] }}">{{ $item['title'] }}</a></li>
            @endif
        @endforeach
    </ul>
</nav>
@endif
