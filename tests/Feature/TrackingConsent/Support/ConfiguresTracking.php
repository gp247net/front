<?php

namespace GP247\Front\Tests\Feature\TrackingConsent\Support;

use GP247\Front\Testing\ConfiguresTracking as SharedConfiguresTracking;

/**
 * Configures the tracking and consent settings for a test.
 *
 * WHY an alias: the helper moved to src/Testing so that the test suites of other packages
 * can use it; tests that use this name keep working with one implementation.
 *
 * @aidlc-unit tracking-consent
 */
trait ConfiguresTracking
{
    use SharedConfiguresTracking;
}
