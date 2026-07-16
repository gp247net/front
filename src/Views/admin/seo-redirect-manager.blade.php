{{--
    "Redirect 301" admin screen — two-panel: form (left) + list (right) on the
    ResourcePanel base (ADR-005, ADR-007, ui-tailadmin P1). Third screen under
    the "SEO" menu group. UI text via gp247_language_render.

    @aidlc-unit seo
    @aidlc-story US-SEO-006
    @aidlc-adr ADR-001, ADR-005, ADR-006, ADR-007

    Variables: $rows (FrontRedirect paginator); $form, $editingId,
               $sortField, $sortDir, $keyword (component state).
--}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    {{-- Left: add / edit form --}}
    <x-gp247::card :title="gp247_language_render($editingId ? 'action.edit' : 'admin.seo_redirect.add_new')">
        <form wire:submit="save" class="space-y-4">

            <x-gp247::input
                :label="gp247_language_render('admin.seo_redirect.from')"
                name="from"
                placeholder="/old-page.html"
                wire:model="form.from"
                :error="$errors->first('form.from')"
                required />

            <x-gp247::input
                :label="gp247_language_render('admin.seo_redirect.to')"
                name="to"
                placeholder="/new-page.html"
                wire:model="form.to"
                :error="$errors->first('form.to')"
                required />

            @if (($form['from'] ?? '') === '/')
                <p class="text-sm text-yellow-600 dark:text-yellow-400">
                    <i class="fas fa-exclamation-triangle"></i>
                    {{ gp247_language_render('admin.seo_redirect.homepage_warning') }}
                </p>
            @endif

            <div class="space-y-1">
                <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ gp247_language_render('admin.seo_redirect.code') }}</label>
                <select id="code" wire:model="form.code"
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    <option value="301">301 — {{ gp247_language_render('admin.seo_redirect.code_permanent') }}</option>
                    <option value="302">302 — {{ gp247_language_render('admin.seo_redirect.code_temporary') }}</option>
                </select>
                @error('form.code')<p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <x-gp247::checkbox :label="gp247_language_render('admin.active')" wire:model="form.status" value="1" />

            <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
                <x-gp247::button variant="secondary" href="{{ gp247_route_admin('admin_seo_redirect.index') }}" wire:navigate>
                    {{ gp247_language_render($editingId ? 'admin.cancel' : 'admin.reset') }}
                </x-gp247::button>
                <x-gp247::button type="submit" wire:loading.attr="disabled">
                    <i class="fas fa-save"></i>
                    {{ gp247_language_render($editingId ? 'admin.update' : 'admin.submit') }}
                </x-gp247::button>
            </div>
        </form>
    </x-gp247::card>

    {{-- Right: list --}}
    <x-gp247::card :title="gp247_language_render('admin.seo_redirect.title')">

        <div class="mb-3">
            <input type="search" wire:model.live.debounce.300ms="keyword"
                placeholder="{{ gp247_language_render('admin.search') }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
        </div>

        <x-gp247::table :empty="$rows->isEmpty() ? gp247_language_render('admin.no_records') : null">
            <x-slot:head>
                <tr>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                        wire:click="setSort('from')">
                        {{ gp247_language_render('admin.seo_redirect.from') }}
                        @if ($sortField === 'from')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                        wire:click="setSort('to')">
                        {{ gp247_language_render('admin.seo_redirect.to') }}
                        @if ($sortField === 'to')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                        wire:click="setSort('code')">
                        {{ gp247_language_render('admin.seo_redirect.code') }}
                        @if ($sortField === 'code')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                        wire:click="setSort('status')">
                        {{ gp247_language_render('admin.status') }}
                        @if ($sortField === 'status')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ gp247_language_render('admin.action') }}
                    </th>
                </tr>
            </x-slot:head>

            @foreach ($rows as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ (string) $row->id === (string) $editingId ? 'bg-blue-50 dark:bg-blue-900/30' : '' }}"
                    wire:key="seo-redirect-{{ $row->id }}">
                    <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100"><code class="text-xs">{{ $row->from }}</code></td>
                    <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100"><code class="text-xs">{{ $row->to }}</code></td>
                    <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">{{ $row->code }}</td>
                    <td class="px-4 py-3">
                        <x-gp247::badge :color="$row->status ? 'green' : 'gray'">{{ $row->status ? gp247_language_render('admin.active') : gp247_language_render('admin.inactive') }}</x-gp247::badge>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <x-gp247::button size="sm" variant="ghost"
                                href="{{ gp247_route_admin('admin_seo_redirect.edit', $row->id) }}"
                                wire:navigate>
                                <i class="fas fa-edit"></i>
                            </x-gp247::button>
                            <x-gp247::button size="sm" variant="ghost"
                                wire:click="delete('{{ $row->id }}')"
                                wire:confirm="{{ gp247_language_render('action.delete_confirm') }}">
                                <i class="fas fa-trash-alt text-red-600"></i>
                            </x-gp247::button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-gp247::table>

        <div class="mt-4">{{ $rows->links('gp247-admin::partials.pagination') }}</div>
    </x-gp247::card>

</div>
