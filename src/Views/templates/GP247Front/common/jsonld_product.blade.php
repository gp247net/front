{{--
    JSON-LD — Product schema.

    Expects variables passed via @push('jsonld') from the product screen view:
      $jsonldProduct (array): {
          name, url, price, currency, availability, imageUrl, description,
          // optional (modification 20261116T050835):
          sku, brand, gtin12, gtin13, mpn, priceValidUntil (Y-m-d),
          productId — used to ask rating resolvers for aggregateRating
      }

    @aidlc-unit seo
    @aidlc-story US-SEO-005
--}}
@if(!empty($jsonldProduct))
@php
    $productJsonLd = \GP247\Front\Library\SeoMeta::buildProductJsonLd(
        name:            $jsonldProduct['name']         ?? '',
        url:             $jsonldProduct['url']          ?? request()->url(),
        price:           $jsonldProduct['price']        ?? '0',
        currency:        $jsonldProduct['currency']     ?? 'USD',
        availability:    $jsonldProduct['availability'] ?? 'InStock',
        imageUrl:        $jsonldProduct['imageUrl']     ?? null,
        description:     $jsonldProduct['description']  ?? null,
        sku:             isset($jsonldProduct['sku'])    ? (string) $jsonldProduct['sku'] : null,
        brand:           isset($jsonldProduct['brand'])  ? (string) $jsonldProduct['brand'] : null,
        gtin12:          isset($jsonldProduct['gtin12']) ? (string) $jsonldProduct['gtin12'] : null,
        gtin13:          isset($jsonldProduct['gtin13']) ? (string) $jsonldProduct['gtin13'] : null,
        mpn:             isset($jsonldProduct['mpn'])    ? (string) $jsonldProduct['mpn'] : null,
        priceValidUntil: $jsonldProduct['priceValidUntil'] ?? null,
        rating:          \GP247\Front\Library\SeoMeta::productRating((string) ($jsonldProduct['productId'] ?? '')),
    );
@endphp
<script type="application/ld+json">{!! $productJsonLd !!}</script>
@endif
