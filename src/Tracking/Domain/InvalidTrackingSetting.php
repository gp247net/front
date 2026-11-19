<?php

namespace GP247\Front\Tracking\Domain;

/**
 * A tracking setting or code block that breaks a rule. Carries one language code per
 * field so the settings screen can show each message under its own input.
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-preset-services, US-tracking-consent-custom-code
 * @aidlc-adr tracking-consent_config-storage-and-authority
 */
final class InvalidTrackingSetting extends \DomainException
{
    /** @var array<string, string> field => language code */
    private array $errors;

    /**
     * @param array<string, string> $errors field => language code of the message.
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct((string) (reset($errors) ?: 'admin.tracking.error_invalid'));
    }

    /**
     * @return array<string, string> field => language code of the message.
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
