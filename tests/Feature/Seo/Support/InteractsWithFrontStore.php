<?php

namespace GP247\Front\Tests\Feature\Seo\Support;

use GP247\Front\Testing\InteractsWithFrontStore as SharedInteractsWithFrontStore;

/**
 * Activates the front store for tests that hit public front routes.
 *
 * WHY an alias: the helper moved to src/Testing so that the test suites of other packages
 * can use it; tests that use this name keep working with one implementation.
 *
 * @aidlc-unit seo
 */
trait InteractsWithFrontStore
{
    use SharedInteractsWithFrontStore;
}
