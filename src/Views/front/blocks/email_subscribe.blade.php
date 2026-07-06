{{--
    Newsletter signup card — "view"-type layout block (see
    vendor/gp247/front/src/Library/Helpers/front.php::gp247_render_block()).
    Tailwind port of ecommerce-template/index.html's "NEWSLETTER" section
    (card p-8 sm:p-12 text-center max-w-2xl mx-auto). An admin assigns this
    block (type=view, text=email_subscribe) to a region (e.g. `bottom`) for
    any page from Admin > Layout Blocks.

    Same subscribe contract as layout/block_footer.blade.php's mini form —
    same route (front.subscribe → RootFrontController::emailSubscribe()),
    same field name (subscribe_email), and the same already-seeded i18n keys
    (subscribe.title/subscribe_des/subscribe_email/action) — no hardcoded
    copy, no new language keys, no new controller/model.

    @aidlc-unit frontend-template-dev
    @aidlc-story US-TPL-009
    @aidlc-adr ADR-014
--}}
<section class="container-x py-12">
    <div class="card p-8 sm:p-12 text-center max-w-2xl mx-auto">
        <h2 class="section-title">{{ gp247_language_render('subscribe.title') }}</h2>
        <p class="text-sm text-ink-500 mt-2">{{ gp247_language_render('subscribe.subscribe_des') }}</p>
        <form method="post" action="{{ gp247_route_front('front.subscribe') }}" class="flex gap-3 mt-5">
            @csrf
            <input type="email" name="subscribe_email" required placeholder="{{ gp247_language_render('subscribe.subscribe_email') }}" class="input">
            <button type="submit" class="btn-primary shrink-0">{{ gp247_language_render('subscribe.action') }}</button>
        </form>
    </div>
</section>
