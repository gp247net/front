{{--
    Footer — Tailwind markup adapted from ecommerce-template/partials/footer.html,
    but content columns are wired to Default's real data sources
    (gp247_store_info/gp247_config/<x-gp247-front::footer-links />) instead of
    the demo's mock "Company/Support/For Business" link lists, which have no
    equivalent in the old template (P1: scope = old-template parity, not demo
    parity). Subscribe form removed (dropped in favour of
    blocks/email_subscribe.blade.php, a full-width card an admin can place
    wherever needed — same route/fields, no duplicate widget).

    .gp247-footer-links__link overridden to white here only: its shared color
    (common/gp247-components-css.blade.php's --gp247-color-link, #333333) is
    also used by breadcrumb/language-switcher/currency-switcher on light
    backgrounds elsewhere, so it can't just change globally — this footer's
    dark bg-ink-900 needs its own scoped override.

    @aidlc-unit frontend-template-dev
    @aidlc-story US-TPL-009
    @aidlc-adr ADR-014
--}}
<style>
    footer[role="contentinfo"] .gp247-footer-links__link { color: #fff; }
    footer[role="contentinfo"] .gp247-footer-links__link:hover { color: #fff; opacity: 0.75; }
</style>
<footer role="contentinfo" class="bg-ink-900 text-ink-300 mt-16 pb-20 md:pb-0">
    <div class="container-x py-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">

        <div class="col-span-1 sm:col-span-2 lg:col-span-1">
            <a href="{{ gp247_route_front('front.home') }}" class="flex items-center gap-2 mb-3">
                <img src="{{ gp247_file(gp247_store_info(key: 'logo', default: null)) }}" alt="{{ gp247_store_info(key: 'name', default: null) }}" class="h-9 w-auto" />
                <span class="text-xl font-extrabold text-white">{{ gp247_store_info(key: 'name', default: null) }}</span>
            </a>
            <p class="text-sm max-w-xs">{!! gp247_store_info(key: 'time_active', default: null) !!}</p>
            {{-- WHY: icons are inline SVG (same convention as layout/block_menu.blade.php)
                 because this template loads neither MDI nor FontAwesome — the previous
                 <i class="mdi mdi-facebook"> etc. rendered as empty grey circles. --}}
            <div class="flex items-center gap-3 mt-4">
                @if (gp247_config('facebook_url'))
                    <a target="_blank" rel="noopener" href="{{ gp247_config('facebook_url') }}" aria-label="Facebook" class="w-8 h-8 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white/20 transition">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    </a>
                @endif
                @if (gp247_config('twitter_url'))
                    <a target="_blank" rel="noopener" href="{{ gp247_config('twitter_url') }}" aria-label="X" class="w-8 h-8 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white/20 transition">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.9 1.15h3.68l-8.04 9.19L24 22.85h-7.4l-5.8-7.58-6.64 7.58H.47l8.6-9.83L0 1.15h7.59l5.25 6.93 6.06-6.93Zm-1.29 19.5h2.04L6.49 3.24H4.3l13.31 17.4Z"/></svg>
                    </a>
                @endif
                @if (gp247_config('instagram_url'))
                    <a target="_blank" rel="noopener" href="{{ gp247_config('instagram_url') }}" aria-label="Instagram" class="w-8 h-8 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white/20 transition">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/></svg>
                    </a>
                @endif
                @if (gp247_config('youtube_url'))
                    <a target="_blank" rel="noopener" href="{{ gp247_config('youtube_url') }}" aria-label="YouTube" class="w-8 h-8 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white/20 transition">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19.3c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2.02A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><path d="M9.75 15.02V8.48L15.5 11.75z"/></svg>
                    </a>
                @endif
            </div>
        </div>

        <div>
            <h3 class="text-white font-semibold mb-3">{{ gp247_language_render('about.page_title') }}</h3>
            <ul class="space-y-2 text-sm">
                @if (gp247_store_info(key: 'address', default: null))
                    <li>{{ gp247_language_render('store.address') }}: {{ gp247_store_info(key: 'address', default: null) }}</li>
                @endif
                @if (gp247_store_info(key: 'long_phone', default: null))
                    <li>{{ gp247_language_render('store.hotline') }}: {{ gp247_store_info(key: 'long_phone', default: null) }}</li>
                @endif
                @if (gp247_store_info(key: 'email', default: null))
                    <li>{{ gp247_language_render('store.email') }}: {{ gp247_store_info(key: 'email', default: null) }}</li>
                @endif
            </ul>
        </div>

        <div>
            <h3 class="text-white font-semibold mb-3">{{ gp247_language_render('front.link_useful') }}</h3>
            <x-gp247-front::footer-links />
        </div>
    </div>

    <div class="divider border-white/10"></div>

    <div class="container-x py-6 flex flex-col md:flex-row items-center justify-between gap-4 text-xs">
        {{-- WHY: 'hidden_copyright_footer' previously only gated the "Power by"
             attribution below, never this line — so the "Hide website footer
             copyright information" admin toggle had no visible effect on the
             actual copyright text. Gate this line too. --}}
        @if (!gp247_config('hidden_copyright_footer'))
            <p>&copy; {{ date('Y') }} {{ gp247_store_info(key: 'name', default: null) }}. All rights reserved.</p>
        @endif
        <div class="flex items-center gap-3">
            @if (gp247_config('fanpage_url'))
                <a target="_blank" href="{{ gp247_config('fanpage_url') }}" class="hover:text-white transition">Fanpage FB</a>
            @endif
            @if (!gp247_config('hidden_copyright_footer'))
                <span>Power by <a href="{{ config('gp247.homepage') }}" class="hover:text-white transition">{{ config('gp247.name') }} {{ config('gp247.sub-version') }}</a></span>
            @endif
        </div>
    </div>
</footer>
