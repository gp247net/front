{{--
    Package default view (ADR-013, amended 2026-07-03 — modification `20260703T023638`;
    collection/children support added 2026-07-09 — modification `20260709T231050`,
    reworked same day to an inline "label: child, child" cluster after the first
    stacked-block attempt read poorly in this list's horizontal flex-wrap flow).
    Framework-neutral markup using a fixed `gp247-*` class contract — see
    language-switcher.blade.php in this directory for the full rationale.
    `Default`'s own published copy keeps its original contacts-creative/rd-nav-item
    markup and is unaffected by this change.
--}}
<ul class="gp247-footer-links">
    @foreach ($links as $link)
    @if ($link['type'] === 'collection')
    <li class="gp247-footer-links__item gp247-footer-links__item--group">
        <span class="gp247-footer-links__label">{{ $link['name'] }}:</span>
        @foreach ($link['children'] as $child)
        <a class="gp247-footer-links__link gp247-footer-links__link--child" {{ $child['target'] === '_blank' ? 'target=_blank' : '' }} href="{{ $child['url'] }}">{{ $child['name'] }}</a>{{ !$loop->last ? ',' : '' }}
        @endforeach
    </li>
    @else
    <li class="gp247-footer-links__item">
        <a class="gp247-footer-links__link" {{ $link['target'] === '_blank' ? 'target=_blank' : '' }} href="{{ $link['url'] }}">{{ $link['name'] }}</a>
    </li>
    @endif
    @endforeach
</ul>
