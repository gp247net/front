<?php

namespace GP247\Front\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class FrontUninstall extends Command
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
     * @return mixed
     */
    public function handle()
    {
        try {
            // Xóa dữ liệu từ bảng migrations
            DB::connection(GP247_DB_CONNECTION)
                ->table('migrations')
                ->where('migration', '00_00_00_create_tables_front')
                ->delete();

            // Gọi hàm down từ migration để drop tables
            $migration = require __DIR__.'/../DB/migrations/00_00_00_create_tables_front.php';
            $migration->down();
            
            $this->info('---------------> Uninstall Front module successfully!');
            return Command::SUCCESS;
            
        } catch (Throwable $e) {
            $this->error('Error uninstalling Front module: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 