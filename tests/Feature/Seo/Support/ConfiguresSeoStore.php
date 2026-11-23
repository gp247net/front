<?php

namespace GP247\Front\Tests\Feature\Seo\Support;

use GP247\Front\Testing\ConfiguresSeoStore as SharedConfiguresSeoStore;

/**
 * Sets per-store SEO switches for a test.
 *
 * WHY an alias: the helper moved to src/Testing so that the test suites of other packages
 * can use it; tests that use this name keep working with one implementation.
 *
 * @aidlc-unit seo
 */
trait ConfiguresSeoStore
{
    use SharedConfiguresSeoStore;
}
