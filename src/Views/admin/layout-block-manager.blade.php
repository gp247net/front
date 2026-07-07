{{--
    Layout block manager — two-panel: form (left) + list (right) on the
    ResourcePanel base (ADR-005, ADR-007, ui-tailadmin P1). UI text via
    gp247_language_render.

    @aidlc-unit front-admin
    @aidlc-story US-FADM-004
    @aidlc-adr ADR-001, ADR-005, ADR-006, ADR-007

    Variables: $rows (FrontLayoutBlock paginator), $types, $positionOptions,
               $viewBlocks, $pageOptions, $pageViewOptions, $editingId, $form,
               $sortField, $sortDir, $keyword.
--}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    {{-- Left: add / edit form --}}
    <x-gp247::card :title="gp247_language_render($editingId ? 'action.edit' : 'admin.layout_block.add_new')">
        <form wire:submit="save" class="space-y-4">

            <x-gp247::input :label="gp247_language_render('admin.layout_block.name')" name="name"
                wire:model="form.name" :error="$errors->first('form.name')" required />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Type --}}
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ gp247_language_render('admin.layout_block.type') }}<span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        @foreach ($types as $code => $label)
                            <button type="button" wire:click="selectType('{{ $code }}')"
                                @class([
                                    'flex-1 rounded-lg border px-3 py-2 text-sm font-medium transition-colors',
                                    'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' => $form['type'] === $code,
                                    'border-gray-300 bg-white text-gray-700 hover:border-blue-400 hover:bg-blue-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200' => $form['type'] !== $code,
                                ])>
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    @error('form.type')<p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <x-gp247::input type="number" min="0" :label="gp247_language_render('admin.core.sort')"
                    name="sort" wire:model="form.sort" :error="$errors->first('form.sort')" required />
            </div>

            {{-- Position with layout-guide popup --}}
            <div x-data="{ show: false }">
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{ gp247_language_render('admin.layout_block.position') }}<span class="text-red-500">*</span>
                    <button type="button" @click="show = true"
                            class="text-gray-400 hover:text-blue-500" title="Xem vị trí bố cục">
                        <i class="fa fa-question-circle"></i>
                    </button>
                </label>
                <x-gp247::searchable-select
                    model="form.position"
                    :options="$positionOptions"
                    :error="$errors->first('form.position')" />
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
            </div>

            {{-- Page scope multi-select --}}
            <x-gp247::searchable-select
                model="form.page"
                :label="gp247_language_render('admin.layout_block.page')"
                :options="$pageOptions"
                :error="$errors->first('form.page')"
                multiple
                required />

            {{-- Content field: switches per type --}}
            @if ($form['type'] === 'view')
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ gp247_language_render('admin.layout_block.text') }}<span class="text-red-500">*</span>
                    </label>
                    @if ($viewBlocks)
                        <div class="flex flex-wrap gap-2">
                            @foreach ($viewBlocks as $key => $label)
                                <button type="button" wire:click="$set('form.text', '{{ $key }}')"
                                    @class([
                                        'rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors',
                                        'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' => $form['text'] === $key,
                                        'border-gray-300 bg-white text-gray-700 hover:border-blue-400 hover:bg-blue-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200' => $form['text'] !== $key,
                                    ])>
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500">{{ gp247_language_render('admin.layout_block.text_help') }}</p>
                    @endif
                    @error('form.text')<p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
            @elseif ($form['type'] === 'page')
                <x-gp247::searchable-select
                    model="form.text"
                    :label="gp247_language_render('admin.layout_block.text')"
                    :options="$pageViewOptions"
                    :error="$errors->first('form.text')"
                    required />
            @else
                <x-gp247::rich-editor model="form.text" :label="gp247_language_render('admin.layout_block.text')"
                    :error="$errors->first('form.text')"
                    :help="gp247_language_render('admin.layout_block.text_help')" required />
            @endif

            <x-gp247::checkbox :label="gp247_language_render('admin.core.active')" wire:model="form.status" value="1" />

            <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
                <x-gp247::button variant="secondary" href="{{ gp247_route_admin('admin_layout_block.index') }}" wire:navigate>
                    {{ gp247_language_render($editingId ? 'admin.core.cancel' : 'admin.core.reset') }}
                </x-gp247::button>
                <x-gp247::button type="submit" wire:loading.attr="disabled">
                    <i class="fas fa-save"></i> {{ gp247_language_render($editingId ? 'admin.core.update' : 'admin.core.submit') }}
                </x-gp247::button>
            </div>
        </form>
    </x-gp247::card>

    {{-- Right: list --}}
    <x-gp247::card :title="gp247_language_render('admin.layout_block.title')">
        <div class="mb-3">
            <input type="search" wire:model.live.debounce.300ms="keyword"
                placeholder="{{ gp247_language_render('admin.layout_block.name') }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
        </div>

        <x-gp247::table :empty="$rows->isEmpty() ? gp247_language_render('admin.core.no_records') : null">
            <x-slot:head>
                <tr>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('name')">
                        {{ gp247_language_render('admin.layout_block.name') }} @if ($sortField === 'name')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('type')">
                        {{ gp247_language_render('admin.layout_block.type') }} @if ($sortField === 'type')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('position')">
                        {{ gp247_language_render('admin.layout_block.position') }} @if ($sortField === 'position')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('status')">
                        {{ gp247_language_render('admin.core.status') }} @if ($sortField === 'status')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.core.action') }}</th>
                </tr>
            </x-slot:head>

            @foreach ($rows as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ (string) $row->id === (string) $editingId ? 'bg-blue-50 dark:bg-blue-900/30' : '' }}"
                    wire:key="layout-block-{{ $row->id }}">
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $row->name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $row->type }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $row->position }}</td>
                    <td class="px-4 py-3">
                        <x-gp247::badge :color="$row->status ? 'green' : 'gray'">{{ $row->status ? gp247_language_render('admin.core.active') : gp247_language_render('admin.core.inactive') }}</x-gp247::badge>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <x-gp247::button size="sm" variant="ghost" href="{{ gp247_route_admin('admin_layout_block.edit', $row->id) }}" wire:navigate><i class="fas fa-edit"></i></x-gp247::button>
                            <x-gp247::button size="sm" variant="ghost" wire:click="delete('{{ $row->id }}')" wire:confirm="{{ gp247_language_render('action.delete_confirm') }}"><i class="fas fa-trash-alt text-red-600"></i></x-gp247::button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-gp247::table>

        <div class="mt-4">{{ $rows->links('gp247-admin::partials.pagination') }}</div>
    </x-gp247::card>
</div>
