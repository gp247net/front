{{--
    JSON-LD — Product schema.

    Expects variables passed via @push('jsonld') from the product screen view:
      $jsonldProduct (array): {
          name, url, price, currency, availability, imageUrl, description
      }

    @aidlc-unit seo
    @aidlc-story US-SEO-005
--}}
@if(!empty($jsonldProduct))
@php
    $productJsonLd = \GP247\Front\Library\SeoMeta::buildProductJsonLd(
        name:         $jsonldProduct['name']         ?? '',
        url:          $jsonldProduct['url']          ?? request()->url(),
        price:        $jsonldProduct['price']        ?? '0',
        currency:     $jsonldProduct['currency']     ?? 'USD',
        availability: $jsonldProduct['availability'] ?? 'InStock',
        imageUrl:     $jsonldProduct['imageUrl']     ?? null,
        description:  $jsonldProduct['description']  ?? null,
    );
@endphp
<script type="application/ld+json">{!! $productJsonLd !!}</script>
@endif
