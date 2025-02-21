<?php

namespace GP247\Front\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Exception;
use GP247\Core\Models\AdminConfig;
use Illuminate\Support\Facades\Log;

class TemplateSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gp247:template-setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup template for GP247 store';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $classTemplate = '\App\GP247\Templates\\' . GP247_TEMPLATE_FRONT_DEFAULT . '\AppConfig';

        if (!class_exists($classTemplate)) {
            $this->info('Class template Default not found');
        } else {
            $classTemplate = new $classTemplate();
            $classTemplate->install();
            $this->info('---------------> Setup template default done!');
        }
    }
} 