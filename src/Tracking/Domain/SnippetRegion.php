<?php

namespace GP247\Front\Tracking\Domain;

/**
 * Where a block of code goes on the storefront page once it is allowed to run.
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-custom-code
 * @aidlc-adr tracking-consent_head-injection-via-meta-head
 */
enum SnippetRegion: string
{
    case HEAD = 'head';
    case BODY_OPEN = 'body_open';
    case BODY_CLOSE = 'body_close';
}
