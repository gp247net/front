{{--
    GP247Front override: click-toggle via Alpine — see language-switcher.blade.php
    in this directory for the full rationale (overlapping dropdown panels when
    both switchers were open via :hover at once).
--}}
@if (count($currencies))
@php $current = collect($currencies)->firstWhere('code', $activeCode); @endphp
<li class="gp247-currency-switcher" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <a class="gp247-currency-switcher__toggle" href="#" @click.prevent="open = !open" :aria-expanded="open.toString()">
        {{ $current['name'] ?? $activeCode }} <i class="fas fa-caret-down"></i>
    </a>
    <ul class="gp247-currency-switcher__menu" x-show="open" x-cloak @click="open = false">
        @foreach ($currencies as $currency)
        <li class="gp247-currency-switcher__item{{ $currency['code'] === $activeCode ? ' gp247-currency-switcher__item--active' : '' }}" {{ $currency['code'] === $activeCode ? 'disabled' : '' }}>
            <a class="gp247-currency-switcher__link" href="{{ $currency['url'] }}">{{ $currency['name'] }}</a>
        </li>
        @endforeach
    </ul>
</li>
@endif
