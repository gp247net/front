{{--
    "Tracking code & cookies" screen (Unit tracking-consent, Bolt TC-1): store picker
    (multi-store only), the "tracking code on" switch, GA4 / GTM / Meta Pixel ids, custom
    code blocks and their before / after history. Administrators only (checked in the
    component). Custom code is shown in textareas through {{ }} — escaped, never run here.

    @aidlc-unit tracking-consent
    @aidlc-story US-tracking-consent-settings-screen, US-tracking-consent-preset-services, US-tracking-consent-custom-code

    Variables:
      - $storeOptions (array<string,string>) store id => name; empty on a single-store site
      - $isSubStore (bool), $rootOff (bool)
      - $regions, $categories (string[])
      - $revisions (FrontTrackingRevision[]), $maxSnippets (int)
--}}
@php
    $input = 'block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
    $hint = 'flex items-start gap-1 text-xs text-gray-500 dark:text-gray-400';
    $hintIcon = 'fas fa-info-circle mt-0.5 shrink-0 text-blue-600 dark:text-blue-400';
    $label = 'mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300';
    $fields = [
        'tracking_ga4_id' => ['id' => 'ga4', 'label' => 'admin.tracking.ga4_id', 'help' => 'admin.tracking.ga4_help', 'placeholder' => 'G-XXXXXXXXXX'],
        'tracking_gtm_id' => ['id' => 'gtm', 'label' => 'admin.tracking.gtm_id', 'help' => 'admin.tracking.gtm_help', 'placeholder' => 'GTM-XXXXXXX'],
        'tracking_pixel_id' => ['id' => 'pixel', 'label' => 'admin.tracking.pixel_id', 'help' => 'admin.tracking.pixel_help', 'placeholder' => '123456789012345'],
    ];
@endphp

<div class="space-y-6" data-testid="tracking-settings-page">
    <x-gp247::card :title="gp247_language_render('admin.tracking.title')">
        <div class="space-y-4">
            <p class="{{ $hint }}"><i class="{{ $hintIcon }}"></i><span>{{ gp247_language_render('admin.tracking.intro') }}</span></p>

            @if ($storeOptions !== [])
                <div class="max-w-sm">
                    <label for="tracking-store" class="{{ $label }}">{{ gp247_language_render('admin.tracking.store') }}</label>
                    <select id="tracking-store" wire:model.live="storeId" class="{{ $input }}" data-testid="tracking-settings-store">
                        @foreach ($storeOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <x-gp247::checkbox
                wire:model="enabled"
                wire:change="saveEnabled"
                data-testid="tracking-settings-enabled"
                :label="gp247_language_render('admin.tracking.enabled')"
                :help="gp247_language_render('admin.tracking.enabled_help')"
            />
            @if ($templateMissing !== '')
                <x-gp247::alert type="warning" data-testid="tracking-settings-template-warning">{{ gp247_language_render('admin.tracking.template_missing', ['template' => $templateMissing]) }}</x-gp247::alert>
            @endif
            @if ($rootOff)
                <x-gp247::alert type="warning" data-testid="tracking-settings-root-off">{{ gp247_language_render('admin.tracking.disabled_by_root') }}</x-gp247::alert>
            @endif
        </div>
    </x-gp247::card>

    <x-gp247::card :title="gp247_language_render('admin.tracking.services_title')" :subtitle="gp247_language_render('admin.tracking.services_help')">
        <form wire:submit="save" class="space-y-4" data-testid="tracking-settings-services">
            @foreach ($fields as $key => $field)
                <div>
                    <x-gp247::input
                        wire:model="form.{{ $key }}"
                        :label="gp247_language_render($field['label'])"
                        :help="gp247_language_render($field['help'])"
                        :error="$errors->first('form.' . $key)"
                        placeholder="{{ $field['placeholder'] }}"
                        autocomplete="off"
                        data-testid="tracking-settings-{{ $field['id'] }}"
                    />
                    @if ($isSubStore)
                        @include('gp247-front-admin::partials.tracking-inherit', ['key' => $key])
                    @endif
                </div>
            @endforeach

            <div>
                <label for="tracking-gtm-category" class="{{ $label }}">{{ gp247_language_render('admin.tracking.gtm_category') }}</label>
                <select id="tracking-gtm-category" wire:model="form.tracking_gtm_category" class="{{ $input }} max-w-sm" data-testid="tracking-settings-gtm-category">
                    @foreach ($categories as $category)
                        <option value="{{ $category }}">{{ gp247_language_render('admin.tracking.category_' . $category) }}</option>
                    @endforeach
                </select>
                <p class="{{ $hint }} mt-1" data-testid="tracking-settings-gtm-category-help"><i class="{{ $hintIcon }}"></i><span>{{ gp247_language_render('admin.tracking.gtm_category_help') }}</span></p>
                @error('form.tracking_gtm_category')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
                @if ($isSubStore)
                    @include('gp247-front-admin::partials.tracking-inherit', ['key' => 'tracking_gtm_category'])
                @endif
            </div>

            <div class="flex justify-end">
                <x-gp247::button type="submit" data-testid="tracking-settings-save">
                    <i class="fas fa-save"></i>{{ gp247_language_render('admin.tracking.save') }}
                </x-gp247::button>
            </div>
        </form>
    </x-gp247::card>

    <x-gp247::card :title="gp247_language_render('admin.tracking.banner_title')" :subtitle="gp247_language_render('admin.tracking.banner_help')">
        <form wire:submit="saveBanner" class="space-y-5" data-testid="tracking-settings-banner">
            @if ($offWithCode)
                <x-gp247::alert type="warning" data-testid="tracking-settings-banner-off-warning">{{ gp247_language_render('admin.tracking.banner_off_warning') }}</x-gp247::alert>
            @endif

            <fieldset>
                <legend class="{{ $label }}">{{ gp247_language_render('admin.tracking.banner_mode') }}</legend>
                <div class="space-y-2">
                    @foreach ($modes as $mode)
                        <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <input type="radio" wire:model="banner.mode" value="{{ $mode }}" class="mt-1" data-testid="tracking-settings-banner-mode-{{ $mode }}">
                            <span>
                                <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ gp247_language_render('admin.tracking.banner_mode_' . $mode) }}</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.tracking.banner_mode_' . $mode . '_help') }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('banner.mode')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                @if ($isSubStore)
                    @include('gp247-front-admin::partials.tracking-inherit', ['key' => 'tracking_banner_mode', 'inherited' => $bannerInherited])
                @endif
            </fieldset>

            <div>
                <label for="tracking-policy-page" class="{{ $label }}">{{ gp247_language_render('admin.tracking.policy_page') }}</label>
                <select id="tracking-policy-page" wire:model="banner.page" class="{{ $input }} max-w-sm" data-testid="tracking-settings-policy-page">
                    <option value="">{{ gp247_language_render('admin.tracking.policy_page_none') }}</option>
                    @foreach ($policyPages as $alias => $title)
                        <option value="{{ $alias }}">{{ $title }}</option>
                    @endforeach
                </select>
                <p class="{{ $hint }} mt-1"><i class="{{ $hintIcon }}"></i><span>{{ gp247_language_render('admin.tracking.policy_page_help') }}</span></p>
                @error('banner.page')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div class="space-y-3">
                <p class="{{ $label }}">{{ gp247_language_render('admin.tracking.banner_text') }}</p>
                <p class="{{ $hint }}"><i class="{{ $hintIcon }}"></i><span>{{ gp247_language_render('admin.tracking.banner_text_help') }}</span></p>
                @foreach ($languages as $code => $name)
                    <div>
                        <label for="tracking-banner-text-{{ $code }}" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ $name }} ({{ $code }})</label>
                        <textarea id="tracking-banner-text-{{ $code }}" wire:model="banner.text.{{ $code }}" rows="2" maxlength="{{ $maxBannerText }}" class="{{ $input }}" data-testid="tracking-settings-banner-text-{{ $code }}"></textarea>
                        @error('banner.text.' . $code)<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                @endforeach
                @if ($isSubStore)
                    @include('gp247-front-admin::partials.tracking-inherit', ['key' => 'tracking_banner_text', 'inherited' => $bannerInherited])
                @endif
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2">
                <x-gp247::button type="button" variant="secondary" wire:click="askAgain" wire:confirm="{{ gp247_language_render('admin.tracking.ask_again_confirm') }}" data-testid="tracking-settings-ask-again">
                    <i class="fas fa-redo"></i>{{ gp247_language_render('admin.tracking.ask_again') }}
                </x-gp247::button>
                <x-gp247::button type="submit" data-testid="tracking-settings-banner-save">
                    <i class="fas fa-save"></i>{{ gp247_language_render('admin.tracking.save') }}
                </x-gp247::button>
            </div>
            <p class="{{ $hint }}" data-testid="tracking-settings-policy-version"><i class="{{ $hintIcon }}"></i><span>{{ gp247_language_render('admin.tracking.ask_again_help') }} ({{ gp247_language_render('admin.tracking.policy_version') }}: {{ $policyVersion }})</span></p>
        </form>
    </x-gp247::card>

    <x-gp247::card :title="gp247_language_render('admin.tracking.custom_title')" :subtitle="gp247_language_render('admin.tracking.custom_help')">
        <div class="space-y-4">
            <x-gp247::alert type="warning" data-testid="tracking-settings-custom-warning">{{ gp247_language_render('admin.tracking.custom_warning') }}</x-gp247::alert>

            <div class="space-y-1" data-testid="tracking-settings-custom-guide">
                @foreach (['snippet_region_help', 'snippet_category_help', 'snippet_save_help'] as $guide)
                    <p class="{{ $hint }}"><i class="{{ $hintIcon }}"></i><span>{{ gp247_language_render('admin.tracking.' . $guide) }}</span></p>
                @endforeach
            </div>

            @if ($isSubStore)
                <x-gp247::checkbox
                    wire:model="snippetOwn"
                    wire:change="saveSnippetOwn"
                    data-testid="tracking-settings-snippet-own"
                    :label="gp247_language_render('admin.tracking.snippet_own')"
                />
                @unless ($snippetOwn)
                    <p class="{{ $hint }}" data-testid="tracking-settings-snippet-root-note"><i class="{{ $hintIcon }}"></i><span>{{ gp247_language_render('admin.tracking.snippet_root_note') }}</span></p>
                @endunless
            @endif

            @error('snippets')
                <p class="text-sm text-red-500" data-testid="tracking-settings-snippets-error">{{ $message }}</p>
            @enderror

            @forelse ($snippets as $i => $snippet)
                <div wire:key="tracking-snippet-{{ $i }}" class="space-y-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700" data-testid="tracking-settings-snippet-{{ $i }}">
                    <div class="grid gap-3 md:grid-cols-3">
                        <x-gp247::input
                            wire:model="snippets.{{ $i }}.label"
                            :label="gp247_language_render('admin.tracking.snippet_label')"
                            :error="$errors->first('snippets.' . $i . '.label')"
                            maxlength="100"
                            data-testid="tracking-settings-snippet-{{ $i }}-label"
                        />
                        <div>
                            <label class="{{ $label }}">{{ gp247_language_render('admin.tracking.snippet_region') }}</label>
                            <select wire:model="snippets.{{ $i }}.region" class="{{ $input }}" data-testid="tracking-settings-snippet-{{ $i }}-region">
                                @foreach ($regions as $region)
                                    <option value="{{ $region }}">{{ gp247_language_render('admin.tracking.region_' . $region) }}</option>
                                @endforeach
                            </select>
                            @error('snippets.' . $i . '.region')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $label }}">{{ gp247_language_render('admin.tracking.snippet_category') }}</label>
                            <select wire:model="snippets.{{ $i }}.category" class="{{ $input }}" data-testid="tracking-settings-snippet-{{ $i }}-category">
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}">{{ gp247_language_render('admin.tracking.category_' . $category) }}</option>
                                @endforeach
                            </select>
                            @error('snippets.' . $i . '.category')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="{{ $label }}">{{ gp247_language_render('admin.tracking.snippet_content') }}</label>
                        <textarea wire:model="snippets.{{ $i }}.content" rows="6" spellcheck="false" class="{{ $input }} font-mono" data-testid="tracking-settings-snippet-{{ $i }}-content"></textarea>
                        @error('snippets.' . $i . '.content')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex items-center justify-between">
                        <x-gp247::checkbox wire:model="snippets.{{ $i }}.status" data-testid="tracking-settings-snippet-{{ $i }}-active" :label="gp247_language_render('admin.tracking.snippet_active')" />
                        <x-gp247::button type="button" variant="ghost" size="sm" wire:click="removeSnippet({{ $i }})" data-testid="tracking-settings-snippet-{{ $i }}-remove">
                            <i class="fas fa-trash"></i>{{ gp247_language_render('admin.tracking.snippet_remove') }}
                        </x-gp247::button>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400" data-testid="tracking-settings-snippet-empty">{{ gp247_language_render('admin.tracking.snippet_empty') }}</p>
            @endforelse

            <div class="flex flex-wrap justify-between gap-2">
                <x-gp247::button type="button" variant="secondary" wire:click="addSnippet" :disabled="count($snippets) >= $maxSnippets" data-testid="tracking-settings-snippet-add">
                    <i class="fas fa-plus"></i>{{ gp247_language_render('admin.tracking.snippet_add') }}
                </x-gp247::button>
                <x-gp247::button type="button" wire:click="saveSnippets" data-testid="tracking-settings-snippet-save">
                    <i class="fas fa-save"></i>{{ gp247_language_render('admin.tracking.snippet_save') }}
                </x-gp247::button>
            </div>
        </div>
    </x-gp247::card>

    <x-gp247::card :title="gp247_language_render('admin.tracking.revisions_title')" :subtitle="gp247_language_render('admin.tracking.revisions_help')">
        <div class="space-y-2" data-testid="tracking-settings-revisions">
            <p class="{{ $hint }}" data-testid="tracking-settings-revisions-how"><i class="{{ $hintIcon }}"></i><span>{{ gp247_language_render('admin.tracking.revisions_how') }}</span></p>
            @forelse ($revisions as $revision)
                <details class="rounded-lg border border-gray-200 dark:border-gray-700" data-testid="tracking-settings-revision-{{ $revision->id }}">
                    <summary class="cursor-pointer px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                        {{ optional($revision->created_at)->format('Y-m-d H:i:s') }}
                        · {{ gp247_language_render('admin.tracking.revision_by') }}: {{ $revision->admin_user_name ?: '—' }}
                    </summary>
                    <div class="grid gap-3 p-4 md:grid-cols-2">
                        <div>
                            <p class="{{ $label }}">{{ gp247_language_render('admin.tracking.revision_before') }}</p>
                            <textarea readonly rows="8" class="{{ $input }} font-mono text-xs">{{ json_encode(json_decode($revision->before, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</textarea>
                        </div>
                        <div>
                            <p class="{{ $label }}">{{ gp247_language_render('admin.tracking.revision_after') }}</p>
                            <textarea readonly rows="8" class="{{ $input }} font-mono text-xs">{{ json_encode(json_decode($revision->after, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</textarea>
                        </div>
                    </div>
                </details>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.tracking.revisions_empty') }}</p>
            @endforelse
        </div>
    </x-gp247::card>
</div>
