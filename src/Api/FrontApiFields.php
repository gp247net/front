<?php

namespace GP247\Front\Api;

/**
 * Field allowlists of the v1 public banner/page REST API (ApiFieldAllowlist specs).
 * A new table column only reaches the API when it is added here; the banner click
 * counter is internal and left out.
 *
 * @aidlc-unit compat-foundation
 * @aidlc-story US-CMP-api-public-field-allowlist
 * @aidlc-adr compat-foundation_rest-api-hardening
 */
final class FrontApiFields
{
    /** Public banner (no click counter). */
    public const BANNER = [
        'id', 'name', 'image', 'url', 'target', 'html', 'status', 'sort', 'type', 'store_id', 'created_at', 'updated_at',
    ];

    /** Public CMS page. */
    public const PAGE = [
        'id', 'image', 'alias', 'status', 'store_id', 'created_at', 'updated_at', 'descriptions' => '*',
    ];
}
