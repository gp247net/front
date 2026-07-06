<?php

namespace GP247\Front\TemplateComponents;

/**
 * Flash-message toast trigger (US-TPL-008): the view itself reads the flash
 * session keys and calls the alertJs() SweetAlert2 helper it defines, so no
 * PHP-side data preparation is needed here.
 *
 * @aidlc-unit frontend-template-dev
 * @aidlc-story US-TPL-008
 * @aidlc-adr ADR-013
 */
class Notice extends BaseFrontViewComponent
{
    protected function templateViewKey(): string
    {
        return 'gp247_components.notice';
    }

    protected function defaultViewNamespace(): string
    {
        return 'gp247-front';
    }
}
