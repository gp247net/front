{{--
    Package default view (ADR-013, amended 2026-07-03 — modification `20260703T023638`).
    Framework-neutral markup using a fixed `gp247-*` class contract — a
    template that doesn't override this file styles it via CSS on these
    classes (or replaces the file entirely). `Default`'s own published copy
    at app/GP247/Templates/Default/gp247_components/language-switcher.blade.php
    keeps its original rd-nav-item/rd-menu markup (tied to its bundled
    rd-navbar JS/CSS) and is unaffected by this change.
--}}
{{--
    GP247Front override: click-toggle via Alpine (x-data on the <li> itself),
    not the package default's :hover/:focus-within CSS. This topbar sits the
    currency and language switcher LIs only ~16px apart while their dropdown
    panels are 120-140px wide — right-aligned to each switcher's own (narrow)
    LI, so whenever both happened to be open at once (proximity hover, tab
    focus) the two panels overlapped by up to ~95px. Click-toggle removes the
    accidental-simultaneous-open case entirely: opening one dispatches a click
    outside the other, which closes it via @click.outside. Also fixes the
    switcher being inert on touch devices (no :hover). Alpine is already
    bundled via @livewireScripts on every GP247Front page.
--}}
@if (count($languages))
@php $current = collect($languages)->firstWhere('active', true); @endphp
<li class="gp247-language-switcher" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <a class="gp247-language-switcher__toggle" href="#" @click.prevent="open = !open" :aria-expanded="open.toString()">
        <img src="{{ gp247_file($current['icon'] ?? '') }}" style="height: 25px;"> <i class="fas fa-caret-down"></i>
    </a>
    <ul class="gp247-language-switcher__menu" x-show="open" x-cloak @click="open = false">
        @foreach ($languages as $language)
        <li class="gp247-language-switcher__item{{ $language['active'] ? ' gp247-language-switcher__item--active' : '' }}">
            <a class="gp247-language-switcher__link" href="{{ $language['url'] }}">
                <img src="{{ gp247_file($language['icon']) }}" style="height: 25px;"> {{ $language['name'] }}
            </a>
        </li>
        @endforeach
    </ul>
</li>
@endif
