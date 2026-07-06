{{--
    Page (CMS) manager — two-panel: form (left) + list (right) on the ResourcePanel base
    (ADR-005, ADR-007, ui-tailadmin P1). Per-language descriptions with rich-text content
    via <x-gp247::rich-editor>. Image via <x-gp247::media-input> (LFM). Multi-store
    assignment when active. UI text via gp247_language_render.

    @aidlc-unit front-admin
    @aidlc-story US-FADM-002
    @aidlc-adr ADR-001, ADR-005, ADR-006, ADR-007

    Variables: $rows (FrontPage paginator, descriptions eager-loaded),
               $languages (AdminLanguage[] keyed by code), $multiStore (bool),
               $storeList (array), $editingId, $form, $descriptions, $stores,
               $sortField, $sortDir, $keyword.
--}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    {{-- Left: add / edit form --}}
    <x-gp247::card :title="gp247_language_render($editingId ? 'action.edit' : 'admin.page.add_new')">
        <form wire:submit="save" class="space-y-4">

            {{-- Per-language descriptions --}}
            @foreach ($languages as $code => $language)
                <div class="space-y-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700" wire:key="page-lang-{{ $code }}">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                        @if ($language->icon)
                            {!! gp247_image_render($language->icon, '20px', '20px', $language->name) !!}
                        @endif
                        {{ $language->name }}
                    </h3>

                    <x-gp247::input :label="gp247_language_render('admin.page.title_field')" name="title_{{ $code }}"
                        wire:model="descriptions.{{ $code }}.name"
                        :error="$errors->first('descriptions.'.$code.'.name')" required />

                    <x-gp247::input :label="gp247_language_render('admin.page.keyword')" name="keyword_{{ $code }}"
                        wire:model="descriptions.{{ $code }}.keyword"
                        :error="$errors->first('descriptions.'.$code.'.keyword')" />

                    <x-gp247::input :label="gp247_language_render('admin.page.description')" name="description_{{ $code }}"
                        wire:model="descriptions.{{ $code }}.description"
                        :error="$errors->first('descriptions.'.$code.'.description')" />

                    <x-gp247::rich-editor model="descriptions.{{ $code }}.content" type="page"
                        :label="gp247_language_render('admin.page.content')" />
                </div>
            @endforeach

            <x-gp247::media-input :label="gp247_language_render('admin.page.image')" name="image"
                wire:model="form.image" :value="$form['image'] ?? ''" type="page" />

            <x-gp247::input :label="gp247_language_render('admin.page.alias')" name="alias"
                wire:model="form.alias" :error="$errors->first('form.alias')" required />

            @if ($multiStore)
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ gp247_language_render('admin.core.store') }}</label>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($storeList as $storeId => $storeName)
                            <x-gp247::checkbox :label="$storeName" wire:model="stores" value="{{ $storeId }}" id="pm-store-{{ $storeId }}" />
                        @endforeach
                    </div>
                </div>
            @endif

            <x-gp247::checkbox :label="gp247_language_render('admin.core.active')" wire:model="form.status" value="1" />

            <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
                <x-gp247::button variant="secondary" href="{{ gp247_route_admin('admin_page.index') }}" wire:navigate>
                    {{ gp247_language_render($editingId ? 'admin.core.cancel' : 'admin.core.reset') }}
                </x-gp247::button>
                <x-gp247::button type="submit" wire:loading.attr="disabled">
                    <i class="fas fa-save"></i> {{ gp247_language_render($editingId ? 'admin.core.update' : 'admin.core.submit') }}
                </x-gp247::button>
            </div>
        </form>
    </x-gp247::card>

    {{-- Right: list --}}
    <x-gp247::card :title="gp247_language_render('admin.page.title')">
        <div class="mb-3">
            <input type="search" wire:model.live.debounce.300ms="keyword"
                placeholder="{{ gp247_clean(gp247_language_render('admin.page.search')) }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
        </div>

        <x-gp247::table :empty="$rows->isEmpty() ? gp247_language_render('admin.core.no_records') : null">
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.page.image') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.page.title_field') }}</th>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('alias')">
                        {!! gp247_language_render('admin.page.alias') !!} @if ($sortField === 'alias')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('status')">
                        {{ gp247_language_render('admin.core.status') }} @if ($sortField === 'status')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.core.action') }}</th>
                </tr>
            </x-slot:head>

            @foreach ($rows as $row)
                @php
                    $rowTitle = optional($row->descriptions->firstWhere('lang', gp247_get_locale()))->name
                        ?: optional($row->descriptions->first())->name;
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ (string) $row->id === (string) $editingId ? 'bg-blue-50 dark:bg-blue-900/30' : '' }}"
                    wire:key="page-{{ $row->id }}">
                    <td class="px-4 py-3">
                        @if ($row->image)
                            <img src="{{ gp247_image_get_path_thumb($row->image) }}" alt=""
                                class="h-10 w-auto rounded border border-gray-200 dark:border-gray-600">
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $rowTitle }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $row->alias }}</td>
                    <td class="px-4 py-3">
                        <x-gp247::badge :color="$row->status ? 'green' : 'gray'">{{ $row->status ? gp247_language_render('admin.core.active') : gp247_language_render('admin.core.inactive') }}</x-gp247::badge>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <x-gp247::button size="sm" variant="ghost" href="{{ gp247_route_admin('admin_page.edit', $row->id) }}" wire:navigate><i class="fas fa-edit"></i></x-gp247::button>
                            <x-gp247::button size="sm" variant="ghost" wire:click="delete({{ $row->id }})" wire:confirm="{{ gp247_language_render('action.delete_confirm') }}"><i class="fas fa-trash-alt text-red-600"></i></x-gp247::button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-gp247::table>

        <div class="mt-4">{{ $rows->links('gp247-admin::partials.pagination') }}</div>
    </x-gp247::card>
</div>
