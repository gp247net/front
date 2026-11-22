<?php

namespace GP247\Front\Tests\Unit\Front\Support;

use GP247\Front\Livewire\BaseFrontComponent;

/**
 * Minimal concrete subclass used only to exercise BaseFrontComponent's
 * template-method behaviour (view resolution) in isolation.
 */
class StubFrontComponent extends BaseFrontComponent
{
    public string $forcedTemplate = 'Default';

    /** @var array<string, mixed> */
    public array $forcedViewData = [];

    protected function templateViewKey(): string
    {
        return 'livewire.stub-component';
    }

    protected function defaultViewNamespace(): string
    {
        return 'gp247-stub-front';
    }

    protected function activeTemplateName(): string
    {
        return $this->forcedTemplate;
    }

    protected function viewData(): array
    {
        return $this->forcedViewData;
    }

    /** Expose the protected resolver for test assertions. */
    public function resolvedViewForTest(): string
    {
        return $this->resolveTemplateView();
    }
}
