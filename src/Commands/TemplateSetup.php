<?php

namespace GP247\Front\Commands;

use GP247\Core\Console\GP247Command;

/**
 * Set up the default template for the root store: load the default template's
 * AppConfig and run install() + setupStore().
 *
 * @aidlc-unit system-cli
 * @aidlc-story US-CLI-005
 * @aidlc-adr system-cli_output-contract
 */
class TemplateSetup extends GP247Command
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
     * @return int Exit code.
     */
    protected function handleGp247(): int
    {
        $classTemplate = '\App\GP247\Templates\\' . GP247_TEMPLATE_FRONT_DEFAULT . '\AppConfig';

        if (!class_exists($classTemplate)) {
            // WHY: a missing default template is an operational error worth a
            // non-zero exit so automation notices, not a silent success.
            return $this->respondFailure('template_not_found', 'Class template Default not found');
        }

        $classTemplate = new $classTemplate();
        $classTemplate->install();
        $classTemplate->setupStore(GP247_STORE_ID_ROOT);
        $this->info('---------------> Setup template default done!');

        return $this->respondSuccess(['template' => GP247_TEMPLATE_FRONT_DEFAULT]);
    }
}
