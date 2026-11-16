{{--
    JSON-LD — Article schema.

    Expects variables passed via @push('jsonld') from the content screen view:
      $jsonldArticle (array): {
          headline, url, imageUrl?, datePublished?, dateModified?, description?,
          author? — a name (Person) or a schema.org node; defaults to the store
      }

    The publisher is always the store, as an Organization; the author defaults to
    the same Organization when the content has no author of its own
    (modification 20261117T055341).

    @aidlc-unit seo
    @aidlc-story US-SEO-005
--}}
@if(!empty($jsonldArticle))
@php
    $articlePublisher = array_filter([
        '@type' => 'Organization',
        'name'  => gp247_store_info('name') ?: config('app.name'),
        'logo'  => !empty(gp247_store_info('logo')) ? gp247_file(gp247_store_info('logo')) : null,
    ]);
    $articleJsonLd = \GP247\Front\Library\SeoMeta::buildArticleJsonLd(
        headline:      $jsonldArticle['headline']      ?? '',
        url:           $jsonldArticle['url']           ?? request()->url(),
        imageUrl:      $jsonldArticle['imageUrl']      ?? null,
        datePublished: $jsonldArticle['datePublished'] ?? null,
        dateModified:  $jsonldArticle['dateModified']  ?? null,
        description:   $jsonldArticle['description']   ?? null,
        author:        !empty($jsonldArticle['author']) ? $jsonldArticle['author'] : $articlePublisher,
        publisher:     $articlePublisher,
    );
@endphp
<script type="application/ld+json">{!! $articleJsonLd !!}</script>
@endif
