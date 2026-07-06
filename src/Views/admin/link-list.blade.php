{{--
    Link list (front-admin Unit). UI text via gp247_language_render.

    @aidlc-unit front-admin
    @aidlc-story US-FADM-003
    @aidlc-adr ADR-006, ADR-007

    Variables: $rows (FrontLink paginator).
--}}
<div>
    <x-gp247::list-toolbar :placeholder="gp247_language_render('admin.link.title')"
        :selected-count="count($selected)" :bulk-confirm="gp247_language_render('action.delete_confirm')">
        <x-slot:actions>
            <x-gp247::button href="{{ gp247_route_admin('admin_link_group.index') }}" variant="secondary" wire:navigate size="sm">
                <i class="fas fa-indent"></i> {{ gp247_language_render('admin.link_group.title') }}
            </x-gp247::button>
            <x-gp247::button href="{{ gp247_route_admin('admin_link.create') }}" wire:navigate size="sm">
                <i class="fas fa-plus"></i> {{ gp247_language_render('admin.link.add_new') }}
            </x-gp247::button>
        </x-slot:actions>
    </x-gp247::list-toolbar>

    <x-gp247::table :empty="$rows->isEmpty() ? gp247_language_render('admin.core.no_records') : null">
        <x-slot:head>
            <tr>
                <th class="w-10 px-4 py-3"></th>
                <x-gp247::th-sort field="name" :sort-field="$sortField" :sort-dir="$sortDir">{{ gp247_language_render('admin.link.name') }}</x-gp247::th-sort>
                <x-gp247::th-sort field="group" :sort-field="$sortField" :sort-dir="$sortDir">{{ gp247_language_render('admin.link.group') }}</x-gp247::th-sort>
                <x-gp247::th-sort field="sort" :sort-field="$sortField" :sort-dir="$sortDir">{{ gp247_language_render('admin.core.sort') }}</x-gp247::th-sort>
                <x-gp247::th-sort field="status" :sort-field="$sortField" :sort-dir="$sortDir">{{ gp247_language_render('admin.core.status') }}</x-gp247::th-sort>
                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.core.actions') }}</th>
            </tr>
        </x-slot:head>

        @foreach ($rows as $row)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50" wire:key="link-{{ $row->id }}">
                <td class="px-4 py-3"><x-gp247::select-check :value="$row->id" /></td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                    @if ($row->type === 'collection')
                        <span class="mr-1 text-yellow-500"><i class="fas fa-folder-open"></i></span>
                    @endif
                    {{ $row->name }}
                    @if ($row->url && $row->type !== 'collection')
                        <a href="{{ $row->url }}" target="_blank" class="ml-1 text-xs text-blue-500"><i class="fas fa-external-link-alt"></i></a>
                    @endif
                </td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $row->group }}</td>
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->sort }}</td>
                <td class="px-4 py-3">
                    <x-gp247::badge :color="$row->status ? 'green' : 'gray'">{{ $row->status ? gp247_language_render('admin.core.active') : gp247_language_render('admin.core.inactive') }}</x-gp247::badge>
                </td>
                <td class="px-4 py-3">
                    <x-gp247::row-actions
                        :edit="gp247_route_admin('admin_link.edit', ['id' => $row->id])"
                        :delete-id="$row->id"
                        :delete-confirm="gp247_language_render('action.delete_confirm')" />
                </td>
            </tr>
        @endforeach
    </x-gp247::table>

    <div class="mt-4">{{ $rows->links('gp247-admin::partials.pagination') }}</div>
</div>
