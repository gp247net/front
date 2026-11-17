{{--
    SEO "Meta & JSON-LD" screen (US-SEO-004/005, modification 20260711T154553
    — split out of the former combined seo-settings.blade.php): robots.txt
    editor, structured data (JSON-LD) master toggle, and a quick link to the
    existing "Website info" screen for default meta/OG (deliberately not
    duplicated here — see modification 20260711T114155).
    Each card carries one short plain-language hint for site owners
    (modification 20261117T214301).

    @aidlc-unit seo
    @aidlc-story US-SEO-004, US-SEO-005, US-SEO-site-verification, US-SEO-indexnow, US-SEO-title-template

    Variables:
      - $robotsMaxLength (int)
--}}
@php
    $input = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-mono transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
    // One-line hint with an info icon; classes already in the built core admin CSS.
    $hint = 'flex items-start gap-1 text-xs text-gray-500 dark:text-gray-400';
    $hintIcon = 'fas fa-info-circle mt-0.5 shrink-0 text-blue-600 dark:text-blue-400';
@endphp

<div class="space-y-6">
    <x-gp247::card :title="gp247_language_render('admin.seo.robots_txt')">
        <div class="space-y-2">
            <p class="{{ $hint }}" data-testid="seo-meta-explain-robots">
                <i class="{{ $hintIcon }}"></i><span>{{ gp247_language_render('admin.seo.robots_txt_explain') }}</span>
            </p>
            <textarea
                wire:model.live.blur="robotsTxt"
                rows="6"
                maxlength="{{ $robotsMaxLength }}"
                class="{{ $input }}"
                placeholder="User-agent: *&#10;Disallow: /admin/"
            >{{ $robotsTxt }}</textarea>
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>{{ gp247_language_render('admin.seo.robots_txt_help') }}</span>
                <span>{{ mb_strlen($robotsTxt) }}/{{ $robotsMaxLength }}</span>
            </div>
        </div>

        <x-slot:footer>
            <a href="{{ url('/robots.txt') }}" target="_blank" rel="noopener" class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                <i class="fas fa-external-link-alt mr-1"></i>{{ gp247_language_render('admin.seo.view_robots') }}
            </a>
        </x-slot:footer>
    </x-gp247::card>

    <x-gp247::card :title="gp247_language_render('admin.seo.jsonld_title')">
        <div class="space-y-2">
            <x-gp247::checkbox
                wire:model.live="jsonldEnabled"
                :label="gp247_language_render('admin.seo.jsonld_enabled')"
            />
            <p class="{{ $hint }}" data-testid="seo-meta-explain-jsonld">
                <i class="{{ $hintIcon }}"></i><span>{{ gp247_language_render('admin.seo.jsonld_explain') }} {{ gp247_language_render('admin.seo.jsonld_help') }}</span>
            </p>
        </div>
    </x-gp247::card>

    {{-- Site verification codes (US-SEO-site-verification, modification 20261116T050835) --}}
    <x-gp247::card :title="gp247_language_render('admin.seo.verify_title')">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach (['verifyGoogle' => ['admin.seo.verify_google', 'seo-meta-verify-google'], 'verifyBing' => ['admin.seo.verify_bing', 'seo-meta-verify-bing']] as $field => [$labelKey, $testId])
                <div class="space-y-1">
                    <label for="{{ $testId }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ gp247_language_render($labelKey) }}</label>
                    <input
                        id="{{ $testId }}"
                        type="text"
                        wire:model.live.blur="{{ $field }}"
                        maxlength="500"
                        autocomplete="off"
                        spellcheck="false"
                        data-testid="{{ $testId }}"
                        class="{{ $input }}"
                    >
                    @error($field)
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
        </div>
        <div class="mt-2 space-y-1">
            <p class="{{ $hint }}" data-testid="seo-meta-explain-verify">
                <i class="{{ $hintIcon }}"></i><span>{{ gp247_language_render('admin.seo.verify_explain') }}</span>
            </p>
            <details class="text-xs text-gray-500 dark:text-gray-400" data-testid="seo-meta-verify-howto">
                <summary class="cursor-pointer font-medium text-blue-600 hover:underline dark:text-blue-400">{{ gp247_language_render('admin.seo.verify_howto') }}</summary>
                <div class="mt-1 space-y-1">
                    <p>{{ gp247_language_render('admin.seo.verify_steps_google') }}</p>
                    <p>{{ gp247_language_render('admin.seo.verify_steps_bing') }}</p>
                </div>
            </details>
        </div>
    </x-gp247::card>

    {{-- IndexNow (US-SEO-indexnow) --}}
    <x-gp247::card :title="gp247_language_render('admin.seo.indexnow_title')">
        <div class="space-y-2">
            <x-gp247::checkbox wire:model.live="indexNowEnabled" data-testid="seo-indexnow-switch"
                :label="gp247_language_render('admin.seo.indexnow_enabled')" />
            <p class="{{ $hint }}" data-testid="seo-meta-explain-indexnow">
                <i class="{{ $hintIcon }}"></i><span>{{ gp247_language_render('admin.seo.indexnow_explain') }}</span>
            </p>
            @if ($indexNowKey !== '')
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <span class="text-gray-600 dark:text-gray-300">{{ gp247_language_render('admin.seo.indexnow_key') }}:</span>
                    <code class="rounded bg-gray-100 px-2 py-0.5 text-xs dark:bg-gray-900" data-testid="seo-indexnow-key">{{ $indexNowKey }}</code>
                    <a href="{{ url('/' . $indexNowKey . '.txt') }}" target="_blank" rel="noopener" class="text-xs text-blue-600 hover:underline dark:text-blue-400">/{{ $indexNowKey }}.txt</a>
                    <x-gp247::button size="sm" variant="ghost" wire:click="regenerateIndexNowKey"
                        wire:confirm="{{ gp247_language_render('admin.seo.indexnow_regenerate_confirm') }}" data-testid="seo-indexnow-regenerate">
                        <i class="fas fa-sync-alt"></i> {{ gp247_language_render('admin.seo.indexnow_regenerate') }}
                    </x-gp247::button>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400" data-testid="seo-meta-explain-indexnow-key">{{ gp247_language_render('admin.seo.indexnow_key_explain') }}</p>
            @endif
        </div>
    </x-gp247::card>

    {{-- Title templates per content type (US-SEO-title-template) --}}
    <x-gp247::card :title="gp247_language_render('admin.seo.title_template')">
        <p class="{{ $hint }} mb-3" data-testid="seo-meta-explain-title-template">
            <i class="{{ $hintIcon }}"></i><span>{{ gp247_language_render('admin.seo.title_template_explain') }}</span>
        </p>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach ($this->titleTemplateTypes() as $type => $labelKey)
                <div class="space-y-1">
                    <label for="seo-meta-title-template-{{ $type }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ gp247_language_render($labelKey) }}</label>
                    <input
                        id="seo-meta-title-template-{{ $type }}"
                        type="text"
                        wire:model.live.blur="titleTemplates.{{ $type }}"
                        maxlength="{{ \GP247\Front\Library\SeoContentMeta::TITLE_TEMPLATE_MAX }}"
                        placeholder="{title} | {site}"
                        autocomplete="off"
                        data-testid="seo-meta-title-template-{{ $type }}"
                        class="{{ $input }}"
                    >
                    @if ($type === \GP247\Front\Library\SeoContentMeta::TITLE_TEMPLATE_DEFAULT)
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.seo.title_template_default_explain') }}</p>
                    @endif
                    @error('titleTemplates.' . $type)
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
        </div>
    </x-gp247::card>

    <x-gp247::card>
        <p class="text-sm text-gray-600 dark:text-gray-300">
            {{ gp247_language_render('admin.seo.meta_og_note') }}
            <a href="{{ route('admin_store.index') }}" class="text-blue-600 hover:underline dark:text-blue-400">
                {{ gp247_language_render('admin.seo.meta_og_link') }}
            </a>
        </p>
    </x-gp247::card>
</div>
