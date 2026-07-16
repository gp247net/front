{{--
    Layout block list (front-admin Unit). UI text via gp247_language_render.

    @aidlc-unit front-admin
    @aidlc-story US-FADM-004
    @aidlc-adr ADR-006, ADR-007

    Variables: $rows (FrontLayoutBlock paginator).
--}}
<div>
    <x-gp247::list-toolbar :placeholder="gp247_language_render('admin.layout_block.title')"
        :selected-count="count($selected)" :bulk-confirm="gp247_language_render('action.delete_confirm')">
        <x-slot:actions>
            <x-gp247::button href="{{ gp247_route_admin('admin_layout_block.create') }}" wire:navigate size="sm">
                <i class="fas fa-plus"></i> {{ gp247_language_render('admin.layout_block.add_new') }}
            </x-gp247::button>
        </x-slot:actions>
    </x-gp247::list-toolbar>

    <x-gp247::table :empty="$rows->isEmpty() ? gp247_language_render('admin.no_records') : null">
        <x-slot:head>
            <tr>
                <th class="w-10 px-4 py-3"></th>
                <x-gp247::th-sort field="name" :sort-field="$sortField" :sort-dir="$sortDir">{{ gp247_language_render('admin.layout_block.name') }}</x-gp247::th-sort>
                <x-gp247::th-sort field="type" :sort-field="$sortField" :sort-dir="$sortDir">{{ gp247_language_render('admin.layout_block.type') }}</x-gp247::th-sort>
                <x-gp247::th-sort field="position" :sort-field="$sortField" :sort-dir="$sortDir">
                    <span class="inline-flex items-center gap-1">
                        {{ gp247_language_render('admin.layout_block.position') }}
                        <span x-data="{ show: false }">
                            <button type="button" @click.stop="show = true"
                                    class="text-gray-400 hover:text-blue-500">
                                <i class="fa fa-question-circle text-xs"></i>
                            </button>
                            <template x-teleport="body">
                                <div x-show="show" x-cloak @click="show = false"
                                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
                                    <div @click.stop class="relative">
                                        <img src="https://static.gp247.net/page/block-template.jpg"
                                             alt="Layout position guide"
                                             class="max-h-[80vh] max-w-full rounded-lg shadow-2xl">
                                        <button type="button" @click="show = false"
                                                class="absolute -right-3 -top-3 flex h-7 w-7 items-center justify-center rounded-full bg-white font-bold text-gray-700 shadow hover:bg-gray-100">
                                            ×
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </span>
                    </span>
                </x-gp247::th-sort>
                <x-gp247::th-sort field="sort" :sort-field="$sortField" :sort-dir="$sortDir">{{ gp247_language_render('admin.sort') }}</x-gp247::th-sort>
                <x-gp247::th-sort field="status" :sort-field="$sortField" :sort-dir="$sortDir">{{ gp247_language_render('admin.status') }}</x-gp247::th-sort>
                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.actions') }}</th>
            </tr>
        </x-slot:head>

        @foreach ($rows as $row)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50" wire:key="layout-block-{{ $row->id }}">
                <td class="px-4 py-3"><x-gp247::select-check :value="$row->id" /></td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $row->name }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $row->type }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $row->position }}</td>
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->sort }}</td>
                <td class="px-4 py-3">
                    <x-gp247::badge :color="$row->status ? 'green' : 'gray'">{{ $row->status ? gp247_language_render('admin.active') : gp247_language_render('admin.inactive') }}</x-gp247::badge>
                </td>
                <td class="px-4 py-3">
                    <x-gp247::row-actions
                        :edit="gp247_route_admin('admin_layout_block.edit', ['id' => $row->id])"
                        :delete-id="$row->id"
                        :delete-confirm="gp247_language_render('action.delete_confirm')" />
                </td>
            </tr>
        @endforeach
    </x-gp247::table>

    <div class="mt-4">{{ $rows->links('gp247-admin::partials.pagination') }}</div>
</div>
