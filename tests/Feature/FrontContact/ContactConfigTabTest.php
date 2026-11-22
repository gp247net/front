<?php

namespace GP247\Front\Tests\Feature\FrontContact;

use GP247\Core\AdminShell\Support\SettingsHubTabRegistry;
use GP247\Core\Models\AdminConfig;
use GP247\Front\Admin\Livewire\ContactConfigForm;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use GP247\Core\Testing\InteractsWithAdminUser;
use GP247\Front\Tests\Feature\FrontContact\Support\InteractsWithContactForm;
use GP247\Front\Tests\TestCase;

/**
 * The owner configures the contact form on a "Contact" tab of the Configuration
 * hub (on/off + who gets mail), gated by its own permission; the upgrade that
 * brings the form keeps the mail choices a site already made.
 *
 * @aidlc-unit front-contact
 * @aidlc-story US-front-contact-config-tab
 * @aidlc-adr front-contact_contact-form-in-front, admin-shell_config-hub-tab-registry
 */
#[Group('front-contact')]
class ContactConfigTabTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAdminUser;
    use InteractsWithContactForm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetConfigCaches();
    }

    /**
     * @return object The upgrade migration that installs the contact form.
     */
    private function migration(): object
    {
        return require base_path('vendor/gp247/front/src/Admin/Database/Migrations/upgrade/2026_10_25_074941_install_front_contact.php');
    }

    public function test_the_configuration_screen_has_a_contact_tab_with_its_own_permission(): void
    {
        $tab = collect(SettingsHubTabRegistry::all())->firstWhere('key', 'contact');

        $this->assertNotNull($tab, 'front registers the Contact tab once its table exists');
        $this->assertSame('gp247-front-admin::contact-config-form', $tab['component']);
        $this->assertStringEndsWith('/contact_config', $tab['authUri']);
    }

    public function test_the_owner_turns_the_form_on_and_picks_who_gets_mail(): void
    {
        $this->asUser(slugs: ['admin_contact_config']);
        $this->setContactConfig(['contact_status' => 0, 'contact_to_admin' => 1, 'contact_to_customer' => 0]);

        Livewire::test(ContactConfigForm::class)
            ->assertSee(gp247_language_render('admin.contact.config.contact_status'))
            ->set('values.contact_status', true)
            ->set('values.contact_to_customer', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->resetConfigCaches();
        $this->assertSame('1', (string) gp247_config('contact_status', $this->rootStoreId()));
        $this->assertSame('1', (string) gp247_config('contact_to_customer', $this->rootStoreId()));
        $this->assertSame('1', (string) gp247_config('contact_to_admin', $this->rootStoreId()));
    }

    public function test_the_tab_warns_when_system_mail_is_off(): void
    {
        $this->asUser(slugs: ['admin_contact_config']);
        $this->setCoreConfig('email_action_mode', 0);

        Livewire::test(ContactConfigForm::class)
            ->assertSee('data-testid="config-form-readiness-item-system_mail"', false);
    }

    public function test_an_admin_without_the_permission_cannot_change_the_settings(): void
    {
        $this->asUser(slugs: ['admin_contact']);

        $this->assertDenied(fn () => Livewire::test(ContactConfigForm::class));
    }

    public function test_the_upgrade_moves_the_old_mail_choices_to_the_contact_settings_and_can_run_twice(): void
    {
        $root = $this->rootStoreId();
        // State of a site upgraded from before the contact form existed.
        AdminConfig::where('key', 'contact_to_admin')->where('store_id', $root)
            ->update(['group' => 'gp247_cart', 'code' => 'sendmail_config', 'value' => '0']);
        AdminConfig::where('key', 'contact_to_customer')->where('store_id', $root)
            ->update(['group' => 'gp247_cart', 'code' => 'sendmail_config', 'value' => '1']);

        $migration = $this->migration();
        $migration->moveLegacyMailToggles();
        $migration->seedConfig();
        $migration->moveLegacyMailToggles();
        $migration->seedConfig();

        $rows = AdminConfig::whereIn('key', ['contact_status', 'contact_to_admin', 'contact_to_customer'])
            ->where('store_id', $root)->get()->keyBy('key');
        $this->assertCount(3, $rows);
        foreach ($rows as $row) {
            $this->assertSame('contact_config', $row->code);
            $this->assertSame('front_contact', $row->group);
            $this->assertSame('admin.contact.config.' . $row->key, $row->detail, 'the Contact tab labels the row with its own wording');
        }
        $this->assertSame('0', (string) $rows['contact_to_admin']->value, 'value chosen before the upgrade is kept');
        $this->assertSame('1', (string) $rows['contact_to_customer']->value, 'value chosen before the upgrade is kept');
        $this->assertSame(0, AdminConfig::whereIn('key', ['contact_to_admin', 'contact_to_customer'])->where('code', 'sendmail_config')->count());
    }
}
