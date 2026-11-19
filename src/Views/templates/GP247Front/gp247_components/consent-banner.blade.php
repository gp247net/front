{{--
    Cookie consent banner (Unit tracking-consent, Bolt TC-2) — default view of
    GP247\Front\TemplateComponents\ConsentBanner. Printed inside an inert <template>; the
    consent loader copies it into <body> when needed.

    Override per template: app/GP247/Templates/<Template>/gp247_components/consent-banner.blade.php
    Contract with the loader (keep these when restyling):
      - [data-gp247-consent-banner]            the bar; [data-gp247-consent-panel] the customise panel
      - [data-gp247-consent-accept] / [data-gp247-consent-reject] / [data-gp247-consent-customize]
        three buttons of equal size and style (EU regulators: refusing must be as easy as accepting)
      - input[data-gp247-consent-category="analytics|marketing"], [data-gp247-consent-save], [data-gp247-consent-close]
    Styles are inline and scoped to .gp247-consent so no Tailwind class of the template is needed.
    Colours: override the --gp247-consent-* variables.

    Variables: $message (string, plain text), $policyUrl (string, '' = no link)

    @aidlc-unit tracking-consent
    @aidlc-story US-tracking-consent-banner
--}}
<style>
.gp247-consent{--gp247-consent-bg:#fff;--gp247-consent-fg:#1f2937;--gp247-consent-muted:#4b5563;--gp247-consent-border:#d1d5db;--gp247-consent-btn-bg:#1f2937;--gp247-consent-btn-fg:#fff;--gp247-consent-focus:#2563eb;font-family:inherit;font-size:14px;line-height:1.5;color:var(--gp247-consent-fg)}
.gp247-consent *{box-sizing:border-box}
.gp247-consent__bar,.gp247-consent__panel{position:fixed;inset-inline:0;inset-block-end:0;z-index:2147483000;background:var(--gp247-consent-bg);border-block-start:1px solid var(--gp247-consent-border);box-shadow:0 -4px 16px rgba(0,0,0,.12);padding:16px}
.gp247-consent__panel{max-block-size:90vh;overflow:auto}
.gp247-consent__inner{max-inline-size:1100px;margin-inline:auto;display:flex;flex-wrap:wrap;gap:12px 24px;align-items:center}
.gp247-consent__text{flex:1 1 320px;margin:0;color:var(--gp247-consent-muted)}
.gp247-consent__title{display:block;margin:0 0 4px;font-size:15px;font-weight:600;color:var(--gp247-consent-fg)}
.gp247-consent__text a{color:inherit;text-decoration:underline}
.gp247-consent__actions{display:flex;flex-wrap:wrap;gap:8px;flex:1 1 360px}
.gp247-consent__btn{flex:1 1 0;min-inline-size:110px;min-block-size:44px;padding:10px 14px;border:1px solid var(--gp247-consent-btn-bg);border-radius:6px;background:var(--gp247-consent-btn-bg);color:var(--gp247-consent-btn-fg);font:inherit;font-weight:600;cursor:pointer}
.gp247-consent__btn:focus-visible,.gp247-consent__toggle input:focus-visible{outline:3px solid var(--gp247-consent-focus);outline-offset:2px}
.gp247-consent__list{list-style:none;margin:12px 0;padding:0;inline-size:100%}
.gp247-consent__item{display:flex;gap:12px;align-items:flex-start;padding:10px 0;border-block-end:1px solid var(--gp247-consent-border)}
.gp247-consent__toggle{display:flex;gap:10px;align-items:flex-start;cursor:pointer}
.gp247-consent__toggle input{inline-size:20px;block-size:20px;margin:2px 0 0;flex:none}
.gp247-consent__item small{display:block;color:var(--gp247-consent-muted)}
.gp247-consent[hidden],.gp247-consent [hidden]{display:none!important}
</style>
<div class="gp247-consent" data-gp247-consent-root>
    <div class="gp247-consent__bar" role="dialog" aria-modal="false" aria-labelledby="gp247-consent-title" aria-describedby="gp247-consent-message" data-gp247-consent-banner data-testid="tracking-consent-banner">
        <div class="gp247-consent__inner">
            <p class="gp247-consent__text">
                <strong class="gp247-consent__title" id="gp247-consent-title">{{ gp247_language_render('front.consent.title') }}</strong>
                <span id="gp247-consent-message">{{ $message }}</span>
                @if ($policyUrl !== '')
                    <a href="{{ $policyUrl }}" data-testid="tracking-consent-policy">{{ gp247_language_render('front.consent.policy') }}</a>
                @endif
            </p>
            <div class="gp247-consent__actions">
                <button type="button" class="gp247-consent__btn" data-gp247-consent-accept data-testid="tracking-consent-accept">{{ gp247_language_render('front.consent.accept_all') }}</button>
                <button type="button" class="gp247-consent__btn" data-gp247-consent-reject data-testid="tracking-consent-reject">{{ gp247_language_render('front.consent.reject') }}</button>
                <button type="button" class="gp247-consent__btn" data-gp247-consent-customize data-testid="tracking-consent-customize">{{ gp247_language_render('front.consent.customize') }}</button>
            </div>
        </div>
    </div>

    <div class="gp247-consent__panel" role="dialog" aria-modal="false" aria-labelledby="gp247-consent-panel-title" data-gp247-consent-panel data-testid="tracking-consent-panel" hidden>
        <div class="gp247-consent__inner">
            <p class="gp247-consent__text">
                <strong class="gp247-consent__title" id="gp247-consent-panel-title">{{ gp247_language_render('front.consent.panel_title') }}</strong>
                <span>{{ $message }}</span>
                @if ($policyUrl !== '')
                    <a href="{{ $policyUrl }}">{{ gp247_language_render('front.consent.policy') }}</a>
                @endif
            </p>
            <ul class="gp247-consent__list">
                <li class="gp247-consent__item">
                    <label class="gp247-consent__toggle">
                        <input type="checkbox" checked disabled>
                        <span>{{ gp247_language_render('front.consent.necessary') }}<small>{{ gp247_language_render('front.consent.necessary_help') }}</small></span>
                    </label>
                </li>
                <li class="gp247-consent__item">
                    <label class="gp247-consent__toggle">
                        <input type="checkbox" data-gp247-consent-category="analytics" data-testid="tracking-consent-analytics">
                        <span>{{ gp247_language_render('front.consent.analytics') }}<small>{{ gp247_language_render('front.consent.analytics_help') }}</small></span>
                    </label>
                </li>
                <li class="gp247-consent__item">
                    <label class="gp247-consent__toggle">
                        <input type="checkbox" data-gp247-consent-category="marketing" data-testid="tracking-consent-marketing">
                        <span>{{ gp247_language_render('front.consent.marketing') }}<small>{{ gp247_language_render('front.consent.marketing_help') }}</small></span>
                    </label>
                </li>
            </ul>
            <div class="gp247-consent__actions">
                <button type="button" class="gp247-consent__btn" data-gp247-consent-save data-testid="tracking-consent-save">{{ gp247_language_render('front.consent.save') }}</button>
                <button type="button" class="gp247-consent__btn" data-gp247-consent-accept>{{ gp247_language_render('front.consent.accept_all') }}</button>
                <button type="button" class="gp247-consent__btn" data-gp247-consent-reject>{{ gp247_language_render('front.consent.reject') }}</button>
                <button type="button" class="gp247-consent__btn" data-gp247-consent-close data-testid="tracking-consent-close">{{ gp247_language_render('front.consent.close') }}</button>
            </div>
        </div>
    </div>
</div>
