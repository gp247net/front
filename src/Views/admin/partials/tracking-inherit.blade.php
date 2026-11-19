{{--
    Inheritance line under a tracking field at a sub-store: "using the root store value",
    or a link that drops the store's own value.

    @aidlc-unit tracking-consent
    @aidlc-story US-tracking-consent-settings-screen

    Variables: $key (string) form key; $inherited (array) from the component.
--}}
@if ($inherited[$key] ?? false)
    <span class="mt-1 inline-block" data-testid="tracking-settings-inherited-{{ $key }}">
        <x-gp247::badge color="blue">{{ gp247_language_render('admin.tracking.inherited') }}</x-gp247::badge>
    </span>
@else
    <button type="button" wire:click="resetToRoot('{{ $key }}')" class="mt-1 text-xs text-blue-600 hover:underline dark:text-blue-400" data-testid="tracking-settings-reset-{{ $key }}">
        {{ gp247_language_render('admin.tracking.use_root') }}
    </button>
@endif
