{{--
    Banner create/edit form (front-admin Unit). UI text via gp247_language_render.
    Image via <x-gp247::media-input> (LFM), html via <x-gp247::rich-editor> (TinyMCE),
    expiry-free. Multi-store assignment shown only when multistore/partner active.

    @aidlc-unit front-admin
    @aidlc-story US-FADM-001
    @aidlc-adr ADR-005, ADR-006, ADR-007

    Variables: $types (FrontBannerType[]), $multiStore (bool), $storeList (array id=>name).
--}}
<div class="max-w-3xl">
    <x-gp247::card :title="gp247_language_render($editingId ? 'action.edit' : 'admin.banner.add_new')">
        <form wire:submit="save" class="space-y-4">

            <x-gp247::input :label="gp247_language_render('admin.banner.title')" name="title"
                wire:model="form.name" :error="$errors->first('form.name')" required />

            <x-gp247::media-input :label="gp247_language_render('admin.banner.image')" name="image"
                wire:model="form.image" :value="$form['image'] ?? ''" type="banner" />

            <x-gp247::input :label="gp247_language_render('admin.banner.url')" name="url"
                wire:model="form.url" :error="$errors->first('form.url')" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                {{-- Type --}}
                <div class="space-y-1">
                    <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ gp247_language_render('admin.banner.type') }}</label>
                    <select id="type" wire:model="form.type"
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        <option value="">—</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->code }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Target --}}
                <div class="space-y-1">
                    <label for="target" class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ gp247_language_render('admin.banner.target') }}</label>
                    <select id="target" wire:model="form.target"
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        <option value="_self">_self</option>
                        <option value="_blank">_blank</option>
                    </select>
                </div>

                <x-gp247::input type="number" min="0" :label="gp247_language_render('admin.core.sort')"
                    name="sort" wire:model="form.sort" :error="$errors->first('form.sort')" required />
            </div>

            {{-- HTML content (rich text) --}}
            <x-gp247::rich-editor model="form.html" type="banner" :label="gp247_language_render('admin.banner.html')" />

            @if ($multiStore)
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ gp247_language_render('admin.core.store') }}</label>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($storeList as $storeId => $storeName)
                            <x-gp247::checkbox :label="$storeName" wire:model="stores" value="{{ $storeId }}" id="banner-store-{{ $storeId }}" />
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Status --}}
            <x-gp247::checkbox :label="gp247_language_render('admin.core.active')" wire:model="form.status" value="1" />

            <div class="flex items-center justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
                <x-gp247::button variant="secondary" href="{{ gp247_route_admin('admin_banner.index') }}" wire:navigate>
                    {{ gp247_language_render('admin.core.cancel') }}
                </x-gp247::button>
                <x-gp247::button type="submit" wire:loading.attr="disabled" wire:target="save">
                    <i class="fas fa-save"></i> {{ gp247_language_render('admin.core.save') }}
                </x-gp247::button>
            </div>
        </form>
    </x-gp247::card>
</div>
