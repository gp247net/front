<?php

namespace GP247\Front\Tests\Feature\SystemCli;

use GP247\Core\Library\ExtensionInstaller;
use GP247\Front\Tests\TestCase;

/**
 * Verifies the shared ExtensionInstaller guard logic (extension_protected +
 * runtime guard registry) that both the admin UI and the CLI rely on
 * (ADR system-cli_service-extraction, US-CLI-002, MC-cli-ext-lifecycle-parity).
 *
 * @aidlc-unit plugin-manager
 * @aidlc-story US-CLI-002
 * @aidlc-adr system-cli_service-extraction
 */
class ExtensionInstallerGuardTest extends TestCase
{
    /**
     * A protected plugin cannot be uninstalled (server-side enforcement, not just
     * a hidden UI button).
     *
     * @return void
     */
    public function test_extension_protected_blocks_uninstall(): void
    {
        config(['gp247-config.admin.extension.extension_protected' => ['Plugins' => ['MyLockedPlugin']]]);

        $reason = (new ExtensionInstaller)->blockReason('Plugins', 'MyLockedPlugin', 'uninstall');

        $this->assertNotNull($reason, 'A protected plugin must be blocked from uninstall');
    }

    /**
     * An unprotected plugin is not blocked.
     *
     * @return void
     */
    public function test_unprotected_plugin_uninstall_not_blocked(): void
    {
        config(['gp247-config.admin.extension.extension_protected' => ['Plugins' => []]]);

        $this->assertNull((new ExtensionInstaller)->blockReason('Plugins', 'SomeFreePlugin', 'uninstall'));
    }

    /**
     * The front-registered template guard refuses uninstalling the default
     * template even when no store row references it — proving core enforces a
     * front-owned rule via the runtime guard registry (NFR-MAINT-001 preserved).
     *
     * @return void
     */
    public function test_template_guard_blocks_default_template_uninstall(): void
    {
        $this->assertTrue(defined('GP247_TEMPLATE_FRONT_DEFAULT'), 'front must define the default template constant');

        $reason = (new ExtensionInstaller)->blockReason('Templates', GP247_TEMPLATE_FRONT_DEFAULT, 'uninstall');

        $this->assertNotNull($reason, 'The default template must be protected from uninstall');
    }

    /**
     * Disabling an unused plugin is allowed.
     *
     * @return void
     */
    public function test_disable_of_unused_plugin_not_blocked(): void
    {
        $this->assertNull((new ExtensionInstaller)->blockReason('Plugins', 'SomeFreePlugin', 'disable'));
    }
}
