<?php

namespace GP247\Front\Tests\Feature\SystemCli;

use GP247\Core\AdminShell\Domain\AdminAlertCheck;
use GP247\Core\AdminShell\Infrastructure\AdminAlert;
use GP247\Core\AdminShell\Support\AdminAlertRegistry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;
use GP247\Front\Tests\TestCase;

/**
 * gp247:doctor also runs the checks packages and plugins registered for the admin
 * "System alerts" block, without changing its exit code.
 *
 * @aidlc-unit system-cli
 * @aidlc-story US-CLI-doctor-registered-checks
 * @aidlc-adr admin-shell_system-alert-registry
 */
class DoctorRegisteredChecksTest extends TestCase
{
    use DatabaseTransactions;

    private array $saved = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->saved = AdminAlertRegistry::all();
    }

    protected function tearDown(): void
    {
        AdminAlertRegistry::reset();
        foreach ($this->saved as $class) {
            AdminAlertRegistry::register($class);
        }
        parent::tearDown();
    }

    public function test_a_registered_warning_appears_as_a_warn_row_with_its_items(): void
    {
        AdminAlertRegistry::register(ZzWarningCheck::class);

        $row = $this->row($this->doctor(), 'zz_registered_warning');

        $this->assertNotNull($row);
        $this->assertSame('warn', $row['status']);
        $this->assertSame(['zz item'], $row['items'] ?? []);
    }

    public function test_a_check_that_throws_is_skipped_not_failed(): void
    {
        AdminAlertRegistry::register(ZzThrowingCheck::class);

        $result = $this->doctor();

        $this->assertSame('pass', $this->row($result, 'zz_registered_throwing')['status']);
    }

    public function test_core_checks_are_not_run_twice_and_the_exit_code_is_unchanged(): void
    {
        AdminAlertRegistry::register(ZzWarningCheck::class);

        $result = $this->doctor();
        $names = array_column($result['data']['data']['checks'] ?? [], 'name');

        $this->assertSame(count($names), count(array_unique($names)));
        $this->assertNotContains('fail', array_column(array_filter($result['data']['data']['checks'], static fn ($c) => str_starts_with($c['name'], 'zz_')), 'status'));
        $this->assertSame(0, $result['code']);
    }

    public function test_the_seo_check_of_front_shows_up(): void
    {
        $this->assertNotNull($this->row($this->doctor(), 'seo_audit'));
    }

    private function doctor(): array
    {
        $buffer = new BufferedOutput();
        $code = Artisan::call('gp247:doctor', ['--json' => true], $buffer);

        return ['code' => $code, 'data' => json_decode(trim($buffer->fetch()), true) ?: []];
    }

    private function row(array $result, string $name): ?array
    {
        foreach ($result['data']['data']['checks'] ?? [] as $check) {
            if (($check['name'] ?? '') === $name) {
                return $check;
            }
        }

        return null;
    }
}

/** Test double: a registered check that warns. */
class ZzWarningCheck implements AdminAlertCheck
{
    public function key(): string
    {
        return 'zz_registered_warning';
    }

    public function ttl(): int
    {
        return 60;
    }

    public function evaluate(): AdminAlert
    {
        return AdminAlert::warning('zz_registered_warning', 'ZZ warning', 'zz detail', items: ['zz item']);
    }
}

/** Test double: a registered check that throws. */
class ZzThrowingCheck implements AdminAlertCheck
{
    public function key(): string
    {
        return 'zz_registered_throwing';
    }

    public function ttl(): int
    {
        return 60;
    }

    public function evaluate(): AdminAlert
    {
        throw new \RuntimeException('zz boom');
    }
}
