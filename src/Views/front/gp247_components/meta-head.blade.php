{{--
    Shared <head> block (US-TPL-008/ADR-013 + US-SEO-001/002/003/005/ADR-014).
    Default package view for GP247\Front\TemplateComponents\MetaHead —
    overridable per template via
    app/GP247/Templates/<Template>/gp247_components/meta-head.blade.php, same as
    Breadcrumb/Notice. Public properties on MetaHead are auto-bound to this
    view by Illuminate\View\Component (no @props needed).

    Single source for the entire <head> SEO surface (modification
    20260702T190000) — folds in what used to be two separate includes
    (seo_head.blade.php, jsonld_organization.blade.php) that a later,
    unrelated refactor silently dropped from layout.blade.php (RISK-TECH-012).
--}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="author" content="{{ config('app.name') }}">
<link rel="canonical" href="{{ $canonical ?? \GP247\Front\Library\SeoMeta::canonicalUrl() }}" />
<meta name="description" content="{{ $description ?? gp247_store_info('description') }}">
<meta name="keywords" content="{{ $keyword ?? gp247_store_info('keyword') }}">
<title>{{ $title ?? gp247_store_info('name') }}</title>
<link rel="icon" href="{{ gp247_file(gp247_store_info(key: 'icon', default: 'GP247/Core/logo/icon.png')) }}" type="image/png" sizes="16x16">
<meta property="og:image" content="{{ !empty($og_image) ? gp247_file($og_image) : gp247_file(gp247_store_info(key: 'og_image', default: 'GP247/Core/images/org.jpg')) }}" />
<meta property="og:url" content="{{ \Request::fullUrl() }}" />
<meta property="og:type" content="{{ $ogType ?? 'website' }}" />
<meta property="og:title" content="{{ $title ?? gp247_store_info('name') }}" />
<meta property="og:description" content="{{ $description ?? gp247_store_info('description') }}" />
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- hreflang (only rendered when a caller passed locale => URL entries) --}}
@if(!empty($hreflang))
    @foreach($hreflang as $locale => $url)
        <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}" />
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ reset($hreflang) }}" />
@endif

{{-- Twitter card --}}
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $title ?? gp247_store_info('name') }}" />
<meta name="twitter:description" content="{{ $description ?? gp247_store_info('description') }}" />
@if(!empty($og_image))
    <meta name="twitter:image" content="{{ gp247_file($og_image) }}" />
@endif

{{-- JSON-LD: Organization (site-wide, no per-page data needed) — gated by seo.jsonld_enabled (modification 20260711T143819) --}}
@if(\GP247\Front\Library\SeoMeta::jsonldEnabled())
@php
    $gp247OrgJsonLd = \GP247\Front\Library\SeoMeta::buildOrganizationJsonLd(
        name:    gp247_store_info('name') ?: config('app.name'),
        url:     url('/'),
        logoUrl: !empty(gp247_store_info('logo')) ? gp247_file(gp247_store_info('logo')) : null,
    );
@endphp
<script type="application/ld+json">{!! $gp247OrgJsonLd !!}</script>
@endif
