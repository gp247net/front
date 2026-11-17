{{--
    "SEO check" screen (US-SEO-audit, SEO phase 3): run button, time of the last check,
    findings grouped "To fix" / "To review", examples with a link to the edit screen.
    Example labels are text typed by staff — printed escaped.

    @aidlc-unit seo
    @aidlc-story US-SEO-audit

    Variables:
      - $groups (array<'fix'|'review', array<int, array>>)
--}}
<div class="space-y-6">
    <x-gp247::card :title="gp247_language_render('admin.seo_audit.title')">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ gp247_language_render('admin.seo_audit.help') }}</p>
            <div class="flex items-center gap-2">
                <x-gp247::button wire:click="runAudit" wire:loading.attr="disabled" wire:target="runAudit" data-testid="seo-audit-run">
                    <i class="fas fa-search"></i> {{ gp247_language_render('admin.seo_audit.run') }}
                </x-gp247::button>
            </div>
        </div>
        @if ($result)
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400" data-testid="seo-audit-checked-at">
                {{-- WHY: the stored time is ISO 8601 in UTC (also read by MCP); show it in the app timezone like the System alerts block. --}}
                {{ gp247_language_render('admin.seo_audit.checked_at') }}: {{ rescue(fn () => \Illuminate\Support\Carbon::parse((string) ($result['checked_at'] ?? ''))->setTimezone(config('app.timezone'))->format('H:i d/m/Y'), (string) ($result['checked_at'] ?? ''), false) }}
                @if (empty($result['complete']))
                    — <span class="text-amber-600 dark:text-amber-400">{{ gp247_language_render('admin.seo_audit.partial') }}</span>
                @endif
            </p>
        @else
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.seo_audit.never') }}</p>
        @endif
    </x-gp247::card>

    @if ($result)
        @foreach ([\GP247\Front\Library\SeoAudit::LEVEL_FIX => 'red', \GP247\Front\Library\SeoAudit::LEVEL_REVIEW => 'amber'] as $level => $color)
            <x-gp247::card :title="gp247_language_render('admin.seo_audit.level_' . $level)">
                @if (empty($groups[$level]))
                    <p class="text-sm text-green-700 dark:text-green-400">{{ gp247_language_render('admin.seo_audit.none') }}</p>
                @else
                    <div class="space-y-4">
                        @foreach ($groups[$level] as $finding)
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700" data-testid="seo-audit-finding-{{ $finding['code'] }}">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ gp247_language_render('admin.seo_audit.code_' . $finding['code']) }}</p>
                                    <x-gp247::badge :color="$color">{{ $finding['count'] }}</x-gp247::badge>
                                </div>
                                @if ($finding['code'] === 'orphan_seo_rows')
                                    <x-gp247::button size="sm" variant="ghost" wire:click="cleanOrphans" class="mt-2"
                                        wire:confirm="{{ gp247_language_render('action.delete_confirm') }}" data-testid="seo-audit-clean-orphans">
                                        <i class="fas fa-broom"></i> {{ gp247_language_render('admin.seo_audit.clean') }}
                                    </x-gp247::button>
                                @endif
                                @if (!empty($finding['examples']))
                                    <ul class="mt-2 space-y-1 text-sm">
                                        @foreach ($finding['examples'] as $example)
                                            <li class="flex items-center gap-2">
                                                {{-- Type name in the admin language (registered label); an unregistered type keeps its key. --}}
                                                <span class="text-xs text-gray-400">{{ isset(\GP247\Front\Library\SeoContentMeta::types()[$example['type']]['label']) ? gp247_language_render(\GP247\Front\Library\SeoContentMeta::types()[$example['type']]['label']) : $example['type'] }}</span>
                                                @if ($example['url'] !== '')
                                                    <a href="{{ $example['url'] }}" class="break-all text-blue-600 hover:underline dark:text-blue-400" data-testid="seo-audit-example-link">{{ $example['label'] !== '' ? $example['label'] : $example['id'] }}</a>
                                                @else
                                                    <span class="break-all text-gray-700 dark:text-gray-200">{{ $example['label'] !== '' ? $example['label'] : $example['id'] }}</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-gp247::card>
        @endforeach
    @endif
</div>
