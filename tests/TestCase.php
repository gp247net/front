<?php

namespace GP247\Front\Tests;

use GP247\Core\Testing\GuardsDdlInTransaction;
use GP247\Core\Testing\GuardsTestDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base class of the gp247/front tests that boot the application.
 *
 * Refuses a database that is not marked as a test database before any test touches
 * it, and fails a test whose transaction was closed by DDL (both traits ship with
 * gp247/core).
 *
 * @aidlc-unit compat-foundation
 * @aidlc-story US-CMP-public-test-suite
 */
abstract class TestCase extends BaseTestCase
{
    use GuardsTestDatabase;
    use GuardsDdlInTransaction;
}
