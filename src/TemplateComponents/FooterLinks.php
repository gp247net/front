<?php

namespace GP247\Front\TemplateComponents;

/**
 * Footer link list (US-TPL-008): normalizes gp247_link_collection()['footer']
 * once here so no template Blade view needs to call it itself.
 *
 * @aidlc-unit frontend-template-dev
 * @aidlc-story US-TPL-008
 * @aidlc-adr ADR-013
 */
class FooterLinks extends BaseFrontViewComponent
{
    /** @var array<int, array{name: string, url: string, target: string}> */
    public array $links;

    public function __construct()
    {
        $this->links = [];

        $footer = gp247_link_collection()['footer'] ?? [];
        foreach ($footer as $url) {
            if ($url['type'] === 'collection') {
                continue;
            }

            $this->links[] = [
                'name' => gp247_language_render($url['data']['name']),
                'url' => gp247_url_render($url['data']['url']),
                'target' => $url['data']['target'] ?? '_self',
            ];
        }
    }

    protected function templateViewKey(): string
    {
        return 'gp247_components.footer-links';
    }

    protected function defaultViewNamespace(): string
    {
        return 'gp247-front';
    }
}
