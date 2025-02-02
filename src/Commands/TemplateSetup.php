<?php

namespace GP247\Front\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Exception;
use GP247\Core\Admin\Models\AdminConfig;
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
        // Check class template Default
        $classTemplate = gp247_extension_get_class_config(type:'Templates', key:GP247_TEMPLATE_FRONT_DEFAULT);
        if (!$classTemplate) {
            throw new Exception('Class template Default not found');
        }
        $classTemplate = new $classTemplate();
        $classTemplate->install();
        $this->info('---------------> Setup template default done!');
    }
} 