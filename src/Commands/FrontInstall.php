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
     * Publish tags an install runs, in order. Deliberately does NOT include
     * gp247:front-view (the Blade tree): copying it would freeze the site's
     * storefront at the installed version, which is exactly what
     * US-TPL-template-vendor-resident removes. Exposed as a constant so the
     * contract is assertable without executing a real publish.
     *
     * @var array<int, string>
     */
    public const PUBLISH_TAGS = [
        'gp247:front-public',
        'gp247:front-template',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gp247:front-install {--force=0 : Reinstall over existing front tables without asking (DESTROYS their data)}';

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
        // WHY before the uninstall: a site that already has front tables loses their data on a
        // reinstall, so it needs consent (--force=1 or "yes"); a fresh site is not asked (US-CLI-003).
        if (($stop = $this->guardReinstall('front_layout_block', 'front')) !== null) {
            return $stop;
        }

        // Uninstall gp247 front before install
        $this->runArtisan('gp247:front-uninstall');

        // Install gp247 front
        \DB::connection(GP247_DB_CONNECTION)->table('migrations')->where('migration', '00_00_00_create_tables_front')->delete();
        // WHY migrateForInstall(): --force so an APP_ENV=production site gets its tables back, and a
        // failed migration stops before the seeder writes into tables that are not there.
        if (($stop = $this->migrateForInstall('/vendor/gp247/front/src/Admin/Database/Migrations/00_00_00_create_tables_front.php')) !== null) {
            return $stop;
        }
        $this->info('---------------> Migrate schema Front default done!');

        $this->runArtisan('db:seed', ['--class' => '\GP247\Front\Admin\Database\Seeders\DataFrontDefaultSeeder', '--force' => true]);
        $this->info('---------------> Seeding database Front default done!');
        // WHY: front tables are new here, so their text is already stored as typed; without the
        // flag the first gp247:update would decode "&amp;" a user typed on purpose.
        \GP247\Core\Support\StoredTextDecoder::markStoredAsTyped('text_storage_decoded_front');
        // WHY: the base migration and the seeders already did what front's upgrade migrations do;
        // without rows the first gp247:update would run them all again (US-CLI-fresh-install-upgrade-baseline).
        $recorded = \GP247\Core\Support\UpgradeMigrationBaseline::markApplied(dirname(__DIR__) . '/Admin/Database/Migrations/upgrade');
        $this->info('---------------> Front upgrade migrations recorded: ' . count($recorded));

        //== Begin setup template default
        // Install the default template. Since modification 20260913T200309 this
        // publishes only its extension SHELL (AppConfig/Provider/Route/config/
        // function/gp247.json/Lang) plus the compiled public assets — the Blade
        // tree stays in the package and is served through the GP247TemplatePath
        // hint paths, so composer update keeps delivering template fixes. A site
        // that wants to edit a screen publishes that one file:
        //   php artisan gp247:template-publish GP247Front --file=screen/home.blade.php
        foreach (self::PUBLISH_TAGS as $tag) {
            $this->runArtisan('vendor:publish', ['--tag' => $tag, '--force' => true]);
        }

        //Setup template default for Root store
        // This command can only be run after the above default template copy command is successful.
        // If copying the above pattern fails, do it manually. Then run this command again.
        $this->runArtisan('gp247:template-setup');

        //== End setup template default

        return $this->respondSuccess(['installed' => true]);
    }
}
