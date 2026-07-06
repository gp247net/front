/*
 * GP247Front interaction JS — plain ES, no bundling (same precedent as
 * vendor/gp247/core/src/AdminShell/resources/assets/js/admin.js): Alpine.js
 * itself ships inside Livewire 4's bundle (@livewireScripts in
 * layout.blade.php), so this file only registers extra Alpine.data()
 * components via the `alpine:init` hook and is loaded as a plain <script>
 * (see layout.blade.php) — no build step needed, copied as-is to
 * public/GP247/Templates/GP247Front/js/app.js.
 *
 * Scope note (Phase 1): only the pure UI-state pieces of
 * ecommerce-template/assets/js/{app,main,components/header}.js are ported
 * here (sticky header shrink, mobile menu toggle). The demo's mock
 * $store.i18n/$store.currency/$store.session/$store.cart/$store.wishlist
 * are NOT ported — GP247Front uses GP247's real language/currency shared
 * components, customer() auth guard and gp247_cart() service instead (see
 * layout/block_menu.blade.php), so no client-side store is needed for them.
 */
document.addEventListener('alpine:init', () => {
    /**
     * Sticky header shell state: scroll shrink only (mega-menu-by-category
     * deferred — Phase 1 nav uses gp247_link_collection(), see
     * layout/block_menu.blade.php; category mega-menu lands with the
     * catalog screens in a later phase).
     *
     * @aidlc-unit frontend-template-dev
     * @aidlc-story US-TPL-009
     */
    Alpine.data('gp247frontHeader', () => ({
        scrolled: false,
        mobileMenuOpen: false,
        init() {
            this.scrolled = window.scrollY > 80;
            window.addEventListener('scroll', () => { this.scrolled = window.scrollY > 80; }, { passive: true });
        },
    }));

    /**
     * Home hero banner slider (blocks/banner_image.blade.php): autoplay,
     * dot navigation, and mouse/touch drag-to-swipe — ported from
     * ecommerce-template/index.html's `heroDrag*` methods. `isRtl` is
     * passed in from the server (gp247front_is_rtl()) since RTL here
     * follows AdminLanguage.rtl, not a client-side toggle.
     *
     * @aidlc-unit frontend-template-dev
     * @aidlc-story US-TPL-009
     * @aidlc-adr ADR-014
     */
    Alpine.data('gp247frontBannerSlider', (count = 1, isRtl = false) => ({
        count,
        index: 0,
        dirSign: isRtl ? -1 : 1,
        dragging: false,
        dragStartX: 0,
        dragDeltaX: 0,
        dragWidth: 1,
        wasDragged: false,
        init() {
            if (this.count <= 1) {
                return;
            }
            setInterval(() => {
                if (!this.dragging) {
                    this.index = (this.index + 1) % this.count;
                }
            }, 5000);
        },
        /**
         * Inline transform for the slide track, following the pointer in
         * real time while dragging and snapping between slides otherwise.
         */
        get trackStyle() {
            const basePct = -(this.index * 100);
            const dragPct = this.dragWidth ? (this.dragDeltaX / this.dragWidth) * 100 : 0;
            return `transform: translateX(${this.dirSign * (basePct + dragPct)}%); transition: ${this.dragging ? 'none' : 'transform 500ms ease'}`;
        },
        /**
         * Start tracking a mouse or touch drag on the slider.
         */
        dragStart(event) {
            this.dragging = true;
            this.wasDragged = false;
            this.dragDeltaX = 0;
            this.dragStartX = event.touches ? event.touches[0].clientX : event.clientX;
            this.dragWidth = event.currentTarget.offsetWidth || 1;
        },
        /**
         * Track pointer movement during a drag, flagging it as a real drag
         * once it crosses a small threshold so slide links aren't
         * accidentally followed after a swipe.
         */
        dragMove(event) {
            if (!this.dragging) {
                return;
            }
            const clientX = event.touches ? event.touches[0].clientX : event.clientX;
            this.dragDeltaX = clientX - this.dragStartX;
            if (Math.abs(this.dragDeltaX) > 5) {
                this.wasDragged = true;
            }
        },
        /**
         * End the drag gesture, advancing to the next/previous slide when
         * the pointer moved past the snap threshold.
         */
        dragEnd() {
            if (!this.dragging) {
                return;
            }
            const threshold = this.dragWidth * 0.15;
            const signedDelta = this.dragDeltaX * this.dirSign;
            if (signedDelta < -threshold) {
                this.index = (this.index + 1) % this.count;
            } else if (signedDelta > threshold) {
                this.index = (this.index - 1 + this.count) % this.count;
            }
            this.dragging = false;
            this.dragDeltaX = 0;
        },
        /**
         * Suppress a slide's link/CTA click when it was triggered right
         * after a drag gesture, so swiping doesn't also navigate away.
         *
         * @param {MouseEvent} event - Click event on a slide link or CTA.
         */
        linkClick(event) {
            if (this.wasDragged) {
                event.preventDefault();
            }
        },
    }));

    /**
     * Flash-sale countdown (blocks/shop_flash_sale.blade.php): ticks down
     * to a server-computed target (the soonest `date_end` among the active
     * promotions, in epoch ms) so the displayed h/m/s reflect a real
     * promotion deadline rather than a decorative mock timer.
     *
     * @aidlc-unit frontend-template-dev
     * @aidlc-story US-TPL-009
     * @aidlc-adr ADR-014
     */
    Alpine.data('gp247frontCountdown', (targetMs) => ({
        targetMs,
        h: '00',
        m: '00',
        s: '00',
        timer: null,
        init() {
            this.tick();
            this.timer = setInterval(() => this.tick(), 1000);
        },
        /**
         * Recompute the displayed h/m/s from the remaining time, stopping
         * the interval once the target is reached.
         */
        tick() {
            const remainingSeconds = Math.max(0, Math.floor((this.targetMs - Date.now()) / 1000));
            const pad = (n) => String(n).padStart(2, '0');
            this.h = pad(Math.floor(remainingSeconds / 3600));
            this.m = pad(Math.floor((remainingSeconds % 3600) / 60));
            this.s = pad(remainingSeconds % 60);
            if (remainingSeconds <= 0 && this.timer) {
                clearInterval(this.timer);
            }
        },
        destroy() {
            if (this.timer) {
                clearInterval(this.timer);
            }
        },
    }));
});
