{{--
    Mobile bottom nav bar — adapted from
    ecommerce-template/partials/mobile-nav.html. The demo's
    `location.pathname.endsWith(...)` active-state checks are replaced with
    `request()->routeIs(...)` (Blade/Laravel has no client-side router here).

    @aidlc-unit frontend-template-dev
    @aidlc-story US-TPL-009
    @aidlc-adr ADR-014
--}}
<nav class="md:hidden fixed bottom-0 inset-x-0 z-30 bg-white border-t border-ink-100 safe-bottom" aria-label="Bottom navigation">
    <div class="grid grid-cols-4 h-16">
        <a href="{{ gp247_route_front('front.home') }}" class="flex flex-col items-center justify-center gap-0.5 {{ request()->routeIs('front.home') ? 'text-brand-600' : 'text-ink-500' }}">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/></svg>
            <span class="text-[10px] font-medium">{{ gp247_language_render('front.home') }}</span>
        </a>

        @if (function_exists('gp247_cart'))
            @if (gp247_config('link_cart', null, 1))
                <a href="{{ gp247_route_front('cart') }}" class="flex flex-col items-center justify-center gap-0.5 relative text-ink-500">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.5 3h2l2.7 12.4a2 2 0 002 1.6h8.6a2 2 0 002-1.6L21 8H6"/></svg>
                    <span class="text-[10px] font-medium">{{ gp247_language_render('front.cart') }}</span>
                    <span class="gp247-cart absolute top-0 end-5 min-w-[16px] h-[16px] px-1 rounded-full bg-brand-600 text-white text-[9px] font-bold flex items-center justify-center">{{ gp247_cart()->instance('default')->count() }}</span>
                </a>
            @endif

            @if (gp247_config('link_account', null, 1))
                <a href="{{ gp247_route_front('cart.wishlist') }}" class="flex flex-col items-center justify-center gap-0.5 relative text-ink-500">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-4.35-9.5-8.5C.5 8.5 3 5 6.5 5c1.9 0 3.4 1 5.5 3 2.1-2 3.6-3 5.5-3C21 5 23.5 8.5 21.5 12.5 19 16.65 12 21 12 21z"/></svg>
                    <span class="gp247-wishlist absolute top-0 end-5 min-w-[16px] h-[16px] px-1 rounded-full bg-brand-600 text-white text-[9px] font-bold flex items-center justify-center">{{ gp247_cart()->instance('wishlist')->count() }}</span>
                    <span class="text-[10px] font-medium">{{ gp247_language_render('front.wishlist') }}</span>
                </a>

                <a href="{{ (function_exists('customer') && customer()->user()) ? gp247_route_front('customer.index') : gp247_route_front('customer.login') }}" class="flex flex-col items-center justify-center gap-0.5 {{ request()->routeIs('customer.index') ? 'text-brand-600' : 'text-ink-500' }}">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
                    <span class="text-[10px] font-medium">{{ gp247_language_render('customer.my_profile') }}</span>
                </a>
            @endif
        @endif
    </div>
</nav>
