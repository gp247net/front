<?php

namespace GP247\Front;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use GP247\Front\Middleware\CheckDomain;
use GP247\Front\Middleware\CheckActive;
use GP247\Front\Middleware\FrontRedirectMiddleware;
use GP247\Front\Commands\FrontInstall;
use GP247\Front\Commands\FrontUpdate;
use GP247\Front\Commands\FrontUninstall;
use GP247\Front\Commands\MakeTemplate;
use GP247\Front\Commands\TemplateSetup;
use GP247\Front\Commands\TemplatePublish;
use GP247\Front\Commands\TemplatePrune;

class FrontServiceProvider extends ServiceProvider
{
    /**
     * Entries (files/directories, relative to a template root) that make up a
     * template's extension SHELL: the part core needs on disk under app/ to
     * discover, activate and boot the template. Everything else in a template
     * is presentation and is served from the owning package unless the site
     * publishes it to override.
     *
     * Defined once in gp247/core (it is core's extension format) and mirrored
     * here so the publish map, the never-delete allowlist of
     * gp247:template-prune and gp247:doctor can never disagree — removing
     * AppConfig.php would make the template vanish from
     * gp247_extension_get_all_local() and take the live storefront with it.
     *
     * @var array<int, string>
     */
    public const TEMPLATE_SHELL_ENTRIES = \GP247\Core\Support\TemplateSourceAudit::SHELL_ENTRIES;

    protected function initial()
    {
        //Create directory
        try {
            if (!is_dir($directory = app_path('GP247/Front/Api'))) {
                mkdir($directory, 0777, true);
            }
            if (!is_dir($directory = app_path('GP247/Front/Controllers'))) {
                mkdir($directory, 0777, true);
            }
            if (!is_dir($directory = app_path('GP247/Front/Admin/Controllers'))) {
                mkdir($directory, 0777, true);
            }
            if (!is_dir($directory = app_path('GP247/Templates'))) {
                mkdir($directory, 0777, true);
            }
        } catch (\Throwable $e) {
            $msg = '#GP247-FRONT:: '.$e->getMessage().' - Line: '.$e->getLine().' - File: '.$e->getFile();
            echo $msg;
            exit;
        }

        // Shared, template-independent front-end libraries (e.g. sweetalert2,
        // used by <x-gp247-front::notice />) must not require every template
        // to carry its own copy — self-heal on every boot so no manual
        // vendor:publish step is needed for existing or brand-new templates.
        try {
            $this->ensureSharedAssetsPublished();
        } catch (\Throwable $e) {
            $msg = '#GP247-FRONT:: '.$e->getMessage().' - Line: '.$e->getLine().' - File: '.$e->getFile();
            echo $msg;
            exit;
        }

        // The default template's Blade is served straight from this package, but
        // a browser cannot read vendor/ — its compiled CSS/JS must exist under
        // public/. Self-heal it, gated on the template actually being installed
        // (modification 20260913T200309).
        try {
            $this->ensureTemplateAssetsPublished();
        } catch (\Throwable $e) {
            // WHY swallowed (unlike the block above): a read-only public/ on a
            // locked-down shared host must not take the whole site down — the
            // storefront still renders, only unstyled, and gp247:doctor reports it.
            gp247_report('#GP247-FRONT::template_assets:: '.$e->getMessage());
        }

        //Load publish
        try {
            $this->registerPublishing();
        } catch (\Throwable $e) {
            $msg = '#GP247-FRONT:: '.$e->getMessage().' - Line: '.$e->getLine().' - File: '.$e->getFile();
            echo $msg;
            exit;
        }

        try {
            $this->commands([
                FrontInstall::class,
                FrontUpdate::class,
                FrontUninstall::class,
                MakeTemplate::class,
                TemplateSetup::class,
                TemplatePublish::class,
                TemplatePrune::class,
            ]);
        } catch (\Throwable $e) {
            $msg = '#GP247-FRONT:: '.$e->getMessage().' - Line: '.$e->getLine().' - File: '.$e->getFile();
            gp247_report($msg);
            echo $msg;
            exit;
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

        $this->initial();

        if (function_exists('gp247_check_core_actived') && gp247_check_core_actived()) {

            //Load helper
            try {
                foreach (glob(__DIR__.'/Library/Helpers/*.php') as $filename) {
                    require_once $filename;
                }
            } catch (\Throwable $e) {
                $msg = '#GP247-FRONT:: '.$e->getMessage().' - Line: '.$e->getLine().' - File: '.$e->getFile();
                gp247_report($msg);
                echo $msg;
                exit;
            }

            //Boot process GP247
            try {
                $this->bootDefault();
            } catch (\Throwable $e) {
                $msg = '#GP247-FRONT:: '.$e->getMessage().' - Line: '.$e->getLine().' - File: '.$e->getFile();
                gp247_report($msg);
                echo $msg;
                exit;
            }

            // Template view resolution (US-TPL-template-vendor-resident, ADR
            // frontend-template-dev_template-vendor-resident-views): the namespace
            // carries SEVERAL hint paths and Laravel's FileViewFinder returns the
            // first file that exists, so resolution falls back PER FILE:
            //   app/GP247/Templates          -> published overrides + site templates
            //   <front>/Views/templates      -> this package's default template
            //   <shop>/Views/templates       -> appended by ShopServiceProvider
            // WHY the app path is registered first: a file the site published (to
            // edit it) must win over the package's copy.
            // WHY this is safe for other templates: the template name is a PATH
            // SEGMENT ("GP247TemplatePath::MyTheme.screen.home"), and the vendor
            // roots only contain a "GP247Front" directory — so a custom template can
            // never silently inherit (or be overwritten by) GP247Front's views.
            $this->loadViewsFrom(app_path().'/GP247/Templates', 'GP247TemplatePath');
            $this->loadViewsFrom(__DIR__.'/Views/templates', 'GP247TemplatePath');

            // Shared cross-cutting view components (US-TPL-008, ADR-013):
            // <x-gp247-front::language-switcher /> etc. resolve to a class in
            // TemplateComponents, whose default view lives under the
            // 'gp247-front' namespace registered here — same namespace, one
            // registration covers both the component tag and its fallback view.
            // Fallback view source is the package's own copy of the default
            // template — the same tree the GP247TemplatePath hint above serves and
            // that gp247:front-view publishes. Renamed Views/template/view ->
            // Views/front 2026-07-05 (modification 20260705T124936), then ->
            // Views/templates/GP247Front 2026-09-14 (modification 20260913T200309)
            // so the directory name IS the template name, which is what lets the
            // namespace hint above resolve "GP247Front.<view>" straight from vendor.
            Blade::componentNamespace('GP247\\Front\\TemplateComponents', 'gp247-front');
            $this->loadViewsFrom(__DIR__.'/Views/templates/GP247Front', 'gp247-front');

            // Modern admin (front-admin Unit, ADR-006/007): register the TailAdmin
            // Livewire screens that plug into the core admin shell. Additive and
            // reversible (strangler) — legacy AdminLTE front-admin views untouched.
            try {
                $this->registerAdminShell();
            } catch (\Throwable $e) {
                $msg = '#GP247-FRONT::admin-shell:: '.$e->getMessage().' - Line: '.$e->getLine().' - File: '.$e->getFile();
                gp247_report($msg);
                echo $msg;
                exit;
            }

            try {
                $this->registerRouteMiddleware();
            } catch (\Throwable $e) {
                $msg = '#GP247-FRONT:: '.$e->getMessage().' - Line: '.$e->getLine().' - File: '.$e->getFile();
                gp247_report($msg);
                echo $msg;
                exit;
            }

            try {
                $this->validationExtend();
            } catch (\Throwable $e) {
                $msg = '#GP247-FRONT:: '.$e->getMessage().' - Line: '.$e->getLine().' - File: '.$e->getFile();
                gp247_report($msg);
                echo $msg;
                exit;
            }

            //Load Template
            try {
                foreach (glob(app_path().'/GP247/Templates/*/Provider.php') as $filename) {
                    // WHY `require`: same reason as core's plugin loader — a template
                    // Provider.php is a top-level script and must run on EVERY application
                    // boot of the process (PHPUnit, Octane), not only the first one.
                    require $filename;
                }
                foreach (glob(app_path().'/GP247/Templates/*/Route.php') as $filename) {
                    $this->loadRoutesFrom($filename);
                }
            } catch (\Throwable $e) {
                $msg = '#GP247-FRONT::template_load:: '.$e->getMessage().' - Line: '.$e->getLine().' - File: '.$e->getFile();
                gp247_report($msg);
                echo $msg;
                exit;
            }



            $this->eventRegister();

            // Register the extension uninstall/disable guard for templates so both
            // the admin UI and the CLI (gp247:ext-*) refuse to remove a template a
            // store still uses or that is the default (ADR system-cli_service-extraction §5).
            // Runtime config append (same idiom as seo_sitemap_providers) — safe with
            // config:cache because it runs in boot() on every request/console call,
            // and keeps core free of any dependency on front (NFR-MAINT-001).
            config(['gp247-config.admin.extension.guards' => array_merge(
                (array) config('gp247-config.admin.extension.guards', []),
                [[\GP247\Front\Admin\ExtensionTemplateGuard::class, 'check']],
            )]);

        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/Config/config.php', 'gp247-config');
        if (file_exists(__DIR__.'/Library/Const.php')) {
            require_once(__DIR__.'/Library/Const.php');
        }
    }

    public function bootDefault()
    {

        view()->share('GP247TemplatePath', 'GP247TemplatePath::'.gp247_store_info('template'));
        view()->share('GP247TemplateFile', 'GP247/Templates/'.gp247_store_info('template'));
        view()->share('modelBanner', (new \GP247\Front\Models\FrontBanner));
        view()->share('modelPage', (new \GP247\Front\Models\FrontPage));
        view()->share('modelLink', (new \GP247\Front\Models\FrontLink));
    }

    /**
     * Register the modern (Livewire/TailAdmin) front-admin shell: the component
     * view namespace and the full-page routes inside the core admin group, so
     * they inherit admin auth + URI-based RBAC (Layer-1) without touching core
     * (front-admin Unit, ADR-006/007). Routes reference the Livewire component
     * classes by ::class (resolved to a string, no autoload), so registration is
     * safe even before every screen class exists.
     *
     * @return void
     */
    protected function registerAdminShell()
    {
        // `gp247-front-admin::` exposes the modern admin views (Views/admin,
        // renamed from admin-shell 2026-07-02 to free the bare 'gp247-front'
        // namespace for the US-TPL-008 storefront components, which had been
        // colliding with this one — see aidlc-docs ADR-013); class components
        // live under GP247\Front\Admin\Livewire (PSR-4). Reuses the core
        // `<x-gp247::*>` library + base DataTable/Form components.
        $this->loadViewsFrom(__DIR__.'/Views/admin', 'gp247-front-admin');

        // Cutover (PA-1): all front-admin screens are served at legacy URLs
        // (Routes/Admin/*.php), so the parallel front-admin/* routes are gone.
        // The registrar is kept only to declare the Livewire component namespace.
        \GP247\Core\AdminShell\Infrastructure\AdminShellResourceRegistrar::register(
            'gp247-front-admin',
            'GP247\\Front\\Admin\\Livewire',
            'front-admin',
            [],
        );
    }

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'check.domain'     => CheckDomain::class,
        'check.active'     => CheckActive::class,
        'front.redirect'   => FrontRedirectMiddleware::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected function middlewareGroups()
    {
        return [
            'front'        => config('gp247-config.front.middleware'),
        ];
    }

    /**
     * Register the route middleware.
     *
     * @return void
     */
    protected function registerRouteMiddleware()
    {
        // register route middleware.
        foreach ($this->routeMiddleware as $key => $middleware) {
            app('router')->aliasMiddleware($key, $middleware);
        }

        // register middleware group.
        foreach ($this->middlewareGroups() as $key => $middleware) {
            app('router')->middlewareGroup($key, array_values($middleware));
        }
    }

    /**
     * Validattion extend
     *
     * @return  [type]  [return description]
     */
    protected function validationExtend()
    {
        //
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            // WHY: 'Default' was removed entirely (modification 20260705T124936,
            // ADR-014 Amend #1) — GP247Front is now the sole/default template,
            // so this scaffold publishes to that folder name instead.
            $this->publishes([__DIR__.'/public' => public_path('GP247/Templates/GP247Front')], 'gp247:front-public');

            // Two tags instead of one (modification 20260913T200309):
            //   gp247:front-template -> the extension SHELL only. It must live under
            //     app/ because core discovers templates with glob(app_path()) +
            //     file_exists(.../AppConfig.php) and the class is PSR-4 under
            //     App\GP247\Templates\. This is what an install publishes.
            //   gp247:front-view     -> the Blade tree. OPT-IN: a site publishes it
            //     (or single files via gp247:template-publish) only to override.
            //     Anything not published keeps being served from this package, so
            //     composer update can finally deliver template fixes.
            $this->publishes($this->templateShellPublishMap(), 'gp247:front-template');
            $this->publishes([__DIR__.'/Views/templates/GP247Front' => app_path('GP247/Templates/GP247Front')], 'gp247:front-view');

            $this->publishes([__DIR__.'/Views/admin' => resource_path('views/vendor/gp247-front-admin')], 'gp247:front-admin');
            $this->publishes([__DIR__.'/public/js/sweetalert2.all.min.js' => public_path('GP247/Core/js/sweetalert2.all.min.js')], 'gp247:front-assets');
        }
    }

    /**
     * Build the publish map for the default template's extension shell — the
     * files that must physically exist under app/GP247/Templates/<Template>/ for
     * core to see the template at all (discovery globs app_path() and requires
     * AppConfig.php; the class is autoloaded as App\GP247\Templates\...).
     *
     * Everything NOT listed here (the Blade tree) is served from this package.
     *
     * @return array<string, string> Source path => published destination path.
     *
     * @aidlc-unit frontend-template-dev
     * @aidlc-story US-TPL-template-vendor-resident
     * @aidlc-adr frontend-template-dev_template-vendor-resident-views
     */
    protected function templateShellPublishMap(): array
    {
        $source = __DIR__.'/Views/templates/GP247Front';
        $target = app_path('GP247/Templates/GP247Front');

        $map = [];
        foreach (self::TEMPLATE_SHELL_ENTRIES as $entry) {
            $map[$source.'/'.$entry] = $target.'/'.$entry;
        }

        return $map;
    }

    /**
     * Ensure package-owned, template-independent front-end assets exist
     * under public/vendor/gp247-front — currently just sweetalert2, used by
     * the shared <x-gp247-front::notice /> component (ADR-013). A shared
     * component must not depend on any single template's own public/js
     * folder, so this copies the package's bundled copy into place on every
     * boot if it's missing, instead of relying solely on a manual
     * `vendor:publish --tag=gp247:front-assets` run.
     *
     * @aidlc-unit frontend-template-dev
     * @aidlc-story US-TPL-008
     * @aidlc-adr ADR-013
     *
     * @return void
     */
    protected function ensureSharedAssetsPublished()
    {
        $target = public_path('GP247/Core/js/sweetalert2.all.min.js');

        if (!file_exists($target)) {
            if (!is_dir($directory = dirname($target))) {
                mkdir($directory, 0777, true);
            }
            copy(__DIR__.'/public/js/sweetalert2.all.min.js', $target);
        }
    }

    /**
     * Copy this package's compiled storefront assets into public/ when they are
     * missing, so the default template works without a manual vendor:publish.
     *
     * Gated on app/GP247/Templates/GP247Front existing — that directory holds the
     * template's extension shell, so its presence means the site actually has the
     * template installed. A site that removed it (using another template) never
     * gets these files re-created, which is the whole point of
     * RISK-OPS-template-resurrection.
     *
     * WHY a filesystem marker instead of reading the store's active template:
     * this runs on every boot, including before install and on CLI, where the
     * database may not be reachable at all.
     *
     * @return void
     *
     * @aidlc-unit frontend-template-dev
     * @aidlc-story US-TPL-template-vendor-resident
     * @aidlc-adr frontend-template-dev_template-vendor-resident-views
     */
    protected function ensureTemplateAssetsPublished()
    {
        if (!is_dir(app_path('GP247/Templates/GP247Front'))) {
            return;
        }

        $this->syncTemplateAssets(__DIR__.'/public', public_path('GP247/Templates/GP247Front'));
    }

    /**
     * Mirror a package's compiled asset folder into public/, refreshing it when
     * the package ships a new build.
     *
     * WHY refresh and not just "copy when missing" (modification 20260913T200309,
     * follow-up): the Blade of the template now arrives with `composer update`,
     * but a browser cannot read vendor/, so the stylesheet has to be copied out.
     * Copying only when absent left every existing site on its old CSS — new
     * markup shipped by the package, no matching rules in the bundle, elements
     * silently falling back to default styling. Delivery of the two halves has to
     * be symmetric or the asymmetry itself becomes the bug.
     *
     * WHY a stamp file instead of hashing on every boot: this runs on every
     * request. The signature is the size+mtime of the built stylesheet — two
     * stat() calls and a tiny read in the steady state, which a shared host can
     * afford (NFR-AVAIL-cli-shared-host). Composer rewrites mtime when it updates
     * the package, which is exactly the event we need to notice.
     *
     * @param string $source Absolute path of the package's public/ folder.
     * @param string $target Absolute path under the app's public/ folder.
     * @return bool True when files were (re)copied.
     *
     * @aidlc-unit frontend-template-dev
     * @aidlc-story US-TPL-template-vendor-resident
     * @aidlc-adr frontend-template-dev_template-vendor-resident-views
     */
    protected function syncTemplateAssets(string $source, string $target): bool
    {
        $built = $source.'/css/app.css';
        if (!is_file($built)) {
            return false;
        }

        $signature = filesize($built).'-'.filemtime($built);
        $stamp = $target.'/.gp247-assets';

        if (is_file($stamp) && trim((string) file_get_contents($stamp)) === $signature) {
            return false;
        }

        $this->copyDirectory($source, $target, true);

        if (is_dir($target)) {
            // WHY the write may fail silently: on a read-only public/ the copies
            // above failed too, and the site still renders (unstyled) — that is a
            // gp247:doctor finding, not a reason to abort the boot.
            @file_put_contents($stamp, $signature);
        }

        return true;
    }

    /**
     * Recursively copy a directory, creating missing parents.
     *
     * @param string $source    Absolute source directory.
     * @param string $target    Absolute destination directory.
     * @param bool   $overwrite Replace files that already exist (default: keep them).
     * @return void
     *
     * @aidlc-unit frontend-template-dev
     * @aidlc-story US-TPL-template-vendor-resident
     */
    protected function copyDirectory(string $source, string $target, bool $overwrite = false)
    {
        if (!is_dir($source)) {
            return;
        }

        if (!is_dir($target)) {
            mkdir($target, 0777, true);
        }

        foreach (scandir($source) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $from = $source.'/'.$entry;
            $to = $target.'/'.$entry;

            if (is_dir($from)) {
                $this->copyDirectory($from, $to, $overwrite);
            } elseif ($overwrite || !file_exists($to)) {
                @copy($from, $to);
            }
        }
    }

    //Event register
    protected function eventRegister()
    {
        //
    }
}
