<?php

namespace GP247\Front\Support;

/**
 * Shared view-resolution algorithm (ADR-011/ADR-013): resolve a component's
 * view against the currently active template first, falling back to the
 * owning package's default view when the template has no override.
 *
 * Used by both GP247\Front\Livewire\BaseFrontComponent (interactive, ADR-011)
 * and GP247\Front\View\Components\BaseFrontViewComponent (static
 * cross-cutting UI, ADR-013) so the two component kinds share one algorithm
 * instead of each re-implementing it.
 *
 * @aidlc-unit frontend-template-dev
 * @aidlc-story US-TPL-004, US-TPL-008
 * @aidlc-adr ADR-011, ADR-013
 */
trait ResolvesTemplateView
{
    /**
     * Name of the template currently active for the store.
     *
     * WHY: isolated as its own method (rather than inlining
     * gp247_store_info() in resolveTemplateView()) so tests can override it
     * without needing a full store/database fixture.
     *
     * @return string
     */
    protected function activeTemplateName(): string
    {
        // WHY: explicit default here — NOT the same fallback as
        // GP247_TEMPLATE_FRONT_DEFAULT (see Config/config.php) — must be kept
        // in sync manually (modification 20260705T124936, ADR-014 Amend #1:
        // 'Default' removed entirely, GP247Front is now the sole template).
        return (string) gp247_store_info('template', 'GP247Front');
    }

    /**
     * Resolve the view to render: the active template's override when it
     * exists, otherwise the package's default view.
     *
     * WHY the "For" suffix: BaseFrontComponent keeps its own no-arg
     * resolveTemplateView() (public test surface predates this trait) — a
     * same-named 2-arg trait method would silently shadow it instead of
     * resolving to an overload, so this is named distinctly on purpose.
     *
     * @param string $viewKey Dot-path key relative to a template root (e.g. "livewire.shop_cart-manager", "gp247_components.language-switcher").
     * @param string $defaultViewNamespace Fully-qualified Blade view namespace shipped by the owning package (e.g. "gp247-shop-front", "gp247-front").
     * @return string
     */
    protected function resolveTemplateViewFor(string $viewKey, string $defaultViewNamespace): string
    {
        $templateCandidate = 'GP247TemplatePath::' . $this->activeTemplateName() . '.' . $viewKey;

        if (view()->exists($templateCandidate)) {
            return $templateCandidate;
        }

        return $defaultViewNamespace . '::' . $viewKey;
    }
}
