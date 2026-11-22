<?php

namespace GP247\Front\Tests\Feature\SystemCli;

use GP247\Core\Commands\Install;
use GP247\Core\Console\GP247Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;
use GP247\Front\Tests\TestCase;

/**
 * The administrator's sign-in details (admin path, username, the generated password shown once) are the
 * last block `gp247:install` prints, after every package step — a site owner on Windows/Laragon saw the
 * password scroll away under the front/shop install output.
 *
 * WHY fake steps: core/front/shop install migrate and re-create tables; they must never run against the
 * shared test database. Each install step is replaced by a fake command of the same name that only prints
 * noise; the fake core step goes through the real announcement seam of {@see Install}. Every test first
 * proves the real commands are no longer reachable before calling gp247:install.
 *
 * @aidlc-unit system-cli
 * @aidlc-story US-CLI-003
 */
class InstallCredentialsLastTest extends TestCase
{
    /** Password the fake core step "generates". */
    private const PASSWORD = 'Fake-Generated-Pass-2026';

    /**
     * Install step that should fail in the current test, or null.
     *
     * @var string|null
     */
    public static ?string $failing = null;

    /**
     * Whether the failing step throws instead of returning a failure code.
     *
     * @var bool
     */
    public static bool $crash = false;

    /**
     * Replace every install step with a fake and check the replacement took effect.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        self::$failing = null;
        self::$crash = false;
        Install::$lastAdmin = null;

        $fakes = ['gp247:core-install' => new FakeCoreInstall()];
        foreach (['gp247:front-install', 'gp247:shop-install', 'gp247:mcp-install'] as $name) {
            if (array_key_exists($name, Artisan::all())) {
                $fakes[$name] = new FakeNoisyStep($name);
            }
        }
        foreach ($fakes as $fake) {
            $this->app[Kernel::class]->registerCommand($fake);
        }
        foreach ($fakes as $name => $fake) {
            // Safety: the real step would DROP tables — stop here unless the fake is what runs.
            $this->assertSame($fake, Artisan::all()[$name], "$name must be the fake before gp247:install runs");
        }
    }

    /**
     * Run gp247:install unattended and return exit code + everything it printed, in order.
     *
     * @param array<string, mixed> $options
     * @return array{code: int, output: string}
     */
    private function install(array $options = []): array
    {
        $buffer = new BufferedOutput();
        $code = Artisan::call('gp247:install', array_merge(['--force' => 1], $options), $buffer);

        return ['code' => $code, 'output' => $buffer->fetch()];
    }

    public function test_the_sign_in_details_are_the_last_block_after_every_package_step(): void
    {
        $run = $this->install();
        $out = $run['output'];

        $this->assertSame(0, $run['code']);
        $this->assertSame(1, substr_count($out, self::PASSWORD), 'the generated password is printed exactly once');
        $this->assertGreaterThan(strrpos($out, 'FAKE-STEP-NOISE'), strpos($out, self::PASSWORD), 'no step output after the password');
        $this->assertGreaterThan(strrpos($out, 'FAKE-STEP-NOISE'), strpos($out, 'Username: admin'));
        $this->assertGreaterThan(strrpos($out, 'FAKE-STEP-NOISE'), strpos($out, 'Admin path:'));
        $this->assertFalse(Install::$deferWelcome, 'the deferral never leaks past the install');
    }

    public function test_the_sign_in_details_are_still_shown_when_a_later_step_fails(): void
    {
        $failing = array_key_exists('gp247:shop-install', Artisan::all()) ? 'gp247:shop-install' : 'gp247:front-install';
        self::$failing = $failing;

        $run = $this->install();
        $out = $run['output'];

        $this->assertNotSame(0, $run['code']);
        $this->assertStringContainsString('Step failed: ' . $failing, $out);
        $this->assertSame(1, substr_count($out, self::PASSWORD), 'the password set before the failure is still shown, once');
        $this->assertGreaterThan(strrpos($out, 'FAKE-STEP-NOISE'), strpos($out, self::PASSWORD));
        $this->assertFalse(Install::$deferWelcome);
    }

    public function test_the_sign_in_details_are_still_shown_when_a_later_step_crashes(): void
    {
        self::$failing = 'gp247:front-install';
        self::$crash = true;

        $run = $this->install();
        $out = $run['output'];

        $this->assertNotSame(0, $run['code']);
        $this->assertStringContainsString('Step failed: gp247:front-install — fake crash', $out);
        $this->assertSame(1, substr_count($out, self::PASSWORD));
        $this->assertGreaterThan(strpos($out, 'fake crash'), strpos($out, self::PASSWORD), 'the sign-in details stay the last block');
        $this->assertFalse(Install::$deferWelcome);
    }

    public function test_the_json_failure_envelope_still_returns_the_generated_password_once(): void
    {
        self::$failing = array_key_exists('gp247:shop-install', Artisan::all()) ? 'gp247:shop-install' : 'gp247:front-install';

        $out = $this->install(['--json' => true])['output'];
        $lines = array_values(array_filter(array_map('trim', explode("\n", $out)), 'strlen'));
        $envelope = json_decode((string) end($lines), true);

        $this->assertIsArray($envelope, 'the envelope is the last line');
        $this->assertFalse($envelope['ok']);
        $this->assertSame('step_failed', $envelope['error']['code']);
        $this->assertSame(['username' => 'admin', 'password' => self::PASSWORD], $envelope['data']['admin']);
        $this->assertSame(1, substr_count($out, self::PASSWORD), 'only the envelope carries the password');
    }

    public function test_core_install_on_its_own_still_ends_with_the_sign_in_details(): void
    {
        $buffer = new BufferedOutput();
        $code = Artisan::call('gp247:core-install', ['--force' => 1], $buffer);
        $out = $buffer->fetch();

        $this->assertSame(0, $code);
        $this->assertSame(1, substr_count($out, self::PASSWORD));
        $this->assertStringContainsString('Username: admin', $out);
    }
}

/**
 * Stand-in for gp247:core-install: no migration, no seed, no storage write — it only hands the
 * administrator over and announces it through the real seam of {@see Install}.
 */
class FakeCoreInstall extends Install
{
    /**
     * Pretend the install ran and the seeded administrator got a generated password.
     *
     * @return int
     */
    protected function handleGp247(): int
    {
        $this->line('FAKE-STEP-NOISE core');
        $admin = ['username' => 'admin', 'password' => 'Fake-Generated-Pass-2026', 'state' => 'generated'];
        self::$lastAdmin = $admin;
        $this->announceAdmin($admin);

        return $this->respondSuccess(['installed' => true, 'admin' => ['username' => 'admin', 'password' => $admin['password']]]);
    }
}

/**
 * Stand-in for a front/shop/mcp install step: prints a lot, touches nothing.
 */
class FakeNoisyStep extends GP247Command
{
    protected $description = 'Fake install step (test only)';

    /**
     * @param string $name Command name to stand in for.
     */
    public function __construct(string $name)
    {
        $this->signature = $name;
        parent::__construct();
    }

    /**
     * Print noise; fail (or crash past the base handler) when the test asks this step to.
     *
     * @return int
     */
    public function handle(): int
    {
        if (InstallCredentialsLastTest::$crash && InstallCredentialsLastTest::$failing === $this->getName()) {
            // WHY bypass the base handler: a step that is not a GP247Command throws straight up.
            throw new \RuntimeException('fake crash');
        }

        return parent::handle();
    }

    /**
     * Print noise; fail when the test asks this step to fail.
     *
     * @return int
     */
    protected function handleGp247(): int
    {
        for ($i = 0; $i < 30; $i++) {
            $this->line('FAKE-STEP-NOISE ' . $this->getName() . ' ' . $i);
        }

        return InstallCredentialsLastTest::$failing === $this->getName()
            ? $this->respondFailure('fake_failure', 'fake failure')
            : $this->respondSuccess();
    }
}
