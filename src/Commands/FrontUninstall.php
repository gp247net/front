<?php

namespace GP247\Front\Commands;

use GP247\Core\Console\GP247Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Uninstall the GP247 front module: drop the front tables and remove the
 * migration record.
 *
 * @aidlc-unit system-cli
 * @aidlc-story US-CLI-005
 * @aidlc-adr system-cli_output-contract
 */
class FrontUninstall extends GP247Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gp247:front-uninstall';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'GP247 front uninstall';

    /**
     * Execute the console command.
     *
     * @return int Exit code.
     */
    protected function handleGp247(): int
    {
        try {
            // Remove the migration record
            DB::connection(GP247_DB_CONNECTION)
                ->table('migrations')
                ->where('migration', '00_00_00_create_tables_front')
                ->delete();

            // Call the migration's down() to drop tables
            $migration = require __DIR__.'/../Admin/Database/Migrations/00_00_00_create_tables_front.php';
            $migration->down();

            $this->info('---------------> Uninstall Front module successfully!');
            return $this->respondSuccess(['uninstalled' => true]);
        } catch (Throwable $e) {
            gp247_report($e->getMessage());
            return $this->respondFailure('uninstall_failed', 'Error uninstalling Front module: '.$e->getMessage());
        }
    }
}
