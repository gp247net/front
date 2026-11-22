<?php

namespace GP247\Front\Tests\Feature\FrontAdmin;

use GP247\Core\Models\AdminRole;
use GP247\Core\Models\AdminUser;
use GP247\Core\Controllers\ExtensionController;
use GP247\Core\Controllers\ExtensionOnlineController;
use GP247\Front\Admin\Controllers\AdminTemplateController;
use GP247\Front\Admin\Controllers\AdminTemplateOnlineController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use GP247\Front\Tests\TestCase;

/**
 * Verifies US-FADM-005 (Template / TemplateOnline) without a Livewire rebuild.
 *
 * These extension-style screens are not CRUD: the front controllers only `use`
 * core's ExtensionController / ExtensionOnlineController traits, which render
 * `gp247-admin::screen.extension*`. Core already ported those views in place to
 * the TailAdmin plain layout (gp247-admin::layouts.plain), so the front template
 * admin already runs on TailAdmin — zero AdminLTE, marketplace/import untouched
 * (MC-008/MC-013). This locks that fact against regression.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-005
 * @aidlc-adr ADR-006, ADR-007
 */
class TemplateAdminTest extends TestCase
{
    use DatabaseTransactions;

    /** TailAdmin plain-layout asset — presence proves the modern shell. */
    private const TAILADMIN_MARKER = 'GP247/Core/AdminShell/css/admin.css';

    /**
     * Legacy AdminLTE master asset — absence proves no legacy layout. Must be the
     * AdminLTE core CSS (LTE/dist), not the shared FontAwesome under LTE/plugins
     * which the TailAdmin plain layout legitimately still reuses.
     */
    private const ADMINLTE_MARKER = 'GP247/Core/LTE/dist/css/adminlte.min.css';

    /**
     * Create + authenticate an administrator on the `admin` guard. The
     * administrator role short-circuits the URI permission middleware, so the
     * test exercises rendering rather than RBAC wiring (covered elsewhere).
     *
     * @return AdminUser
     */
    private function loginAdministrator(): AdminUser
    {
        $user = AdminUser::create([
            'id' => 'AU-TPL',
            'username' => 'template_admin',
            'password' => bcrypt('secret'),
            'name' => 'Template Admin',
            'email' => 'template@test.local',
            'status' => 1,
        ]);

        $role = AdminRole::firstOrCreate(
            ['slug' => 'administrator'],
            ['name' => 'Administrator'],
        );
        $user->roles()->syncWithoutDetaching([$role->id]);

        $this->actingAs($user, 'admin');

        return $user;
    }

    public function test_front_template_controllers_reuse_core_extension_traits(): void
    {
        // Trait reuse is the mechanism that makes the front screens render core's
        // already-ported TailAdmin view; if it regresses, the views diverge.
        $this->assertContains(
            ExtensionController::class,
            class_uses_recursive(AdminTemplateController::class),
        );
        $this->assertContains(
            ExtensionOnlineController::class,
            class_uses_recursive(AdminTemplateOnlineController::class),
        );
    }

    public function test_template_index_renders_on_tailadmin_plain_layout(): void
    {
        $this->loginAdministrator();

        $response = $this->get(route('admin_template.index'));

        $response->assertOk();
        $response->assertSee(self::TAILADMIN_MARKER, false);
        $response->assertDontSee(self::ADMINLTE_MARKER, false);
        // Marketplace/import action preserved (presentation-only migration).
        $response->assertSee(route('admin_template.import'), false);
    }

    public function test_template_online_index_renders_on_tailadmin_plain_layout(): void
    {
        $this->loginAdministrator();

        $response = $this->get(route('admin_template_online.index'));

        $response->assertOk();
        $response->assertSee(self::TAILADMIN_MARKER, false);
        $response->assertDontSee(self::ADMINLTE_MARKER, false);
    }

    public function test_template_import_screen_renders_on_tailadmin_plain_layout(): void
    {
        $this->loginAdministrator();

        $response = $this->get(route('admin_template.import'));

        $response->assertOk();
        $response->assertSee(self::TAILADMIN_MARKER, false);
        $response->assertDontSee(self::ADMINLTE_MARKER, false);
    }

    public function test_template_admin_requires_authentication(): void
    {
        // No login: the admin guard must gate the screen (no anonymous access).
        $response = $this->get(route('admin_template.index'));

        $response->assertRedirect();
    }
}
