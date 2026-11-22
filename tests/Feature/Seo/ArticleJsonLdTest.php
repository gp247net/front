<?php

namespace GP247\Front\Tests\Feature\Seo;

use GP247\Core\Models\AdminConfig;
use GP247\Front\Library\SeoMeta;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use GP247\Front\Tests\TestCase;

/**
 * JSON-LD Article always names who published it (the store) and an author, and drops
 * an empty description instead of printing "".
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-005
 * @aidlc-adr ADR-014
 */
class ArticleJsonLdTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Reset AdminConfig's per-store memo (another test leaves it null).
     */
    protected function setUp(): void
    {
        parent::setUp();
        (new \ReflectionProperty(AdminConfig::class, 'getAllConfigOfStore'))->setValue(null, []);
    }

    /**
     * Render the shared Article partial the way a content screen includes it.
     *
     * @param array<string, mixed> $article
     * @return array<string, mixed> Decoded JSON-LD.
     */
    private function renderArticle(array $article): array
    {
        $html = view('GP247TemplatePath::GP247Front.common.jsonld_article', ['jsonldArticle' => $article])->render();
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $match);

        return json_decode($match[1] ?? 'null', true) ?? [];
    }

    public function test_the_publisher_is_the_store_as_an_organization(): void
    {
        $data = $this->renderArticle(['headline' => 'ZZ Article', 'url' => 'https://shop.test/zz.html']);

        $this->assertSame('Organization', $data['publisher']['@type']);
        $this->assertSame(gp247_store_info('name') ?: config('app.name'), $data['publisher']['name']);
    }

    public function test_the_author_defaults_to_the_store_when_the_content_has_none(): void
    {
        $data = $this->renderArticle(['headline' => 'ZZ Article', 'url' => 'https://shop.test/zz.html']);

        $this->assertSame($data['publisher'], $data['author']);
    }

    public function test_an_author_given_by_the_content_screen_is_kept(): void
    {
        $data = $this->renderArticle(['headline' => 'ZZ Article', 'url' => 'https://shop.test/zz.html', 'author' => 'ZZ Writer']);

        $this->assertSame(['@type' => 'Person', 'name' => 'ZZ Writer'], $data['author']);
    }

    public function test_an_empty_description_is_left_out(): void
    {
        $data = $this->renderArticle(['headline' => 'ZZ Article', 'url' => 'https://shop.test/zz.html', 'description' => '  ']);

        $this->assertArrayNotHasKey('description', $data);
    }

    public function test_the_builder_stays_compatible_with_callers_that_pass_no_publisher(): void
    {
        $data = json_decode(SeoMeta::buildArticleJsonLd('ZZ', 'https://shop.test/zz.html', null, null, null, 'ZZ desc'), true);

        $this->assertSame('ZZ desc', $data['description']);
        $this->assertArrayNotHasKey('publisher', $data);
    }
}
