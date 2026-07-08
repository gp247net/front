{{--
    Page (CMS) create/edit form (front-admin Unit). UI text via gp247_language_render.
    Image via <x-gp247::media-input> (LFM); per-language content via
    <x-gp247::rich-editor> (TinyMCE). Multi-store assignment shown only when
    multistore/partner active. Languages rendered as stacked per-language sections
    (ADR-007 P1: layout-resemblance is enough).

    @aidlc-unit front-admin
    @aidlc-story US-FADM-002
    @aidlc-adr ADR-006, ADR-007

    Variables: $languages (AdminLanguage[] keyed by code), $multiStore (bool),
    $storeList (array id=>name).
--}}
<div class="max-w-3xl">
    <x-gp247::card :title="gp247_language_render($editingId ? 'action.edit' : 'admin.page.add_new')">
        <form wire:submit="save" class="space-y-4">

            {{-- Per-language descriptions --}}
            @foreach ($languages as $code => $language)
                <div class="space-y-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700" wire:key="page-lang-{{ $code }}">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                        @if ($language->icon)
                            {!! gp247_image_render($language->icon, '20px', '20px', $language->name) !!}
                        @endif
                        {{ $language->name }}
                    </h3>

                    <x-gp247::input :label="gp247_language_render('admin.page.title_field')" name="title_{{ $code }}"
                        wire:model="descriptions.{{ $code }}.name" :error="$errors->first('descriptions.'.$code.'.name')" required />

                    <x-gp247::input :label="gp247_language_render('admin.page.keyword')" name="keyword_{{ $code }}"
                        wire:model="descriptions.{{ $code }}.keyword" :error="$errors->first('descriptions.'.$code.'.keyword')" />

                    <x-gp247::input :label="gp247_language_render('admin.page.description')" name="description_{{ $code }}"
                        wire:model="descriptions.{{ $code }}.description" :error="$errors->first('descriptions.'.$code.'.description')" />

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
                            <x-gp247::checkbox :label="$storeName" wire:model="stores" value="{{ $storeId }}" id="pf-store-{{ $storeId }}" />
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Status --}}
            <x-gp247::checkbox :label="gp247_language_render('admin.core.active')" wire:model="form.status" value="1" />

            <div class="flex items-center justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
                <x-gp247::button variant="secondary" href="{{ gp247_route_admin('admin_page.index') }}" wire:navigate>
                    {{ gp247_language_render('admin.core.cancel') }}
                </x-gp247::button>
                <x-gp247::button type="submit" wire:loading.attr="disabled" wire:target="save">
                    <i class="fas fa-save"></i> {{ gp247_language_render('admin.core.save') }}
                </x-gp247::button>
            </div>
        </form>
    </x-gp247::card>
</div>
