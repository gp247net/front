<?php

namespace GP247\Front\Commands;

use GP247\Core\Console\GP247Command;
use Throwable;

/**
 * Non-destructive upgrade for an existing GP247 front (CMS) install.
 *
 * Mirrors gp247:shop-update. Unlike gp247:front-install (which drops and
 * recreates every front table), this command applies only the incremental,
 * idempotent upgrade migrations by --path so a live site keeps its data. It is
 * the front counterpart the ecosystem previously lacked — created so the store
 * 1-1 standardization (modification 20260828T090911) can reach already-installed
 * front schemas via gp247:update (core UpdateAll).
 *
 * Then runs the front language seeder (insertOrIgnore) so labels added since the
 * install reach the site without an upgrade migration of their own.
 *
 * @aidlc-unit compat-foundation
 * @aidlc-story US-CLI-005, US-CLI-upgrade-migration-delivery, US-CMP-language-rows-via-seeder
 * @aidlc-adr system-cli_output-contract, multi-store_one-to-one-store-ownership, compat-foundation_public-release-migration-policy
 */
class FrontUpdate extends GP247Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gp247:front-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Non-destructive upgrade of the GP247 front schema for an existing install';

    /**
     * Execute the console command.
     *
     * @return int Exit code.
     */
    protected function handleGp247(): int
    {
        try {
            // WHY: run only the upgrade/ folder, never the sibling create-tables
            // migration which would wipe the database.
            $this->runArtisan('migrate', [
                '--path'  => '/vendor/gp247/front/src/Admin/Database/Migrations/upgrade',
                '--force' => true,
            ]);
            $this->info('---------------> Front upgrade migrations done!');
        } catch (Throwable $e) {
            gp247_report($e->getMessage());
            return $this->respondFailure('upgrade_failed', 'Front upgrade failed: '.$e->getMessage());
        }

        // WHY after the migrations: new labels ship in the language seeder, not in upgrade
        // migrations; insertOrIgnore adds the missing ones and keeps a translation the site
        // owner edited (US-CMP-language-rows-via-seeder).
        try {
            $this->runArtisan('db:seed', [
                '--class' => '\GP247\Front\Admin\Database\Seeders\DataFrontLanguageSeeder',
                '--force' => true,
            ]);
            $this->info('---------------> Front language rows done!');
        } catch (Throwable $e) {
            gp247_report($e->getMessage());
            return $this->respondFailure('seed_failed', 'Front language rows failed: '.$e->getMessage());
        }

        return $this->respondSuccess([
            'upgraded' => true,
            'languages_seeded' => true,
        ]);
    }
}
