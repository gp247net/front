{{--
    Banner-type create/edit form (front-admin Unit). UI text via gp247_language_render.

    @aidlc-unit front-admin
    @aidlc-story US-FADM-001
    @aidlc-adr ADR-006, ADR-007
--}}
<div class="max-w-2xl">
    <x-gp247::card :title="gp247_language_render($editingId ? 'action.edit' : 'admin.banner_type.add_new')">
        <form wire:submit="save" class="space-y-4">
            <x-gp247::input :label="gp247_language_render('admin.banner_type.name')" name="name"
                wire:model="form.name" :error="$errors->first('form.name')" required />

            <x-gp247::input :label="gp247_language_render('admin.banner_type.code')" name="code"
                wire:model="form.code" :error="$errors->first('form.code')" required />

            <div class="flex items-center justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
                <x-gp247::button variant="secondary" href="{{ gp247_route_admin('admin_banner_type.index') }}" wire:navigate>
                    {{ gp247_language_render('admin.core.cancel') }}
                </x-gp247::button>
                <x-gp247::button type="submit" wire:loading.attr="disabled">
                    <i class="fas fa-save"></i> {{ gp247_language_render('admin.core.save') }}
                </x-gp247::button>
            </div>
        </form>
    </x-gp247::card>
</div>
