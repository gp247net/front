{{--
    Home hero banner slider — "view"-type layout block (see
    vendor/gp247/front/src/Library/Helpers/front.php::gp247_render_block()).
    An admin assigns this block (type=view, text=banner_image) to a region
    (typically `top`) for the `front_home` page from Admin > Layout Blocks.

    Data source is identical to Default/blocks/banner_image.blade.php
    ($modelBanner is shared globally via FrontServiceProvider::boot(), see
    view()->share('modelBanner', ...)). Two slide kinds, both filling the
    whole slide block edge-to-edge (no side-by-side split like the demo's
    grid slide — simplified per explicit product decision):
    - No `html`: image only, wrapped in the click-tracking link only when
      `url` is set (nothing to link to otherwise).
    - Has `html`: rendered HTML only, plus a "Click link" button when
      `url` is set (the HTML itself may or may not contain its own links).
    Drag-to-swipe (mouse + touch) is ported from ecommerce-template/
    index.html's `heroDrag*` methods via gp247frontBannerSlider (app.js).

    @aidlc-unit frontend-template-dev
    @aidlc-story US-TPL-009
    @aidlc-adr ADR-014
--}}
@php
    $banners = $modelBanner->start()->setType('banner')->getData();
@endphp
@if ($banners->count())
<section class="relative overflow-hidden">
    <div class="container-x py-8 sm:py-14">
        <div
            x-data="gp247frontBannerSlider({{ $banners->count() }}, {{ gp247front_is_rtl() ? 'true' : 'false' }})"
            class="relative rounded-2xl overflow-hidden bg-gradient-to-br from-ink-600 to-ink-800 text-white select-none touch-pan-y"
            :class="dragging ? 'cursor-grabbing' : 'cursor-grab'"
            @mousedown="dragStart($event)" @mousemove="dragMove($event)" @mouseup="dragEnd()" @mouseleave="dragEnd()"
            @touchstart="dragStart($event)" @touchmove="dragMove($event)" @touchend="dragEnd()"
        >
            <div class="flex" :style="trackStyle">
                @foreach ($banners as $banner)
                    <div class="w-full shrink-0 relative aspect-[21/9] sm:aspect-[3/1]">
                        @if ($banner->html)
                            {{-- HTML slide: HTML content fills the whole block --}}
                            <div class="absolute inset-0 flex items-center p-8 sm:p-14">
                                {!! gp247_html_render($banner->html) !!}
                            </div>
                            @if ($banner->url)
                                <a href="{{ gp247_route_front('front.banner.click', ['id' => $banner->id]) }}" target="{{ $banner->target }}" @click="linkClick($event)" class="absolute bottom-6 sm:bottom-10 start-8 sm:start-14 btn-lg bg-white text-brand-700 hover:bg-brand-50 rounded-lg font-semibold">
                                    {{ gp247_language_quickly('front.banner.click_link', 'Click link') }}
                                </a>
                            @endif
                        @else
                            {{-- Image slide: image fills the whole block --}}
                            @if ($banner->url)
                                <a href="{{ gp247_route_front('front.banner.click', ['id' => $banner->id]) }}" target="{{ $banner->target }}" @click="linkClick($event)" class="block w-full h-full">
                                    <img src="{{ gp247_file($banner->image) }}" alt="{{ $banner->name }}" class="w-full h-full object-cover" draggable="false" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                                </a>
                            @else
                                <img src="{{ gp247_file($banner->image) }}" alt="{{ $banner->name }}" class="w-full h-full object-cover" draggable="false" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>

            @if ($banners->count() > 1)
                <div class="absolute bottom-4 start-1/2 -translate-x-1/2 flex gap-2" role="tablist" aria-label="{{ gp247_language_quickly('front.banner', 'Banner') }}">
                    <template x-for="i in count" :key="i">
                        <button type="button" @click="index = i - 1" class="w-2.5 h-2.5 rounded-full transition" :class="index === i - 1 ? 'bg-white' : 'bg-white/40'" :aria-selected="index === i - 1" role="tab" :aria-label="'Slide ' + i"></button>
                    </template>
                </div>
            @endif
        </div>
    </div>
</section>
@endif
