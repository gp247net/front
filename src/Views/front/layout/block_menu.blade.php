{{--
    Topbar + header + mobile drawer — Tailwind markup adapted from
    ecommerce-template/partials/{topbar,header,mobile-menu-drawer}.html.

    Scope decisions (Phase 1, ADR-014):
    - Dropped the demo's mock i18n/currency Alpine stores and its RTL preview
      toggle — language/currency switching goes through the real
      <x-gp247-front::language-switcher /> / <x-gp247-front::currency-switcher />
      components, and RTL follows AdminLanguage.rtl (see layout.blade.php's
      `dir` attribute), not a client-side toggle.
    - Dropped the desktop category mega-menu (window.DATA.categories) — nav
      uses gp247_link_collection()['menu'], the same real data source Default
      uses, matching Default's actual nav loop. True category mega-menu is
      deferred to the catalog phase.
    - Dropped vendors.html / "become a seller" links (out of scope — vendor
      screens are explicitly excluded).
    - Dropped the live search-suggestions panel (demo used mock product data;
      no equivalent backend endpoint is in scope for Phase 1) — search is a
      plain GET form to front.search, same as Default.
    - Cart/wishlist counts and the live badge-sync script are ported verbatim
      from Default/layout/block_menu.blade.php (gp247_cart()->instance(...),
      the `.gp247-cart`/`.gp247-wishlist` badge classes, and the
      Livewire `cart-updated` listener) so ProductCard/CartManager keep
      working identically across both templates.

    @aidlc-unit frontend-template-dev
    @aidlc-story US-TPL-009
    @aidlc-adr ADR-014
--}}
<div x-data="gp247frontHeader">

    <!-- Topbar (desktop) -->
    <div class="hidden md:block bg-ink-700 text-ink-100 text-xs">
        <div class="container-x flex items-center justify-between h-9">
            <div class="flex items-center gap-4">
                @if (gp247_store_info(key: 'long_phone', default: null))
                    <span>{{ gp247_language_render('store.hotline') }}: {{ gp247_store_info(key: 'long_phone', default: null) }}</span>
                @endif
            </div>
            <div class="flex items-center gap-4">
                @if (function_exists('gp247_cart') && gp247_config('link_account', null, 1))
                    @if (function_exists('customer') && !customer()->user())
                        <a href="{{ gp247_route_front('customer.login') }}" class="hover:text-white transition">{{ gp247_language_render('front.login') }} / {{ gp247_language_quickly('front.register', gp247_language_render('customer.title_register')) }}</a>
                    @else
                        <a href="{{ gp247_route_front('customer.index') }}" class="hover:text-white transition">{{ gp247_language_render('customer.my_profile') }}</a>
                    @endif
                @endif
                <x-gp247-front::currency-switcher />
                <x-gp247-front::language-switcher />
            </div>
        </div>
    </div>
    <!-- //Topbar -->

    <!-- Header -->
    <header role="banner" class="sticky top-0 z-30 bg-white border-b border-ink-100 transition-shadow" :class="scrolled && 'shadow-soft'">
        <div class="container-x flex items-center gap-3 sm:gap-6 h-16 sm:h-20">

            <!-- hamburger (mobile) -->
            <button type="button" @click="mobileMenuOpen = true" class="lg:hidden btn-icon -ms-2" aria-label="{{ gp247_language_quickly('front.menu', 'Menu') }}">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            <!-- logo -->
            <a href="{{ gp247_route_front('front.home') }}" class="flex items-center gap-2 shrink-0">
                <img src="{{ gp247_file(gp247_store_info(key: 'logo', default: null)) }}" alt="{{ gp247_store_info(key: 'name', default: null) }}" class="h-9 w-auto" />
                <span class="hidden sm:block text-xl font-extrabold text-ink-900">{{ gp247_store_info(key: 'name', default: null) }}</span>
            </a>

            {{--
                All-categories mega menu — gp247/shop is optional, so this whole
                block is guarded behind class_exists(ShopCategory) (same pattern
                as blocks/shop_category_home.blade.php): a core+front-only install
                never registers ShopServiceProvider, so $modelCategory would not
                be shared and there is nothing to browse by category anyway.
                Root/top categories become the columns (ShopCategory::getCategoryRoot()
                ->getCategoryTop(), same query as shop_category_home.blade.php);
                each column's children are a fresh per-category query
                (ShopCategory::setParent($id)->getData()).
            --}}
            @if (class_exists(\GP247\Shop\Models\ShopCategory::class))
                @php
                    $megaMenuCategories = $modelCategory->start()->getCategoryRoot()->getCategoryTop()->getData();
                @endphp
                @if ($megaMenuCategories->count())
                    <div class="relative hidden lg:block shrink-0" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                        <button type="button" @click="open = !open" class="btn-outline btn-sm flex items-center gap-2" :aria-expanded="open.toString()">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                            {{ gp247_language_quickly('front.all_categories', 'Tất cả danh mục') }}
                        </button>
                        <div x-show="open" x-cloak x-transition
                             class="absolute start-0 top-full mt-2 z-40 w-[min(90vw,48rem)] rounded-xl bg-white shadow-soft border border-ink-100 p-6 grid grid-cols-3 gap-x-8 gap-y-6">
                            @foreach ($megaMenuCategories as $megaMenuCategory)
                                @php
                                    $megaMenuChildren = $modelCategory->start()->setParent($megaMenuCategory->id)->getData();
                                @endphp
                                <div>
                                    <a href="{{ $megaMenuCategory->getUrl() }}" class="flex items-center gap-2 font-semibold text-ink-900 hover:text-brand-600 mb-2">
                                        <img src="{{ gp247_file($megaMenuCategory->getThumb()) }}" alt="" class="w-6 h-6 rounded object-cover shrink-0">
                                        <span class="clamp-1">{{ $megaMenuCategory->name }}</span>
                                    </a>
                                    @if ($megaMenuChildren->count())
                                        <ul class="space-y-1.5">
                                            @foreach ($megaMenuChildren as $megaMenuChild)
                                                <li><a href="{{ $megaMenuChild->getUrl() }}" class="text-sm text-ink-600 hover:text-brand-600">{{ $megaMenuChild->name }}</a></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif

            <!-- desktop nav -->
            @if (!empty(gp247_link_collection()['menu']))
                <nav class="hidden xl:flex items-center gap-5" aria-label="Primary">
                    @foreach (gp247_link_collection()['menu'] as $url)
                        @if ($url['type'] != 'collection')
                            <a class="nav-link" {{ ($url['data']['target'] == '_blank') ? 'target=_blank' : '' }} href="{{ gp247_url_render($url['data']['url']) }}">{{ gp247_language_render($url['data']['name']) }}</a>
                        @elseif (count($url['childs']))
                            <div class="relative group">
                                <button type="button" class="nav-link flex items-center gap-1">
                                    {{ $url['data']['name'] }}
                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </button>
                                <div class="hidden group-hover:block absolute start-0 top-full pt-2 z-40">
                                    <div class="rounded-xl bg-white shadow-soft border border-ink-100 py-2 min-w-[200px]">
                                        @foreach ($url['childs'] as $item)
                                            <a class="block px-4 py-2 text-sm text-ink-700 hover:bg-ink-50" {{ ($item['data']['target'] == '_blank') ? 'target=_blank' : '' }} href="{{ gp247_url_render($item['data']['url']) }}">{{ gp247_language_render($item['data']['name']) }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </nav>
            @endif

            <!-- search (desktop inline) -->
            <form class="flex-1 min-w-0 hidden md:block relative" action="{{ gp247_route_front('front.search') }}" method="GET">
                <span class="absolute inset-y-0 start-3 flex items-center text-ink-400">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-3.5-3.5"/></svg>
                </span>
                <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="{{ gp247_language_render('search.placeholder') }}" class="input ps-9" role="searchbox" aria-label="Search" />
            </form>

            <!-- icon group -->
            <div class="flex items-center gap-1 sm:gap-2 shrink-0 ms-auto md:ms-0">
                @if (function_exists('gp247_cart'))
                    @if (gp247_config('link_account', null, 1))
                        <a href="{{ gp247_route_front('cart.wishlist') }}" class="btn-icon relative" aria-label="{{ gp247_language_render('front.wishlist') }}">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-4.35-9.5-8.5C.5 8.5 3 5 6.5 5c1.9 0 3.4 1 5.5 3 2.1-2 3.6-3 5.5-3C21 5 23.5 8.5 21.5 12.5 19 16.65 12 21 12 21z"/></svg>
                            <span class="gp247-wishlist absolute -top-1 -end-1 min-w-[18px] h-[18px] px-1 rounded-full bg-brand-600 text-white text-[10px] font-bold flex items-center justify-center">{{ gp247_cart()->instance('wishlist')->count() }}</span>
                        </a>
                    @endif

                    @if (gp247_config('link_cart', null, 1))
                        <a href="{{ gp247_route_front('cart') }}" class="btn-icon relative" title="{{ gp247_language_render('cart.page_title') }}" aria-label="{{ gp247_language_render('front.cart') }}">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.5 3h2l2.7 12.4a2 2 0 002 1.6h8.6a2 2 0 002-1.6L21 8H6"/></svg>
                            <span class="gp247-cart absolute -top-1 -end-1 min-w-[18px] h-[18px] px-1 rounded-full bg-brand-600 text-white text-[10px] font-bold flex items-center justify-center">{{ gp247_cart()->instance('default')->count() }}</span>
                        </a>
                    @endif

                    @if (gp247_config('link_account', null, 1))
                        @if (function_exists('customer') && customer()->user())
                            <a href="{{ gp247_route_front('customer.index') }}" class="btn-icon hidden sm:flex" aria-label="{{ gp247_language_render('customer.my_profile') }}">
                                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
                            </a>
                        @else
                            <a href="{{ gp247_route_front('customer.login') }}" class="btn-icon hidden sm:flex" aria-label="{{ gp247_language_render('front.login') }}">
                                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
                            </a>
                        @endif
                    @endif
                @endif
            </div>
        </div>
    </header>
    <!-- //Header -->

    <!-- Mobile menu drawer -->
    <div x-show="mobileMenuOpen" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true">
        <div x-show="mobileMenuOpen" x-transition.opacity @click="mobileMenuOpen = false" class="drawer-overlay"></div>
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full rtl:translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full rtl:translate-x-full"
             class="drawer-panel drawer-panel-start overflow-y-auto" @keydown.escape.window="mobileMenuOpen = false">
            <div class="flex items-center justify-between p-4 border-b border-ink-100">
                <span class="text-lg font-bold">{{ gp247_store_info(key: 'name', default: null) }}</span>
                <button type="button" @click="mobileMenuOpen = false" class="btn-icon" aria-label="Close menu">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>

            @if (!empty(gp247_link_collection()['menu']))
                <nav class="p-4 space-y-1" aria-label="Primary">
                    @foreach (gp247_link_collection()['menu'] as $url)
                        @if ($url['type'] != 'collection')
                            <a class="block py-2 text-sm font-medium text-ink-800" {{ ($url['data']['target'] == '_blank') ? 'target=_blank' : '' }} href="{{ gp247_url_render($url['data']['url']) }}">{{ gp247_language_render($url['data']['name']) }}</a>
                        @elseif (count($url['childs']))
                            <div class="divider">
                                <p class="py-2 text-sm font-semibold text-ink-800">{{ $url['data']['name'] }}</p>
                                <div class="ps-4 pb-2 space-y-2">
                                    @foreach ($url['childs'] as $item)
                                        <a class="block text-sm text-ink-600 py-1" {{ ($item['data']['target'] == '_blank') ? 'target=_blank' : '' }} href="{{ gp247_url_render($item['data']['url']) }}">{{ gp247_language_render($item['data']['name']) }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </nav>
            @endif

            <div class="divider"></div>
            @if (function_exists('gp247_cart') && gp247_config('link_account', null, 1))
                <nav class="p-4 space-y-3" aria-label="Account">
                    @if (function_exists('customer') && customer()->user())
                        <a href="{{ gp247_route_front('customer.index') }}" class="block text-sm font-medium text-ink-800">{{ gp247_language_render('customer.my_profile') }}</a>
                        <a href="{{ gp247_route_front('customer.logout') }}" rel="nofollow" onclick="event.preventDefault(); document.getElementById('gp247front-logout-form').submit();" class="block text-sm font-medium text-red-600">{{ gp247_language_render('front.logout') }}</a>
                        <form id="gp247front-logout-form" action="{{ gp247_route_front('customer.logout') }}" method="POST" class="hidden">@csrf</form>
                    @else
                        <a href="{{ gp247_route_front('customer.login') }}" class="block text-sm font-medium text-brand-600">{{ gp247_language_render('front.login') }}</a>
                    @endif
                    <a href="{{ gp247_route_front('cart.wishlist') }}" class="block text-sm font-medium text-ink-800">{{ gp247_language_render('front.wishlist') }}</a>
                    <a href="{{ gp247_route_front('cart.compare') }}" class="block text-sm font-medium text-ink-800">{{ gp247_language_render('front.compare') }}</a>
                </nav>
                <div class="divider"></div>
            @endif

            <div class="p-4 flex items-center gap-3">
                <x-gp247-front::currency-switcher />
                <x-gp247-front::language-switcher />
            </div>
        </div>
    </div>
    <!-- //Mobile menu drawer -->

</div>

@if (function_exists('gp247_cart'))
    {{--
        Sync cart/wishlist/compare badges from any Livewire component that
        dispatches 'cart-updated' (CartManager, ProductCard — US-LW-004,
        ADR-015) — ported verbatim from Default/layout/block_menu.blade.php.
    --}}
    <script type="text/javascript">
        document.addEventListener('livewire:init', () => {
            Livewire.on('cart-updated', ({ count, instance, message }) => {
                const badgeClass = instance === 'default' ? 'cart' : instance;
                document.querySelectorAll('.gp247-' + badgeClass).forEach((el) => { el.textContent = count; });
                if (message && typeof alertJs === 'function') alertJs('success', message);
            });
            Livewire.on('cart-error', ({ message }) => { if (typeof alertJs === 'function') alertJs('error', message); });
            Livewire.on('cart-redirect', ({ url }) => { window.location.href = url; });
        });
    </script>
@endif
