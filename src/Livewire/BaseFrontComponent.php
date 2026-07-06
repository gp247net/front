<?php

namespace GP247\Front\Livewire;

use GP247\Front\Support\ResolvesTemplateView;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Headless base class for public-facing (front/shop) Livewire components.
 *
 * Separates logic/data (this class and its subclasses) from presentation
 * (Blade view). Subclasses declare a view key and a default package view
 * namespace; render() resolves the actual view through the currently active
 * template first, falling back to the package default when the template has
 * no override. This lets a Template Developer change HTML for any component
 * by dropping a Blade file under `app/GP247/Templates/<Template>/livewire/`
 * without touching PHP.
 *
 * Placed in `gp247/front` (not `gp247/core`): the frontend layer only exists
 * in `front`/`shop` — `shop` already requires `front`, so storefront
 * components can extend this directly.
 *
 * @aidlc-unit frontend-template-dev
 * @aidlc-story US-TPL-004, US-TPL-005
 * @aidlc-adr ADR-011
 */
abstract class BaseFrontComponent extends Component
{
    use ResolvesTemplateView;

    /**
     * Dot-path key identifying this component's view, relative to a
     * template root (e.g. "livewire.shop_cart-manager").
     *
     * @return string
     */
    abstract protected function templateViewKey(): string;

    /**
     * Fully-qualified Blade view namespace shipped by the owning package,
     * used when the active template has no override (e.g. "gp247-shop-front").
     *
     * @return string
     */
    abstract protected function defaultViewNamespace(): string;

    /**
     * Data passed to the resolved view. Subclasses override to supply the
     * variables their view needs; the base class does not decide HTML.
     *
     * @return array<string, mixed>
     */
    protected function viewData(): array
    {
        return [];
    }

    /**
     * Resolve the view to render: the active template's override when it
     * exists, otherwise the package's default view.
     *
     * WHY this thin wrapper stays: activeTemplateName() and the resolution
     * algorithm itself now live in the ResolvesTemplateView trait (ADR-013,
     * shared with BaseFrontViewComponent) — this no-arg overload is kept so
     * existing callers/tests (e.g. StubFrontComponent::resolvedViewForTest())
     * are unaffected by the extraction.
     *
     * @return string
     */
    protected function resolveTemplateView(): string
    {
        return $this->resolveTemplateViewFor($this->templateViewKey(), $this->defaultViewNamespace());
    }

    /**
     * Render the component using the resolved template-aware view.
     *
     * @return \Illuminate\View\View
     */
    public function render(): View
    {
        return view($this->resolveTemplateView(), $this->viewData());
    }
}
