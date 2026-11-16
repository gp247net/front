<?php

namespace GP247\Front\TemplateComponents;

use GP247\Front\Library\SeoMeta;

/**
 * Shared <head> block: meta/OG/favicon/csrf plus the full SEO surface
 * (canonical, hreflang, Twitter card, JSON-LD Organization).
 *
 * Sixth component under BaseFrontViewComponent (US-TPL-008, ADR-013 decision
 * 5, amend #2 — modification 20260702T180000): previously a fixed partial
 * with no per-template override; now overridable via
 * app/GP247/Templates/<Template>/gp247_components/meta-head.blade.php exactly
 * like Breadcrumb/Notice.
 *
 * Extended (US-SEO-001/002/003/005, ADR-014, modification 20260702T190000)
 * to be the single source for every <head> SEO tag — folding in what used
 * to be two separate includes (seo_head.blade.php, jsonld_organization.blade.php)
 * that a later refactor silently dropped from layout.blade.php (RISK-TECH-012).
 * Indexing signals are decided HERE, not by the caller (ADR-seo-indexing-signals,
 * modification 20261116T050835): robots, canonical, og:url, hreflang and the
 * search-engine verification codes are computed from the current route when a
 * prop is not passed, because template layouts (GP247EC, MKP) pass only the
 * original six props. A prop that is passed always wins.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-001, US-SEO-002, US-SEO-003, US-SEO-005, US-SEO-robots-meta, US-SEO-site-verification
 * @aidlc-adr ADR-013, ADR-014, ADR-seo-indexing-signals
 */
class MetaHead extends BaseFrontViewComponent
{
    public ?string $title;

    public ?string $description;

    public ?string $keyword;

    /**
     * Page image for og:image / twitter:image; null = use the store image.
     * Kept snake_case because template overrides of meta-head.blade.php read
     * `$og_image` (ADR-013 amend 2026-10-25).
     */
    public ?string $og_image;

    /** Canonical URL — the prop, or SeoMeta::canonicalUrl(); never null after construction. */
    public ?string $canonical;

    /** @var array<string, string> Locale => absolute URL, from SeoMeta::hreflangLinks(). */
    public array $hreflang;

    /** x-default target of $hreflang (store default language), null when there is no hreflang. */
    public ?string $hreflangDefault;

    public ?string $ogType;

    /** og:url — always the canonical, so shared links match it. */
    public string $ogUrl;

    /** `<meta name="robots">` content, or null for no tag (search engines then index). */
    public ?string $robots;

    /** @var array<string, string> Search engine => verification token, from SeoMeta::verificationCodes(). */
    public array $verification;

    /** Placeholder gp247_image_get_path() returns when a record has no usable image. */
    private const NO_IMAGE_PLACEHOLDER = 'GP247/Core/images/no-image.jpg';

    /**
     * @param string|null $ogImage Page image path or URL. Named camelCase on purpose:
     *        Blade only passes `:og_image` / `:og-image` to the constructor when
     *        Str::camel(attribute) matches the parameter name — `$og_image` never
     *        did, so every page shared the store image
     *        (RISK-TECH-blade-component-snake-param, modification 20261025T084752).
     *
     * @param string|null $robots  Robots directive; null = decide from the route/query.
     *
     * @aidlc-story US-SEO-001, US-SEO-002, US-SEO-003, US-SEO-robots-meta, US-SEO-site-verification
     */
    public function __construct(
        ?string $title = null,
        ?string $description = null,
        ?string $keyword = null,
        ?string $ogImage = null,
        ?string $canonical = null,
        array $hreflang = [],
        ?string $ogType = null,
        ?string $robots = null
    ) {
        $this->title = $title;
        // WHY: controllers pass '' for "no description"; the view only falls back to
        // the store's value on null, so '' used to print content="" (mod 20261117T055341).
        $this->description = self::nullIfBlank($description);
        $this->keyword = self::nullIfBlank($keyword);
        $this->og_image = $this->pageImageOrNull($ogImage);
        $this->canonical = self::nullIfBlank($canonical) ?? SeoMeta::canonicalUrl();
        $this->ogUrl = $this->canonical;
        $this->ogType = self::nullIfBlank($ogType) ?? SeoMeta::ogTypeFor() ?? 'website';
        $this->robots = ($robots !== null && trim($robots) !== '') ? trim($robots) : SeoMeta::robotsFor();
        $this->hreflang = $this->resolveHreflang($hreflang);
        $this->hreflangDefault = SeoMeta::hreflangDefault($this->hreflang);
        $this->verification = SeoMeta::verificationCodes();
    }

    /**
     * Treat an empty or whitespace-only string as "not given".
     *
     * @param string|null $value
     * @return string|null
     *
     * @aidlc-story US-SEO-001
     */
    private static function nullIfBlank(?string $value): ?string
    {
        return ($value === null || trim($value) === '') ? null : $value;
    }

    /**
     * hreflang of the page: none on a noindex page (an alternate of a page that
     * must not be indexed is noise), else the caller's map, else the map built
     * from the current route.
     *
     * @param array<string, string> $given Map passed by the caller.
     * @return array<string, string>
     *
     * @aidlc-story US-SEO-003
     */
    private function resolveHreflang(array $given): array
    {
        if ($this->robots !== null && str_contains(strtolower($this->robots), 'noindex')) {
            return [];
        }

        return $given !== [] ? $given : SeoMeta::currentRouteHreflangLinks();
    }

    /**
     * Treat an empty value or the "no image" placeholder as no page image, so
     * the view falls back to the store's og_image instead of sharing a
     * placeholder graphic. Controllers pass gp247_file($model->getImage()),
     * which yields the placeholder (as a path or absolute URL) for records
     * without an image.
     *
     * @param string|null $image Raw value from the controller.
     * @return string|null The page image, or null when there is none.
     *
     * @aidlc-story US-SEO-001
     */
    private function pageImageOrNull(?string $image): ?string
    {
        if ($image === null || trim($image) === '' || str_ends_with($image, self::NO_IMAGE_PLACEHOLDER)) {
            return null;
        }

        return $image;
    }

    protected function templateViewKey(): string
    {
        return 'gp247_components.meta-head';
    }

    protected function defaultViewNamespace(): string
    {
        return 'gp247-front';
    }
}
