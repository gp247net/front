<?php

namespace GP247\Front\TemplateComponents;

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
 * $canonical/$hreflang default to values that reproduce today's behaviour
 * (auto canonical, no hreflang) so existing callers are unaffected.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-001, US-SEO-002, US-SEO-003, US-SEO-005
 * @aidlc-adr ADR-013, ADR-014
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

    public ?string $canonical;

    /** @var array<string, string> Locale => absolute URL, from SeoMeta::hreflangLinks(). */
    public array $hreflang;

    public ?string $ogType;

    /** Placeholder gp247_image_get_path() returns when a record has no usable image. */
    private const NO_IMAGE_PLACEHOLDER = 'GP247/Core/images/no-image.jpg';

    /**
     * @param string|null $ogImage Page image path or URL. Named camelCase on purpose:
     *        Blade only passes `:og_image` / `:og-image` to the constructor when
     *        Str::camel(attribute) matches the parameter name — `$og_image` never
     *        did, so every page shared the store image
     *        (RISK-TECH-blade-component-snake-param, modification 20261025T084752).
     *
     * @aidlc-story US-SEO-001
     */
    public function __construct(
        ?string $title = null,
        ?string $description = null,
        ?string $keyword = null,
        ?string $ogImage = null,
        ?string $canonical = null,
        array $hreflang = [],
        ?string $ogType = null
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->keyword = $keyword;
        $this->og_image = $this->pageImageOrNull($ogImage);
        $this->canonical = $canonical;
        $this->hreflang = $hreflang;
        $this->ogType = $ogType;
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
