<?php

namespace GP247\Front\Commands;

use Illuminate\Console\Command;
use Throwable;
use DB;
use Illuminate\Support\Facades\Storage;

class FrontInstall extends Command
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
     * @return mixed
     */
    public function handle()
    {
        // Uninstall gp247 front before install
        $this->call('gp247:front-uninstall');
        
        // Install gp247 front
        \DB::connection(GP247_DB_CONNECTION)->table('migrations')->where('migration', '00_00_00_create_tables_front')->delete();
        $this->call('migrate', ['--path' => '/vendor/gp247/front/src/DB/migrations/00_00_00_create_tables_front.php']);
        $this->info('---------------> Migrate schema Front default done!');

        $this->call('db:seed', ['--class' => '\GP247\Front\DB\seeders\DataFrontDefaultSeeder', '--force' => true]);
        $this->info('---------------> Seeding database Front default done!');

        $this->call('vendor:publish', ['--tag' => 'gp247:public-templates']);
        $this->call('vendor:publish', ['--tag' => 'gp247:view-templates']);

        $this->welcome();
    }

    private function welcome()
    {
        return Command::SUCCESS;
    }

}
