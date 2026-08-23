<?php

namespace GP247\Front\Commands;

use GP247\Core\Console\GP247Command;

/**
 * Install the GP247 front (storefront UI) module: uninstall the old front,
 * recreate tables, seed defaults, publish assets/views, set up the default
 * template for the root store.
 *
 * @aidlc-unit system-cli
 * @aidlc-story US-CLI-005
 * @aidlc-adr system-cli_output-contract
 */
class FrontInstall extends GP247Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gp247:front-install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'GP247 front install';

    /**
     * Execute the console command.
     *
     * @return int Exit code.
     */
    protected function handleGp247(): int
    {
        // Uninstall gp247 front before install
        $this->runArtisan('gp247:front-uninstall');

        // Install gp247 front
        \DB::connection(GP247_DB_CONNECTION)->table('migrations')->where('migration', '00_00_00_create_tables_front')->delete();
        $this->runArtisan('migrate', ['--path' => '/vendor/gp247/front/src/Admin/Database/Migrations/00_00_00_create_tables_front.php']);
        $this->info('---------------> Migrate schema Front default done!');

        $this->runArtisan('db:seed', ['--class' => '\GP247\Front\Admin\Database\Seeders\DataFrontDefaultSeeder', '--force' => true]);
        $this->info('---------------> Seeding database Front default done!');

        //== Begin setup template default
        // Copy template default
        $this->runArtisan('vendor:publish', ['--tag' => 'gp247:front-public','--force' => true]);
        $this->runArtisan('vendor:publish', ['--tag' => 'gp247:front-view','--force' => true]);

        //Setup template default for Root store
        // This command can only be run after the above default template copy command is successful.
        // If copying the above pattern fails, do it manually. Then run this command again.
        $this->runArtisan('gp247:template-setup');

        //== End setup template default

        return $this->respondSuccess(['installed' => true]);
    }
}
