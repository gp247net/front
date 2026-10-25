{{--
    Contact messages (front-contact). Visitor text is always escaped; line breaks
    kept with nl2br(e()). UI text via gp247_language_render.

    @aidlc-unit front-contact
    @aidlc-story US-front-contact-inbox
    @aidlc-adr front-contact_contact-form-in-front

    Variables: $rows (FrontContact paginator), $viewing (FrontContact|null), $statuses (ContactStatus[]).
--}}
@php
    $badgeColor = [0 => 'blue', 1 => 'gray', 2 => 'green'];
@endphp
<div>
    @if ($viewing)
        <x-gp247::card class="mb-4" data-testid="front-contact-inbox-detail">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ $viewing->title }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $viewing->name }} · {{ $viewing->email }}@if ($viewing->phone) · {{ $viewing->phone }}@endif
                        · {{ $viewing->created_at }}
                    </p>
                </div>
                <x-gp247::badge :color="$badgeColor[$viewing->status] ?? 'gray'">{{ gp247_language_render($viewing->statusEnum()->labelKey()) }}</x-gp247::badge>
            </div>
            <div class="mt-4 whitespace-normal text-sm text-gray-700 dark:text-gray-200" data-testid="front-contact-inbox-content">{!! nl2br(e($viewing->content)) !!}</div>
            <div class="mt-4 flex flex-wrap gap-2">
                <x-gp247::button size="sm" href="mailto:{{ rawurlencode($viewing->email) }}?subject={{ rawurlencode('Re: '.$viewing->title) }}" data-testid="front-contact-inbox-reply">
                    <i class="fas fa-reply"></i> {{ gp247_language_render('admin.contact.reply') }}
                </x-gp247::button>
                @if ($viewing->status === \GP247\Front\Models\FrontContact::STATUS_HANDLED)
                    <x-gp247::button size="sm" variant="secondary" wire:click="markNew('{{ $viewing->id }}')" data-testid="front-contact-inbox-mark-new">
                        {{ gp247_language_render('admin.contact.mark_new') }}
                    </x-gp247::button>
                @else
                    <x-gp247::button size="sm" variant="success" wire:click="markHandled('{{ $viewing->id }}')" data-testid="front-contact-inbox-mark-handled">
                        <i class="fas fa-check"></i> {{ gp247_language_render('admin.contact.mark_handled') }}
                    </x-gp247::button>
                @endif
                <x-gp247::button size="sm" variant="ghost" wire:click="closeView" data-testid="front-contact-inbox-close">
                    {{ gp247_language_render('admin.contact.close') }}
                </x-gp247::button>
            </div>
        </x-gp247::card>
    @endif

    <x-gp247::list-toolbar :placeholder="gp247_language_render('admin.contact.search')"
        :selected-count="count($selected)" :bulk-confirm="gp247_language_render('action.delete_confirm')">
        <x-slot:filters>
            <select wire:model.live="statusFilter" data-testid="front-contact-inbox-status-filter"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                <option value="">{{ gp247_language_render('admin.contact.filter_all') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ gp247_language_render($status->labelKey()) }}</option>
                @endforeach
            </select>
        </x-slot:filters>
    </x-gp247::list-toolbar>

    <x-gp247::table :empty="$rows->isEmpty() ? gp247_language_render('admin.no_records') : null">
        <x-slot:head>
            <tr>
                <th class="w-10 px-4 py-3"></th>
                <x-gp247::th-sort field="created_at" :sort-field="$sortField" :sort-dir="$sortDir">{{ gp247_language_render('admin.contact.col_date') }}</x-gp247::th-sort>
                <x-gp247::th-sort field="name" :sort-field="$sortField" :sort-dir="$sortDir">{{ gp247_language_render('admin.contact.col_sender') }}</x-gp247::th-sort>
                <x-gp247::th-sort field="title" :sort-field="$sortField" :sort-dir="$sortDir">{{ gp247_language_render('contact.subject') }}</x-gp247::th-sort>
                <x-gp247::th-sort field="status" :sort-field="$sortField" :sort-dir="$sortDir">{{ gp247_language_render('admin.contact.col_status') }}</x-gp247::th-sort>
                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('admin.actions') }}</th>
            </tr>
        </x-slot:head>

        @foreach ($rows as $row)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $row->status === 0 ? 'font-semibold' : '' }}" wire:key="contact-{{ $row->id }}" data-testid="front-contact-inbox-item">
                <td class="px-4 py-3"><x-gp247::select-check :value="$row->id" /></td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $row->created_at }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                    {{ $row->name }}
                    <div class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ $row->email }}</div>
                    @include('gp247-admin::partials.store-scope-line', ['storeId' => $row->store_id])
                </td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $row->title }}</td>
                <td class="px-4 py-3">
                    <x-gp247::badge :color="$badgeColor[$row->status] ?? 'gray'">{{ gp247_language_render($row->statusEnum()->labelKey()) }}</x-gp247::badge>
                </td>
                <td class="px-4 py-3">
                    <x-gp247::row-actions :delete-id="$row->id" :delete-confirm="gp247_language_render('action.delete_confirm')">
                        <x-gp247::button size="sm" variant="ghost" wire:click="open('{{ $row->id }}')" title="{{ gp247_language_render('admin.contact.view') }}" data-testid="front-contact-inbox-open">
                            <i class="fas fa-eye"></i>
                        </x-gp247::button>
                    </x-gp247::row-actions>
                </td>
            </tr>
        @endforeach
    </x-gp247::table>

    <div class="mt-4">{{ $rows->links('gp247-admin::partials.pagination') }}</div>
</div>
