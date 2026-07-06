{{--
    Link create/edit form (front-admin Unit). UI text via gp247_language_render.
    A "collection" link is a folder (no url/target); a single link points to a
    url and may live under a collection. Multi-store assignment shown only when
    multistore/partner active.

    @aidlc-unit front-admin
    @aidlc-story US-FADM-003
    @aidlc-adr ADR-005, ADR-006, ADR-007

    Variables: $groups (FrontLinkGroup[]), $collections (FrontLink[] type=collection),
    $multiStore (bool), $storeList (array id=>name).
--}}
<div class="max-w-3xl">
    <x-gp247::card :title="gp247_language_render($editingId ? 'action.edit' : 'admin.link.add_new')">
        <form wire:submit="save" class="space-y-4">

            <x-gp247::input :label="gp247_language_render('admin.link.name')" name="name"
                wire:model="form.name" :error="$errors->first('form.name')" required />

            {{-- Type: single | collection (kept simple per ADR-007 P1) --}}
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
                    {{-- Target --}}
                    <div class="space-y-1">
                        <label for="target" class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ gp247_language_render('admin.link.target') }}</label>
                        <select id="target" wire:model="form.target"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option value="_self">_self</option>
                            <option value="_blank">_blank</option>
                        </select>
                    </div>

                    {{-- Collection (parent) --}}
                    <div class="space-y-1">
                        <label for="collection_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ gp247_language_render('admin.link.collection') }}</label>
                        <select id="collection_id" wire:model="form.collection_id"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option value="">—</option>
                            @foreach ($collections as $collection)
                                <option value="{{ $collection->id }}">{{ $collection->name }} (ID: {{ $collection->id }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Group --}}
                <div class="space-y-1">
                    <label for="group" class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ gp247_language_render('admin.link.group') }}</label>
                    <select id="group" wire:model="form.group"
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        <option value="">—</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->code }}">{{ $group->name }} (Code: {{ $group->code }})</option>
                        @endforeach
                    </select>
                    @error('form.group')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <x-gp247::input type="number" min="0" :label="gp247_language_render('admin.core.sort')"
                    name="sort" wire:model="form.sort" :error="$errors->first('form.sort')" required />
            </div>

            @if ($multiStore)
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ gp247_language_render('admin.core.store') }}</label>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($storeList as $storeId => $storeName)
                            <x-gp247::checkbox :label="$storeName" wire:model="stores" value="{{ $storeId }}" id="link-store-{{ $storeId }}" />
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Status --}}
            <x-gp247::checkbox :label="gp247_language_render('admin.core.active')" wire:model="form.status" value="1" />

            <div class="flex items-center justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
                <x-gp247::button variant="secondary" href="{{ gp247_route_admin('admin_link.index') }}" wire:navigate>
                    {{ gp247_language_render('admin.core.cancel') }}
                </x-gp247::button>
                <x-gp247::button type="submit" wire:loading.attr="disabled">
                    <i class="fas fa-save"></i> {{ gp247_language_render('admin.core.save') }}
                </x-gp247::button>
            </div>
        </form>
    </x-gp247::card>
</div>
