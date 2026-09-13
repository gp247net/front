{{--
    JSON-LD — Article schema.

    Expects variables passed via @push('jsonld') from the content screen view:
      $jsonldArticle (array): {
          headline, url, imageUrl?, datePublished?, dateModified?, description
      }

    @aidlc-unit seo
    @aidlc-story US-SEO-005
--}}
@if(!empty($jsonldArticle))
@php
    $articleJsonLd = \GP247\Front\Library\SeoMeta::buildArticleJsonLd(
        headline:      $jsonldArticle['headline']      ?? '',
        url:           $jsonldArticle['url']           ?? request()->url(),
        imageUrl:      $jsonldArticle['imageUrl']      ?? null,
        datePublished: $jsonldArticle['datePublished'] ?? null,
        dateModified:  $jsonldArticle['dateModified']  ?? null,
        description:   $jsonldArticle['description']   ?? null,
    );
@endphp
<script type="application/ld+json">{!! $articleJsonLd !!}</script>
@endif
