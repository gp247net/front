{{--
    Shared SEO panel for an admin edit screen whose Livewire component uses
    GP247\Front\Admin\Livewire\Concerns\HasSeoPanel (SEO phase 1, modification 20261117T140612).
    Own SEO title/description/canonical per language, share image and robots flags; a
    client-side Google preview (Alpine, no server call) falls back to the screen's own
    name/description while the SEO fields are empty.

    @aidlc-unit seo
    @aidlc-story US-SEO-content-meta, US-SEO-content-index-control
    @aidlc-adr ADR-seo-content-meta-table

    Parameters:
      - nameField (string): Livewire path of the content name, `{code}` = language code
        (e.g. 'desc.{code}.name', 'descriptions.{code}.name', 'form.name').
      - descriptionField (string|null): same for the plain description; null = none.
      - aliasField (string): Livewire path of the alias (default 'form.alias').
      - imageType (string): LFM folder category of the share image.
--}}
@php
    $nameField = $nameField ?? 'form.name';
    $descriptionField = $descriptionField ?? null;
    $aliasField = $aliasField ?? 'form.alias';
    $imageType = $imageType ?? 'other';
    $languages = \GP247\Core\Models\AdminLanguage::getListActive();
    $maxTitle = \GP247\Front\Library\SeoContentMeta::MAX_TITLE;
    $maxDescription = \GP247\Front\Library\SeoContentMeta::MAX_DESCRIPTION;
    $host = preg_replace('#^https?://#', '', rtrim(url('/'), '/'));
    $inputCls = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
@endphp
<div class="space-y-4" data-testid="seo-panel">
    <p class="text-xs text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.seo.panel_help') }}</p>

    @foreach ($languages as $code => $lang)
        @php
            $titlePath = "seo.desc.{$code}.title";
            $descPath = "seo.desc.{$code}.description";
            $namePath = str_replace('{code}', $code, $nameField);
            $fallbackDescPath = $descriptionField !== null ? str_replace('{code}', $code, $descriptionField) : null;
            $canonicalPath = "seo.desc.{$code}.canonical";
        @endphp
        <div class="space-y-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700" wire:key="seo-lang-{{ $code }}">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                @if ($lang->icon)
                    {!! gp247_image_render($lang->icon, '20px', '20px', $lang->name) !!}
                @endif
                {{ $lang->name }}
            </h3>

            <div>
                <label for="seo-title-{{ $code }}" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ gp247_language_render('admin.seo.seo_title') }}</label>
                <input id="seo-title-{{ $code }}" type="text" wire:model="{{ $titlePath }}" maxlength="{{ $maxTitle }}"
                    data-testid="seo-title-{{ $code }}" class="{{ $inputCls }}">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><span x-text="($wire.$get(@js($titlePath)) || '').length"></span> / 60 {{ gp247_language_render('admin.seo.chars') }}</p>
                @error($titlePath)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="seo-description-{{ $code }}" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ gp247_language_render('admin.seo.seo_description') }}</label>
                <textarea id="seo-description-{{ $code }}" rows="2" wire:model="{{ $descPath }}" maxlength="{{ $maxDescription }}"
                    data-testid="seo-description-{{ $code }}" class="{{ $inputCls }}"></textarea>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><span x-text="($wire.$get(@js($descPath)) || '').length"></span> / 160 {{ gp247_language_render('admin.seo.chars') }}</p>
                @error($descPath)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="seo-canonical-{{ $code }}" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ gp247_language_render('admin.seo.canonical') }}</label>
                <input id="seo-canonical-{{ $code }}" type="url" wire:model="seo.desc.{{ $code }}.canonical" placeholder="https://"
                    data-testid="seo-canonical-{{ $code }}" class="{{ $inputCls }}">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.seo.canonical_help') }}</p>
                <p x-show="($wire.$get(@js($canonicalPath)) || '') !== '' && !($wire.$get(@js($canonicalPath)) || '').includes($wire.$get(@js($aliasField)) || '\u0000')"
                    x-cloak class="mt-1 text-xs text-amber-600 dark:text-amber-400">{{ gp247_language_render('admin.seo.canonical_warning') }}</p>
                @error("seo.desc.{$code}.canonical")<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Google preview: SEO value, else the screen's own value --}}
            <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/40" data-testid="seo-preview">
                <p class="mb-1 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.seo.preview') }}</p>
                <p class="truncate text-xs text-gray-600 dark:text-gray-400">{{ $host }} › <span x-text="$wire.$get(@js($aliasField)) || ''"></span></p>
                <p class="truncate text-base text-blue-700 dark:text-blue-400"
                    x-text="(($wire.$get(@js($titlePath)) || $wire.$get(@js($namePath)) || '') + '').substring(0, 60)"></p>
                <p class="text-sm text-gray-600 dark:text-gray-300"
                    x-text="(($wire.$get(@js($descPath)) || {{ $fallbackDescPath !== null ? '$wire.$get(' . \Illuminate\Support\Js::from($fallbackDescPath) . ')' : "''" }} || '') + '').substring(0, 160)"></p>
            </div>
        </div>
    @endforeach

    <x-gp247::media-input :working-store="$formStoreId ?? ''" :label="gp247_language_render('admin.seo.og_image')" name="seo_og_image"
        :type="$imageType" wire:model="seo.og_image" :value="$seo['og_image'] ?? ''" :error="$errors->first('seo.og_image')"
        data-testid="seo-og-image" />

    <div class="space-y-2 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
            <input type="checkbox" wire:model="seo.noindex" data-testid="seo-noindex" class="rounded border-gray-300 dark:border-gray-600">
            {{ gp247_language_render('admin.seo.noindex') }}
        </label>
        <p x-show="$wire.$get('seo.noindex')" x-cloak class="text-xs text-amber-600 dark:text-amber-400">{{ gp247_language_render('admin.seo.noindex_warning') }}</p>
        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
            <input type="checkbox" wire:model="seo.nofollow" data-testid="seo-nofollow" class="rounded border-gray-300 dark:border-gray-600">
            {{ gp247_language_render('admin.seo.nofollow') }}
        </label>
    </div>
</div>
