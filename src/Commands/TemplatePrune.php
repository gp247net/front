<?php

namespace GP247\Front\Commands;

use GP247\Core\Console\GP247Command;
use GP247\Front\FrontServiceProvider;

/**
 * Remove published template files that are byte-for-byte identical to the copy
 * the package ships, so the site goes back to receiving them through
 * `composer update`.
 *
 * This is the migration path for every site installed before modification
 * 20260913T200309: their app/GP247/Templates/<Template>/ holds a full copy of the
 * template, which shadows the package for ever. Deleting only the untouched files
 * hands those back to the package while leaving every edit in place.
 *
 * Safety is the whole design (RISK-OPS-template-prune-dataloss):
 *  - compares CONTENT, never names or timestamps;
 *  - keeps anything the packages do not provide (a plugin's block, a site's own file);
 *  - never touches the extension shell, without which the template disappears.
 *
 * @aidlc-unit system-cli
 * @aidlc-story US-CLI-template-source-lifecycle
 * @aidlc-adr frontend-template-dev_template-vendor-resident-views
 * @aidlc-adr system-cli_output-contract
 */
class TemplatePrune extends GP247Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gp247:template-prune
        {template : Template name, e.g. GP247Front}
        {--dry-run : List what would be deleted and kept, without touching anything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete published template files identical to the package copy (keeps everything you edited)';

    /**
     * Execute the console command.
     *
     * @return int Exit code.
     */
    protected function handleGp247(): int
    {
        $template = (string) $this->argument('template');
        $base = app_path('GP247/Templates/'.$template);

        if (!is_dir($base)) {
            return $this->respondFailure('template_not_published', 'Nothing to prune: '.$base.' does not exist.');
        }

        $sources = $this->packageFiles($template);
        if (empty($sources)) {
            return $this->respondFailure(
                'template_source_missing',
                'No package provides views for template "'.$template.'" — refusing to prune a template nothing can serve.'
            );
        }

        $plan = $this->buildPlan($base, $sources);

        if (empty($plan['delete'])) {
            $this->infoHuman('Nothing to prune — every published file differs from the package copy or is yours.');

            return $this->respondSuccess([
                'template'     => $template,
                'dry_run'      => (bool) $this->option('dry-run'),
                'deleted'      => [],
                'kept_edited'  => $plan['kept_edited'],
                'kept_unknown' => $plan['kept_unknown'],
            ]);
        }

        $this->reportPlan($plan);

        if ($this->option('dry-run')) {
            return $this->respondSuccess([
                'template'     => $template,
                'dry_run'      => true,
                'deleted'      => [],
                'would_delete' => $plan['delete'],
                'kept_edited'  => $plan['kept_edited'],
                'kept_unknown' => $plan['kept_unknown'],
            ]);
        }

        // WHY confirm here and not a --force flag: this deletes files from a live
        // site. An interactive operator gets a default-no prompt; automation that
        // asked for it non-interactively proceeds (same consent model as
        // gp247:update --publish).
        if (!$this->isJson() && $this->input->isInteractive()) {
            if (!$this->confirm('Delete '.count($plan['delete']).' unmodified file(s)?', false)) {
                $this->infoHuman('Canceled — nothing was deleted.');

                return $this->respondSuccess([
                    'template' => $template,
                    'canceled' => true,
                    'deleted'  => [],
                ]);
            }
        }

        $deleted = [];
        foreach ($plan['delete'] as $relative) {
            if (@unlink($base.'/'.$relative)) {
                $deleted[] = $relative;
            } else {
                $this->addWarning('Could not delete '.$relative.' (check permissions).');
            }
        }

        $this->removeEmptyDirectories($base);

        $this->infoHuman('Deleted '.count($deleted).' file(s); kept '.count($plan['kept_edited']).' you edited and '.count($plan['kept_unknown']).' not provided by any package.');

        return $this->respondSuccess([
            'template'     => $template,
            'dry_run'      => false,
            'deleted'      => $deleted,
            'kept_edited'  => $plan['kept_edited'],
            'kept_unknown' => $plan['kept_unknown'],
        ]);
    }

    /**
     * Decide, for every published file, whether it can be handed back.
     *
     * @param string                $base    Absolute path of the published template directory.
     * @param array<string, string> $sources Relative path => absolute package path.
     * @return array{delete: array<int,string>, kept_edited: array<int,string>, kept_unknown: array<int,string>, kept_shell: array<int,string>}
     */
    protected function buildPlan(string $base, array $sources): array
    {
        $plan = ['delete' => [], 'kept_edited' => [], 'kept_unknown' => [], 'kept_shell' => []];

        foreach ($this->walk($base) as $absolute) {
            $relative = ltrim(str_replace('\\', '/', substr($absolute, strlen($base) + 1)), '/');

            if ($this->isShellEntry($relative)) {
                $plan['kept_shell'][] = $relative;
                continue;
            }

            if (!isset($sources[$relative])) {
                $plan['kept_unknown'][] = $relative;
                continue;
            }

            if ($this->sameContent($absolute, $sources[$relative])) {
                $plan['delete'][] = $relative;
            } else {
                $plan['kept_edited'][] = $relative;
            }
        }

        sort($plan['delete']);
        sort($plan['kept_edited']);
        sort($plan['kept_unknown']);
        sort($plan['kept_shell']);

        return $plan;
    }

    /**
     * Print the human-readable plan (skipped entirely in JSON mode).
     *
     * @param array{delete: array<int,string>, kept_edited: array<int,string>, kept_unknown: array<int,string>, kept_shell: array<int,string>} $plan
     * @return void
     */
    protected function reportPlan(array $plan): void
    {
        $this->infoHuman('Unmodified (can be served by the package again): '.count($plan['delete']));

        if (!empty($plan['kept_edited'])) {
            $this->infoHuman('Kept because YOU edited them:');
            foreach ($plan['kept_edited'] as $relative) {
                $this->infoHuman('  - '.$relative);
            }
        }
        if (!empty($plan['kept_unknown'])) {
            $this->infoHuman('Kept because no package provides them (your own files, plugin blocks): '.count($plan['kept_unknown']));
        }
        if (!empty($plan['kept_shell'])) {
            $this->infoHuman('Kept because they are the template shell: '.count($plan['kept_shell']));
        }
    }

    /**
     * Write a human-readable line, unless --json is in effect.
     *
     * WHY: in JSON mode STDOUT must carry exactly one envelope and nothing else,
     * or piping into jq breaks (ADR system-cli_output-contract).
     *
     * @param string $message Line to print.
     * @return void
     */
    protected function infoHuman(string $message): void
    {
        if (!$this->isJson()) {
            $this->info($message);
        }
    }

    /**
     * Files the packages provide for this template.
     *
     * @param string $template Template name.
     * @return array<string, string> Relative path => absolute source path.
     */
    protected function packageFiles(string $template): array
    {
        $files = [];

        // WHY false: the app/ root is what we are pruning — comparing it with
        // itself would mark every file identical and delete the lot.
        foreach (gp247_template_source_roots(false) as $root) {
            $directory = $root.'/'.$template;
            if (!is_dir($directory)) {
                continue;
            }

            foreach ($this->walk($directory) as $absolute) {
                $relative = ltrim(str_replace('\\', '/', substr($absolute, strlen($directory) + 1)), '/');
                if (!array_key_exists($relative, $files)) {
                    $files[$relative] = $absolute;
                }
            }
        }

        return $files;
    }

    /**
     * Whether a relative path belongs to the template's extension shell, which
     * must stay on disk for core to see the template at all.
     *
     * @param string $relative Relative path inside the template directory.
     * @return bool
     */
    protected function isShellEntry(string $relative): bool
    {
        return \GP247\Core\Support\TemplateSourceAudit::isShellEntry($relative);
    }

    /**
     * Compare two files by content.
     *
     * WHY hashes and not filesize/mtime: publishing copies files, so timestamps
     * differ on every install, and a one-character edit keeps the size identical.
     *
     * @param string $a Absolute path.
     * @param string $b Absolute path.
     * @return bool
     */
    protected function sameContent(string $a, string $b): bool
    {
        $hashA = @hash_file('sha256', $a);
        $hashB = @hash_file('sha256', $b);

        return $hashA !== false && $hashB !== false && $hashA === $hashB;
    }

    /**
     * List every file under a directory, recursively.
     *
     * @param string $directory Absolute directory path.
     * @return array<int, string> Absolute file paths.
     */
    protected function walk(string $directory): array
    {
        $found = [];

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory.'/'.$entry;
            if (is_dir($path)) {
                $found = array_merge($found, $this->walk($path));
            } else {
                $found[] = $path;
            }
        }

        return $found;
    }

    /**
     * Delete directories left empty by the prune, depth first. The template root
     * itself is never removed.
     *
     * @param string $directory Absolute directory path.
     * @return bool True when $directory is now empty (and was removed if nested).
     */
    protected function removeEmptyDirectories(string $directory): bool
    {
        foreach (array_diff(scandir($directory) ?: [], ['.', '..']) as $entry) {
            $path = $directory.'/'.$entry;
            if (is_dir($path) && $this->removeEmptyDirectories($path)) {
                @rmdir($path);
            }
        }

        return empty(array_diff(scandir($directory) ?: [], ['.', '..']));
    }
}
