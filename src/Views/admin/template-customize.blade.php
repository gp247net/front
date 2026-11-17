{{--
    Template picker at <admin>/theme — the fixed "Customize theme" menu entry (US-FADM-template-customize-entry).
    Every installed template with what it lets the owner customize; only the template the working store uses can be
    opened (through its clickApp(), like the config button on the Template manager). Others are shown disabled: this page
    never changes the store's template — that stays in Website information.

    @aidlc-unit front-admin
    @aidlc-story US-FADM-template-customize-entry
    @aidlc-adr front-admin_template-settings-entry
--}}
@extends('gp247-admin::layouts.plain')

@section('main')
@php
    $capabilityBadge = [
        'settings' => ['bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300', 'fas fa-sliders-h', 'admin.template.customize_has_settings'],
        'none' => ['bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300', 'fas fa-ban', 'admin.template.customize_no_settings'],
        'disabled' => ['bg-amber-50 text-amber-700 dark:bg-gray-700 dark:text-amber-300', 'fas fa-power-off', 'admin.template.customize_disabled'],
    ];
@endphp
<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800" data-testid="front-admin-template-customize">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.template.customize_intro') }}</p>
    </div>

    <ul class="divide-y divide-gray-100 dark:divide-gray-700/50">
        @forelse ($templates as $tpl)
            @php
                [$badgeClass, $badgeIcon, $badgeLabel] = $capabilityBadge[$tpl['capability']] ?? $capabilityBadge['none'];
                $canOpen = $tpl['inUse'] && $tpl['capability'] === 'settings';
            @endphp
            <li class="flex items-center gap-4 px-5 py-4 {{ $tpl['inUse'] ? '' : 'opacity-70' }}"
                data-testid="front-admin-template-customize-card-{{ $tpl['key'] }}"
                data-in-use="{{ $tpl['inUse'] ? 1 : 0 }}"
                data-capability="{{ $tpl['capability'] }}"
                @unless ($tpl['inUse']) aria-disabled="true" @endunless>
                <div class="shrink-0">
                    @if ($tpl['image'])
                        {!! gp247_image_render('GP247/' . $tpl['image'], '44px', 'rounded-lg border border-gray-200 dark:border-gray-600', $tpl['title']) !!}
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-medium text-gray-800 dark:text-gray-100">{{ $tpl['title'] }}</span>
                        <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $tpl['key'] }}</code>
                        @if ($tpl['version'] !== '')
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $tpl['version'] }}</span>
                        @endif
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        @if ($tpl['inUse'])
                            <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                <i class="fas fa-check"></i>
                                {{ gp247_language_render('admin.template.customize_in_use') }}
                            </span>
                        @endif
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium {{ $badgeClass }}">
                            <i class="{{ $badgeIcon }}"></i>
                            {{ gp247_language_render($badgeLabel) }}
                        </span>
                    </div>
                </div>
                <div class="shrink-0">
                    @if ($canOpen)
                        <a href="{{ $configUrl }}?action=config&key={{ $tpl['key'] }}"
                            data-testid="front-admin-template-customize-open-{{ $tpl['key'] }}"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            <i class="fas fa-palette text-xs"></i>
                            {{ gp247_language_render('admin.template.customize_open') }}
                        </a>
                    @endif
                </div>
            </li>
        @empty
            <li class="px-5 py-12 text-center text-sm text-gray-400 dark:text-gray-500">{{ gp247_language_render('admin.extension.empty') }}</li>
        @endforelse
    </ul>

    <div class="border-t border-gray-200 bg-gray-50 px-5 py-3 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800/40 dark:text-gray-400">
        <i class="fas fa-info-circle"></i>
        {{ gp247_language_render('admin.template.customize_switch_hint') }}
        <a href="{{ $storeInfoUrl }}" data-testid="front-admin-template-customize-store-info" class="font-medium text-blue-600 hover:underline dark:text-blue-400">
            {{ gp247_language_render('admin.menu_titles.store_info') }}
        </a>
    </div>
</div>
@endsection
