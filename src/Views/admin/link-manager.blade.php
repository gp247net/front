{{--
    Link manager — two-panel: form (left) + list (right) on the ResourcePanel base
    (ADR-005, ADR-007, ui-tailadmin P1). Multi-store assignment when active. UI text
    via gp247_language_render.

    @aidlc-unit front-admin
    @aidlc-story US-FADM-003
    @aidlc-adr ADR-001, ADR-005, ADR-006, ADR-007

    Variables: $rows (FrontLink paginator), $groups (FrontLinkGroup[]),
               $collections (FrontLink[]), $multiStore (bool), $storeList (array),
               $editingId, $form, $stores, $sortField, $sortDir, $keyword.
--}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    {{-- Left: add / edit form --}}
    <x-gp247::card :title="gp247_language_render($editingId ? 'action.edit' : 'admin.link.add_new')">
        <form wire:submit="save" class="space-y-4">

            <x-gp247::input :label="gp247_language_render('admin.link.name')" name="name"
                wire:model="form.name" :error="$errors->first('form.name')" required />

            <div class="space-y-1">
                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ gp247_language_render('admin.link.type') }}</label>
                <select id="type" wire:model.live="form.type"
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    <option value="">{{ gp247_language_render('admin.link.type_single') }}</option>
                    <option value="collection">{{ gp247_language_render('admin.link.type_collection') }}</option>
                </select>
            </div>

            @if (($form['type'] ?? '') !== 'collection')
                <x-gp247::input :label="gp247_language_render('admin.link.url')" name="url"
                    wire:model="form.url" :error="$errors->first('form.url')" required />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <label for="target" class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ gp247_language_render('admin.link.target') }}</label>
                        <select id="target" wire:model="form.target"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option value="_self">_self</option>
                            <option value="_blank">_blank</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label for="collection_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ gp247_language_render('admin.link.collection') }}</label>
                        <select id="collection_id" wire:model="form.collection_id"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option value="">—</option>
                            @foreach ($collections as $collection)
                                <option value="{{ $collection->id }}">{{ $collection->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-1">
                    <label for="group" class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ gp247_language_render('admin.link.group') }}</label>
                    <select id="group" wire:model="form.group"
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        <option value="">—</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->code }}">{{ $group->name }} ({{ $group->code }})</option>
                        @endforeach
                    </select>
                    @error('form.group')<p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <x-gp247::input type="number" min="0" :label="gp247_language_render('admin.sort')"
                    name="sort" wire:model="form.sort" :error="$errors->first('form.sort')" required />
            </div>

            @if ($multiStore)
                <div class="space-y-1">
                    <label class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"><i class="fas fa-store text-blue-500"></i>{{ gp247_language_render('admin.store') }}</label>
                    <div class="flex flex-wrap gap-3 rounded-md border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
                        @foreach ($storeList as $storeId => $storeName)
                            <x-gp247::checkbox :label="$storeName" wire:model="stores" value="{{ $storeId }}" id="lnkm-store-{{ $storeId }}" />
                        @endforeach
                    </div>
                </div>
            @endif

            <x-gp247::checkbox :label="gp247_language_render('admin.active')" wire:model="form.status" value="1" />

            <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
                <x-gp247::button variant="secondary" wire:click="cancelEdit" data-testid="admin-link-form-cancel">
                    {{ gp247_language_render($editingId ? 'admin.cancel' : 'admin.reset') }}
                </x-gp247::button>
                <x-gp247::button type="submit" wire:loading.attr="disabled">
                    <i class="fas fa-save"></i> {{ gp247_language_render($editingId ? 'admin.update' : 'admin.submit') }}
                </x-gp247::button>
            </div>
        </form>
    </x-gp247::card>

    {{-- Right: list --}}
    <x-gp247::card :title="gp247_language_render('admin.link.title')">
        <div class="mb-3 flex items-center gap-2">
            <input type="search" wire:model.live.debounce.300ms="keyword"
                placeholder="{{ gp247_language_render('admin.link.name') }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
            @if (Route::has('admin_link_group.index'))
                <x-gp247::button href="{{ gp247_route_admin('admin_link_group.index') }}" variant="secondary" wire:navigate size="sm" class="shrink-0">
                    <i class="fas fa-indent"></i>
                </x-gp247::button>
            @endif
        </div>

        <x-gp247::table :empty="$rows->isEmpty() ? gp247_language_render('admin.no_records') : null">
            <x-slot:head>
                <tr>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('name')">
                        {{ gp247_language_render('admin.link.name') }} @if ($sortField === 'name')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ gp247_language_render('admin.link.collection') }}
                    </th>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('group')">
                        {{ gp247_language_render('admin.link.group') }} @if ($sortField === 'group')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('status')">
                        {{ gp247_language_render('admin.status') }} @if ($sortField === 'status')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.action') }}</th>
                </tr>
            </x-slot:head>

            @foreach ($rows as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ (string) $row->id === (string) $editingId ? 'bg-blue-100 border-l-4 border-blue-500 dark:bg-blue-900 dark:border-blue-500' : '' }}"
                    wire:key="link-{{ $row->id }}">
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                        @if ($row->type === 'collection')
                            <span class="mr-1 text-yellow-500"><i class="fas fa-folder-open"></i></span>
                        @endif
                        {{ $row->name }}
                        @if ($row->url && $row->type !== 'collection')
                            <a href="{{ gp247_url_render($row->url) }}" target="_blank" class="ml-1 text-xs text-blue-500"><i class="fas fa-external-link-alt"></i></a>
                        @endif
                        @if ($multiStore ?? false)
                            @php
                                $visibleStores = ((string)$row->id === (string)$editingId)
                                    ? $row->stores->filter(fn($s) => in_array((string)$s->id, array_map('strval', $stores ?? [])))
                                    : $row->stores;
                            @endphp
                            @if ($visibleStores->isNotEmpty())
                                <div class="mt-1 flex flex-wrap items-center gap-1">
                                    <i class="fas fa-store text-xs text-blue-500"></i>
                                    @foreach ($visibleStores as $store)
                                        <span class="inline-flex items-center rounded bg-gray-100 px-2 py-0.5 text-xs text-blue-500 dark:bg-gray-700">{{ $store->descriptions->firstWhere('lang', gp247_get_locale())?->name ?? $store->code }}</span>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $row->collection->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $row->group }}</td>
                    <td class="px-4 py-3">
                        <x-gp247::badge :color="$row->status ? 'green' : 'gray'">{{ $row->status ? gp247_language_render('admin.active') : gp247_language_render('admin.inactive') }}</x-gp247::badge>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <x-gp247::button size="sm" variant="ghost" wire:click="editRow('{{ $row->id }}')" data-testid="admin-link-list-edit"><i class="fas fa-edit"></i></x-gp247::button>
                            <x-gp247::button size="sm" variant="ghost" wire:click="delete('{{ $row->id }}')" wire:confirm="{{ gp247_language_render('action.delete_confirm') }}"><i class="fas fa-trash-alt text-red-600"></i></x-gp247::button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-gp247::table>

        <div class="mt-4">{{ $rows->links('gp247-admin::partials.pagination') }}</div>
    </x-gp247::card>
</div>
