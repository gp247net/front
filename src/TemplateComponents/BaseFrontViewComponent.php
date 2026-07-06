<?php

namespace GP247\Front\TemplateComponents;

use GP247\Front\Support\ResolvesTemplateView;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Headless base class for static (non-interactive) cross-cutting frontend UI
 * — language switcher, currency switcher, footer links, breadcrumb, notice.
 *
 * Sibling of GP247\Front\Livewire\BaseFrontComponent (ADR-011): same
 * view-resolution algorithm (shared trait), but a plain Blade component
 * instead of Livewire, since none of these need server-round-trip
 * reactivity — they render once per request from data prepared in PHP.
 *
 * A Template Developer only ever writes the Blade view under
 * `app/GP247/Templates/<Template>/gp247_components/<key>.blade.php`; the data
 * (e.g. $languages, $links) is always pre-built by the subclass.
 *
 * @aidlc-unit frontend-template-dev
 * @aidlc-story US-TPL-008
 * @aidlc-adr ADR-013
 */
abstract class BaseFrontViewComponent extends Component
{
    use ResolvesTemplateView;

    /**
     * Dot-path key identifying this component's view, relative to a
     * template root (e.g. "gp247_components.language-switcher").
     *
     * @return string
     */
    abstract protected function templateViewKey(): string;

    /**
     * Fully-qualified Blade view namespace shipped by the owning package,
     * used when the active template has no override (e.g. "gp247-front").
     *
     * @return string
     */
    abstract protected function defaultViewNamespace(): string;

    /**
     * Render the component using the resolved template-aware view. Public
     * properties set by the subclass's constructor are passed to the view
     * automatically by Illuminate\View\Component's data resolution.
     *
     * @return \Illuminate\View\View
     */
    public function render(): View
    {
        return view($this->resolveTemplateViewFor($this->templateViewKey(), $this->defaultViewNamespace()));
    }
}
