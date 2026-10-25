<?php

namespace GP247\Front\TemplateComponents;

/**
 * Footer link list (US-TPL-008): normalizes gp247_link_collection()['footer']
 * once here so no template Blade view needs to call it itself. A link may be a
 * plain entry or a "collection" (folder) grouping child links — rendered as a
 * group label + nested children, mirroring the dropdown/mobile-drawer
 * treatment in layout/block_menu.blade.php (the reference implementation for
 * this same gp247_link_collection() data shape).
 *
 * @aidlc-unit frontend-template-dev
 * @aidlc-story US-TPL-008
 * @aidlc-adr ADR-013
 */
class FooterLinks extends BaseFrontViewComponent
{
    /**
     * @var array<int, array{type: 'link', name: string, url: string, target: string}|array{type: 'collection', name: string, children: array<int, array{name: string, url: string, target: string}>}>
     */
    public array $links;

    public function __construct()
    {
        $this->links = [];

        $footer = gp247_link_collection()['footer'] ?? [];
        foreach ($footer as $url) {
            if ($url['type'] === 'collection') {
                $children = [];
                foreach ($url['childs'] as $item) {
                    $children[] = [
                        'name' => gp247_language_render($item['data']['name']),
                        'url' => gp247_url_render($item['data']['url']),
                        'target' => $item['data']['target'] ?? '_self',
                    ];
                }

                // WHY: an empty collection (no children) has nothing to show —
                // mirrors block_menu.blade.php's `count($url['childs'])` guard.
                if ($children === []) {
                    continue;
                }

                $this->links[] = [
                    'type' => 'collection',
                    'name' => $url['data']['name'],
                    'children' => $children,
                ];

                continue;
            }

            $this->links[] = [
                'type' => 'link',
                'name' => gp247_language_render($url['data']['name']),
                'url' => gp247_url_render($url['data']['url']),
                'target' => $url['data']['target'] ?? '_self',
            ];
        }

        $this->appendContactLink();
    }

    /**
     * Add "Contact" while the contact form is on, unless the owner already put a
     * footer link to the same address.
     *
     * @return void
     *
     * @aidlc-unit front-contact
     * @aidlc-story US-front-contact-toggle
     */
    private function appendContactLink(): void
    {
        if (!\GP247\Front\Support\ContactSettings::enabled()) {
            return;
        }

        $url = gp247_route_front('front.contact');
        foreach ($this->links as $link) {
            if (($link['url'] ?? null) === $url) {
                return;
            }
        }

        $this->links[] = [
            'type' => 'link',
            'name' => gp247_language_render('contact.page_title'),
            'url' => $url,
            'target' => '_self',
        ];
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
