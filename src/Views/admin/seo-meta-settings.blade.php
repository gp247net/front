{{--
    SEO "Meta & JSON-LD" screen (US-SEO-004/005, modification 20260711T154553
    — split out of the former combined seo-settings.blade.php): robots.txt
    editor, structured data (JSON-LD) master toggle, and a quick link to the
    existing "Website info" screen for default meta/OG (deliberately not
    duplicated here — see modification 20260711T114155).

    @aidlc-unit seo
    @aidlc-story US-SEO-004, US-SEO-005

    Variables:
      - $robotsMaxLength (int)
--}}
@php
    $input = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-mono transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
@endphp

<div class="space-y-6">
    <x-gp247::card :title="gp247_language_render('admin.seo.robots_txt')">
        <div class="space-y-2">
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
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.seo.jsonld_help') }}</p>
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
