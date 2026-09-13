<?php

namespace GP247\Front\Commands;

use GP247\Core\Console\GP247Command;
use GP247\Front\FrontServiceProvider;

/**
 * Copy files of a template from the package that ships them into
 * app/GP247/Templates/<Template>/, so the site can edit them.
 *
 * Since modification 20260913T200309 a template's Blade is served straight from
 * the packages through the GP247TemplatePath hint paths; publishing is how a site
 * opts a file OUT of that and into its own hands. Publishing one file keeps every
 * other file receiving updates from composer — which is why this exists next to
 * `vendor:publish --tag=gp247:front-view`, which takes the whole tree.
 *
 * @aidlc-unit system-cli
 * @aidlc-story US-CLI-template-source-lifecycle
 * @aidlc-adr frontend-template-dev_template-vendor-resident-views
 * @aidlc-adr system-cli_output-contract
 */
class TemplatePublish extends GP247Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gp247:template-publish
        {template : Template name, e.g. GP247Front}
        {--file= : Publish a single file, path relative to the template root (e.g. screen/home.blade.php)}
        {--all : Publish every file the packages provide for this template}
        {--force : Overwrite files that already exist in app/GP247/Templates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy template files from their package into app/GP247/Templates so you can edit them';

    /**
     * Execute the console command.
     *
     * @return int Exit code.
     */
    protected function handleGp247(): int
    {
        $template = (string) $this->argument('template');
        $file = $this->option('file');
        $file = is_string($file) ? trim($file) : '';
        $all = (bool) $this->option('all');

        if ($file === '' && !$all) {
            return $this->respondFailure(
                'target_required',
                'Nothing to publish: pass --file=<relative/path.blade.php> for one file, or --all for the whole template.'
            );
        }

        // WHY package roots only: the app/ root is the DESTINATION. Including it
        // would let a file "publish" onto itself.
        $roots = gp247_template_source_roots(false);
        if (empty($roots)) {
            return $this->respondFailure('template_source_missing', 'No package provides views for template "'.$template.'".');
        }

        $available = $this->collectSourceFiles($roots, $template);
        if (empty($available)) {
            return $this->respondFailure(
                'template_source_missing',
                'No package provides views for template "'.$template.'". Check the name (it is case-sensitive).'
            );
        }

        $targets = $all ? array_keys($available) : [$this->normalizeRelativePath($file)];

        $target = app_path('GP247/Templates/'.$template);
        $published = [];
        $skipped = [];

        foreach ($targets as $relative) {
            if (!isset($available[$relative])) {
                return $this->respondFailure(
                    'template_file_not_found',
                    'File "'.$relative.'" is not provided by any package for template "'.$template.'".',
                    ['suggestions' => $this->suggest($relative, array_keys($available))]
                );
            }

            $destination = $target.'/'.$relative;
            if (file_exists($destination) && !$this->option('force')) {
                // WHY skip rather than fail: with --all this is the normal case for
                // a site that already published a few files, and overwriting them
                // would destroy exactly the edits publishing was meant to protect.
                $skipped[] = $relative;
                continue;
            }

            if (!$this->copyFile($available[$relative], $destination)) {
                $this->addWarning('Could not write '.$relative.' (check write permissions on app/GP247/Templates).');
                continue;
            }

            $published[] = $relative;
        }

        // WHY guarded: --json must print exactly one envelope on STDOUT so the
        // output stays pipeable (ADR system-cli_output-contract).
        if (!$this->isJson()) {
            foreach ($published as $relative) {
                $this->info('Published: '.$relative);
            }
        }
        if (!empty($skipped)) {
            $this->addWarning(count($skipped).' file(s) already exist and were kept (use --force to overwrite).');
        }

        return $this->respondSuccess([
            'template'  => $template,
            'published' => $published,
            'skipped'   => $skipped,
        ]);
    }

    /**
     * Index every file the packages provide for a template.
     *
     * @param array<int, string> $roots    Package source roots, highest priority first.
     * @param string             $template Template name.
     * @return array<string, string> Relative path => absolute source path (first root wins).
     */
    protected function collectSourceFiles(array $roots, string $template): array
    {
        $files = [];

        foreach ($roots as $root) {
            $base = $root.'/'.$template;
            if (!is_dir($base)) {
                continue;
            }

            foreach ($this->walk($base) as $absolute) {
                $relative = $this->normalizeRelativePath(substr($absolute, strlen($base) + 1));
                if (!array_key_exists($relative, $files)) {
                    $files[$relative] = $absolute;
                }
            }
        }

        ksort($files);

        return $files;
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
     * Copy one file, creating parent directories.
     *
     * @param string $source      Absolute source path.
     * @param string $destination Absolute destination path.
     * @return bool True when the file was written.
     */
    protected function copyFile(string $source, string $destination): bool
    {
        $directory = dirname($destination);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            return false;
        }

        return (bool) @copy($source, $destination);
    }

    /**
     * Normalize a user-supplied relative path to forward slashes without a
     * leading separator, so Windows input and glob output compare equal.
     *
     * @param string $path Relative path.
     * @return string
     */
    protected function normalizeRelativePath(string $path): string
    {
        return ltrim(str_replace('\\', '/', $path), '/');
    }

    /**
     * Suggest close matches for a path the user got slightly wrong.
     *
     * @param string            $wanted    The requested relative path.
     * @param array<int,string> $available Every available relative path.
     * @return array<int, string> At most 5 suggestions.
     */
    protected function suggest(string $wanted, array $available): array
    {
        $needle = strtolower(basename($wanted));

        $matches = array_values(array_filter(
            $available,
            static fn (string $path): bool => str_contains(strtolower($path), $needle)
        ));

        return array_slice($matches, 0, 5);
    }
}
