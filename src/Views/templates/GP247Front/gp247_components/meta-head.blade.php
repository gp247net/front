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

    A template that overrides this view must print these component variables,
    all decided by MetaHead/SeoMeta (ADR-seo-indexing-signals, modification
    20261116T050835 — RISK-TECH-meta-head-view-override-drift):
      $robots (string|null) · $canonical (string) · $ogUrl (string) ·
      $hreflang (array locale => url) + $hreflangDefault (string|null) ·
      $verification (array: google|bing => token)
--}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="author" content="{{ config('app.name') }}">
@if(!empty($robots))
<meta name="robots" content="{{ $robots }}">
@endif
<link rel="canonical" href="{{ $canonical }}" />
@if(!empty($description))
<meta name="description" content="{{ $description }}">
@endif
@if(!empty($keyword))
<meta name="keywords" content="{{ $keyword }}">
@endif
<title>{{ $title ?? gp247_store_info('name') }}</title>
<link rel="icon" href="{{ gp247_file(gp247_store_info(key: 'icon', default: 'GP247/Core/logo/icon.png')) }}" type="image/png" sizes="16x16">
@if(!empty($verification['google']))
<meta name="google-site-verification" content="{{ $verification['google'] }}">
@endif
@if(!empty($verification['bing']))
<meta name="msvalidate.01" content="{{ $verification['bing'] }}">
@endif
@php
    // No bundled default image: page image, else the store's og_image, else no tag (US-SEO-001).
    $shareImage = !empty($og_image) ? $og_image : trim((string) gp247_store_info(key: 'og_image'));
@endphp
@if($shareImage !== '')
<meta property="og:image" content="{{ gp247_file($shareImage) }}" />
@endif
<meta property="og:url" content="{{ $ogUrl }}" />
<meta property="og:type" content="{{ $ogType ?? 'website' }}" />
<meta property="og:title" content="{{ $title ?? gp247_store_info('name') }}" />
@if(!empty($description))
<meta property="og:description" content="{{ $description }}" />
@endif
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- hreflang (caller's map, or built from the current route; none on a noindex page) --}}
@if(!empty($hreflang))
    @foreach($hreflang as $locale => $url)
        <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}" />
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $hreflangDefault }}" />
@endif

{{-- Twitter card --}}
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $title ?? gp247_store_info('name') }}" />
@if(!empty($description))
<meta name="twitter:description" content="{{ $description }}" />
@endif
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
