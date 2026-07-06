{{--
    Home "welcome" banner — "view"-type layout block (see
    vendor/gp247/front/src/Library/Helpers/front.php::gp247_render_block()).
    Standalone, styled restatement of the message already seeded as the
    `home` FrontPage's content (see DataFrontDefaultSeeder.php:370,
    `<h3>Welcome to CMS created by GP247 system</h3>` — rendered plain/
    unstyled by the existing "Page home" page-type block); this block is
    a dedicated, centered, styled version an admin can place independently
    of that page content.

    @aidlc-unit frontend-template-dev
    @aidlc-story US-TPL-009
    @aidlc-adr ADR-014
--}}
<section class="container-x py-6">
    <div class="card bg-gradient-to-br from-brand-50 to-white text-center px-6 py-10 sm:py-14">
        <p class="text-xl sm:text-2xl font-bold text-ink-900">
            {{ gp247_language_quickly('front.welcome_message', 'Welcome to CMS created by GP247 system') }}
        </p>
    </div>
</section>
