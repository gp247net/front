@php
/*
$layout_page = front_contact
**Variables:**
- $page: CMS page with alias "contact" (or null) — shown above the form
- $viewCaptcha: rendered by gp247_captcha_processview('contact', ...)
- $honeypot: name of the hidden anti-bot field
*/
@endphp

{{--
    Contact form (front-contact). Also the fallback for templates that do not ship
    their own screen/front_contact (resolved through gp247_front_view), so it extends
    the ACTIVE template's layout via $GP247TemplatePath. Classes reuse the compiled
    GP247Front bundle (no new Tailwind combinations).

    @aidlc-unit front-contact
    @aidlc-story US-front-contact-toggle, US-front-contact-submit, US-front-contact-anti-spam
    @aidlc-adr front-contact_contact-form-in-front
--}}
@extends($GP247TemplatePath.'.layout')

@section('block_main')
<main class="container-x py-8">
    @if (!empty($page) && !empty($page->content))
        <article class="max-w-none text-ink-700 leading-relaxed mb-8" data-testid="front-contact-page-content">
            {!! gp247_html_render($page->content) !!}
        </article>
    @endif

    <div class="card p-8 max-w-2xl mx-auto">
        <h1 class="section-title mb-6">{{ gp247_language_render('contact.page_title') }}</h1>

        @if (session('contact_sent'))
            <div class="rounded-lg bg-green-50 text-green-700 text-sm px-4 py-3 mb-4" data-testid="front-contact-sent">{{ session('contact_sent') }}</div>
        @endif

        <form method="POST" action="{{ gp247_route_front('front.contact.submit') }}" class="space-y-4" data-testid="front-contact-form" id="gp247-form-process">
            @csrf

            {{-- Anti-bot trap: hidden from people and screen readers, filled only by bots. --}}
            <div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden" aria-hidden="true">
                <label for="contact-{{ $honeypot }}">{{ $honeypot }}</label>
                <input type="text" id="contact-{{ $honeypot }}" name="{{ $honeypot }}" value="" tabindex="-1" autocomplete="off">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label" for="contact-name">{{ gp247_language_render('contact.name') }} *</label>
                    <input type="text" id="contact-name" name="name" maxlength="100" required value="{{ old('name') }}"
                        class="input @error('name') input-error @enderror" data-testid="front-contact-name">
                    @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="contact-email">{{ gp247_language_render('contact.email') }} *</label>
                    <input type="email" id="contact-email" name="email" maxlength="150" required value="{{ old('email') }}"
                        class="input @error('email') input-error @enderror" data-testid="front-contact-email">
                    @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="contact-phone">{{ gp247_language_render('contact.phone') }}</label>
                    <input type="tel" id="contact-phone" name="phone" maxlength="30" value="{{ old('phone') }}"
                        class="input @error('phone') input-error @enderror" data-testid="front-contact-phone">
                    @error('phone')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="contact-title">{{ gp247_language_render('contact.subject') }} *</label>
                    <input type="text" id="contact-title" name="title" maxlength="200" required value="{{ old('title') }}"
                        class="input @error('title') input-error @enderror" data-testid="front-contact-title">
                    @error('title')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="contact-content">{{ gp247_language_render('contact.content') }} *</label>
                    <textarea id="contact-content" name="content" rows="6" maxlength="5000" required
                        class="input @error('content') input-error @enderror" data-testid="front-contact-content">{{ old('content') }}</textarea>
                    @error('content')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {!! $viewCaptcha ?? '' !!}
            @error('captcha_field')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror

            <button type="submit" class="btn-primary w-full" id="gp247-button-process" data-testid="front-contact-submit">
                {{ gp247_language_render('action.submit') }}
            </button>
        </form>
    </div>
</main>
@endsection
