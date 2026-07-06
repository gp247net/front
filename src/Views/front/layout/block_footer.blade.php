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
            <div class="flex items-center gap-3 mt-4">
                @if (gp247_config('facebook_url'))
                    <a href="{{ gp247_config('facebook_url') }}" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition"><i class="mdi mdi-facebook"></i></a>
                @endif
                @if (gp247_config('twitter_url'))
                    <a href="{{ gp247_config('twitter_url') }}" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition"><i class="mdi mdi-twitter"></i></a>
                @endif
                @if (gp247_config('instagram_url'))
                    <a href="{{ gp247_config('instagram_url') }}" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition"><i class="mdi mdi-instagram"></i></a>
                @endif
                @if (gp247_config('youtube_url'))
                    <a href="{{ gp247_config('youtube_url') }}" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition"><i class="mdi mdi-youtube-play"></i></a>
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
