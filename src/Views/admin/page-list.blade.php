{{--
    Page (CMS) list (front-admin Unit). UI text via gp247_language_render.

    @aidlc-unit front-admin
    @aidlc-story US-FADM-002
    @aidlc-adr ADR-006, ADR-007

    Variables: $rows (FrontPage paginator, descriptions eager-loaded).
--}}
<div>
    <x-gp247::list-toolbar :placeholder="gp247_language_render('admin.page.title')"
        :selected-count="count($selected)" :bulk-confirm="gp247_language_render('action.delete_confirm')">
        <x-slot:actions>
            <x-gp247::button href="{{ gp247_route_admin('admin_page.create') }}" wire:navigate size="sm">
                <i class="fas fa-plus"></i> {{ gp247_language_render('admin.page.add_new') }}
            </x-gp247::button>
        </x-slot:actions>
    </x-gp247::list-toolbar>

    <x-gp247::table :empty="$rows->isEmpty() ? gp247_language_render('admin.core.no_records') : null">
        <x-slot:head>
            <tr>
                <th class="w-10 px-4 py-3"></th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.page.image') }}</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.page.title_field') }}</th>
                <x-gp247::th-sort field="alias" :sort-field="$sortField" :sort-dir="$sortDir">{{ gp247_language_render('admin.page.alias') }}</x-gp247::th-sort>
                <x-gp247::th-sort field="status" :sort-field="$sortField" :sort-dir="$sortDir">{{ gp247_language_render('admin.core.status') }}</x-gp247::th-sort>
                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.core.actions') }}</th>
            </tr>
        </x-slot:head>

        @foreach ($rows as $row)
            @php
                $rowTitle = optional($row->descriptions->firstWhere('lang', gp247_get_locale()))->name
                    ?: optional($row->descriptions->first())->name;
            @endphp
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50" wire:key="page-{{ $row->id }}">
                <td class="px-4 py-3"><x-gp247::select-check :value="$row->id" /></td>
                <td class="px-4 py-3">
                    @if ($row->image)
                        <img src="{{ gp247_image_get_path_thumb($row->image) }}" alt="" class="h-10 w-auto rounded border border-gray-200 dark:border-gray-600">
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
                    <x-gp247::row-actions
                        :edit="gp247_route_admin('admin_page.edit', ['id' => $row->id])"
                        :delete-id="$row->id"
                        :delete-confirm="gp247_language_render('action.delete_confirm')" />
                </td>
            </tr>
        @endforeach
    </x-gp247::table>

    <div class="mt-4">{{ $rows->links('gp247-admin::partials.pagination') }}</div>
</div>
