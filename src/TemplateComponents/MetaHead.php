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

    public ?string $og_image;

    public ?string $canonical;

    /** @var array<string, string> Locale => absolute URL, from SeoMeta::hreflangLinks(). */
    public array $hreflang;

    public ?string $ogType;

    public function __construct(
        ?string $title = null,
        ?string $description = null,
        ?string $keyword = null,
        ?string $og_image = null,
        ?string $canonical = null,
        array $hreflang = [],
        ?string $ogType = null
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->keyword = $keyword;
        $this->og_image = $og_image;
        $this->canonical = $canonical;
        $this->hreflang = $hreflang;
        $this->ogType = $ogType;
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
