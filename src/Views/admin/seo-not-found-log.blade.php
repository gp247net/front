{{--
    "404 log" screen (US-SEO-not-found-log, SEO phase 2): switch, logged storefront
    addresses of the current store, delete, and "Create redirect" (opens the Redirect 301
    screen with `from` prefilled). Paths and referrers are printed escaped.

    @aidlc-unit seo
    @aidlc-story US-SEO-not-found-log

    Variables:
      - $rows (LengthAwarePaginator|null): null while the table does not exist yet.
--}}
<div class="space-y-6">
    <x-gp247::card :title="gp247_language_render('admin.seo_not_found.title')">
        <div class="space-y-2">
            <x-gp247::checkbox wire:model.live="enabled" data-testid="seo-not-found-switch"
                :label="gp247_language_render('admin.seo_not_found.switch')"
                :help="gp247_language_render('admin.seo_not_found.help')" />
        </div>
    </x-gp247::card>

    <x-gp247::card>
        @if ($rows === null)
            <x-gp247::alert type="warning">{{ gp247_language_render('admin.seo_not_found.needs_update') }}</x-gp247::alert>
        @else
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <input type="search" wire:model.live.debounce.400ms="keyword"
                    placeholder="{{ gp247_language_render('admin.seo_not_found.search') }}"
                    class="w-full max-w-sm rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                @if ($rows->total() > 0)
                    <x-gp247::button size="sm" variant="ghost" wire:click="clearAll"
                        wire:confirm="{{ gp247_language_render('action.delete_confirm') }}" data-testid="seo-not-found-clear">
                        <i class="fas fa-trash-alt text-red-600"></i> {{ gp247_language_render('admin.seo_not_found.clear_all') }}
                    </x-gp247::button>
                @endif
            </div>

            <x-gp247::table :empty="$rows->isEmpty() ? gp247_language_render('admin.seo_not_found.empty') : null">
                <x-slot:head>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.seo_not_found.path') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.seo_not_found.hits') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.seo_not_found.last_seen') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('action.title') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($rows as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50" wire:key="seo-404-{{ $row->id }}" data-testid="seo-not-found-list-row">
                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                            <code class="break-all text-xs">{{ $row->path }}</code>
                            @if ($row->referer)
                                <p class="mt-1 break-all text-xs text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.seo_not_found.referer') }}: {{ $row->referer }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-800 dark:text-gray-100">{{ $row->hits }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ optional($row->last_seen_at)->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ gp247_route_admin('admin_seo_redirect.index') }}?from={{ urlencode($row->path) }}"
                                    class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-gray-700"
                                    data-testid="seo-not-found-create-redirect">
                                    <i class="fas fa-random"></i> {{ gp247_language_render('admin.seo_not_found.create_redirect') }}
                                </a>
                                <x-gp247::button size="sm" variant="ghost" wire:click="deleteRow({{ (int) $row->id }})"
                                    wire:confirm="{{ gp247_language_render('action.delete_confirm') }}" data-testid="seo-not-found-delete">
                                    <i class="fas fa-trash-alt text-red-600"></i>
                                </x-gp247::button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-gp247::table>

            <div class="mt-4">{{ $rows->links('gp247-admin::partials.pagination') }}</div>
        @endif
    </x-gp247::card>
</div>
