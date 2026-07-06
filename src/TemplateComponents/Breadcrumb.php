<?php

namespace GP247\Front\TemplateComponents;

/**
 * Breadcrumb trail (US-TPL-008): thin wrapper around $breadcrumbs already
 * built by the controller — normalizes the "is this the last item" check
 * once here instead of every template re-deriving it.
 *
 * @aidlc-unit frontend-template-dev
 * @aidlc-story US-TPL-008
 * @aidlc-adr ADR-013
 */
class Breadcrumb extends BaseFrontViewComponent
{
    /** @var array<int, array{title: string, url: string, active: bool}> */
    public array $items;

    /**
     * @param array<int, array{title: string, url: string}> $items
     */
    public function __construct(array $items = [])
    {
        $count = count($items);
        $this->items = [];

        foreach (array_values($items) as $key => $item) {
            $this->items[] = [
                'title' => $item['title'],
                'url' => $item['url'],
                'active' => ($key + 1) === $count,
            ];
        }
    }

    protected function templateViewKey(): string
    {
        return 'gp247_components.breadcrumb';
    }

    protected function defaultViewNamespace(): string
    {
        return 'gp247-front';
    }
}
