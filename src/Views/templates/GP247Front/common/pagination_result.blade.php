{{--
    Result-count text ("Showing X-Y of Z") — Tailwind port of
    Default/common/pagination_result.blade.php. Called by ProductFilter's
    view (livewire/shop_product-filter.blade.php) via gp247_shop_process_view().

    @aidlc-unit frontend-template-dev
    @aidlc-story US-TPL-009
    @aidlc-adr ADR-014
--}}
<p class="text-sm text-ink-400 mb-4">
    {!! gp247_language_render('front.result_item', ['item_from' => $items->firstItem(), 'item_to' => $items->lastItem(), 'total' => $items->total()]) !!}
</p>
