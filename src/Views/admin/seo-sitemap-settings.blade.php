{{--
    SEO "Sitemap.xml" screen (US-SEO-004, modification 20260711T154553 —
    split out of the former combined seo-settings.blade.php): product/category
    toggles + per-plugin toggles grouped together since both control what
    appears in sitemap.xml (modification 20260711T135915), manual "rebuild
    sitemap" action, and the wildcard alias exclusion list.

    @aidlc-unit seo
    @aidlc-story US-SEO-004

    Variables:
      - $excludeAliasesMaxLength (int)
      - $plugins (array<int, array{key:string, label:string}>)
--}}
@php
    $input = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-mono transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
@endphp

<div class="space-y-6">
    <x-gp247::card :title="gp247_language_render('admin.seo.sitemap_title')">
        <div class="space-y-4">
            <x-gp247::checkbox
                wire:model.live="includeProducts"
                :label="gp247_language_render('admin.seo.sitemap_include_products')"
            />
            <x-gp247::checkbox
                wire:model.live="includeCategories"
                :label="gp247_language_render('admin.seo.sitemap_include_categories')"
            />
            @if (count($plugins))
                <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                        {{ gp247_language_render('admin.seo.plugins_title') }}
                    </p>
                    <div class="space-y-4">
                        @foreach ($plugins as $plugin)
                            <x-gp247::checkbox
                                wire:model.live="pluginEnabled.{{ $plugin['key'] }}"
                                :label="$plugin['label']"
                            />
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.seo.plugins_help') }}</p>
                </div>
            @endif
        </div>

        <x-slot:footer>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <a href="{{ url('/sitemap.xml') }}" target="_blank" rel="noopener" class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                    <i class="fas fa-external-link-alt mr-1"></i>{{ gp247_language_render('admin.seo.view_sitemap') }}
                </a>
                <x-gp247::button wire:click="rebuildSitemap" variant="secondary">
                    <i class="fas fa-sync-alt"></i>{{ gp247_language_render('admin.seo.rebuild_sitemap') }}
                </x-gp247::button>
            </div>
        </x-slot:footer>
    </x-gp247::card>

    <x-gp247::card :title="gp247_language_render('admin.seo.exclude_aliases')">
        <div class="space-y-2">
            <textarea
                wire:model.live.blur="excludeAliases"
                rows="4"
                maxlength="{{ $excludeAliasesMaxLength }}"
                class="{{ $input }}"
                placeholder="old-product-*&#10;temp-page"
            >{{ $excludeAliases }}</textarea>
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>{{ gp247_language_render('admin.seo.exclude_aliases_help') }}</span>
                <span>{{ mb_strlen($excludeAliases) }}/{{ $excludeAliasesMaxLength }}</span>
            </div>
        </div>
    </x-gp247::card>
</div>
