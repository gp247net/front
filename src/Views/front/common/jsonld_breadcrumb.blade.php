{{--
    JSON-LD — BreadcrumbList schema.

    Expects $breadcrumbItems: array of ['name' => string, 'url' => string|null]
    Pass via @push('jsonld') from individual screen views.

    @aidlc-unit seo
    @aidlc-story US-SEO-005
--}}
@if(!empty($breadcrumbItems))
@php
    $breadcrumbJsonLd = \GP247\Front\Library\SeoMeta::buildBreadcrumbJsonLd($breadcrumbItems);
@endphp
@if($breadcrumbJsonLd)
<script type="application/ld+json">{!! $breadcrumbJsonLd !!}</script>
@endif
@endif
