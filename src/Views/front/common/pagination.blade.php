{{--
    Pagination — Tailwind port of Default/common/pagination.blade.php. Uses
    Laravel's own default paginator view (Tailwind-styled out of the box;
    this project does not call Paginator::useBootstrap()), so no extra
    markup wrapper is needed here unlike Default's Bootstrap `.pagination`
    list.

    @aidlc-unit frontend-template-dev
    @aidlc-story US-TPL-009
    @aidlc-adr ADR-014
--}}
<div class="mt-8">
    {{ $items->appends(request()->except(['page', '_token']))->links() }}
</div>
