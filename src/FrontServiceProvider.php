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
use GP247\Front\Commands\FrontUninstall;
use GP247\Front\Commands\MakeTemplate;
use GP247\Front\Commands\TemplateSetup;

class FrontServiceProvider extends ServiceProvider
{

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
                FrontUninstall::class,
                MakeTemplate::class,
                TemplateSetup::class,
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

            $this->loadViewsFrom(app_path().'/GP247/Templates', 'GP247TemplatePath');

            // Shared cross-cutting view components (US-TPL-008, ADR-013):
            // <x-gp247-front::language-switcher /> etc. resolve to a class in
            // TemplateComponents, whose default view lives under the
            // 'gp247-front' namespace registered here — same namespace, one
            // registration covers both the component tag and its fallback view.
            // Fallback view source is Views/front (the same tree published to
            // GP247Front via gp247:front-view) — merged 2026-07-02 from the
            // former Views/front to keep one canonical source instead of two drifting
            // copies (see modification 20260702T123000). Renamed from
            // Views/template/view 2026-07-05 (modification 20260705T124936) to
            // match gp247/shop's Views/front naming.
            Blade::componentNamespace('GP247\\Front\\TemplateComponents', 'gp247-front');
            $this->loadViewsFrom(__DIR__.'/Views/front', 'gp247-front');

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
                    require_once $filename;
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
            // so this scaffold publishes to that folder name instead. Source
            // paths renamed from Views/template/{view,public} to Views/front
            // and public 2026-07-05 (same modification) to match gp247/shop's
            // Views/front naming.
            $this->publishes([__DIR__.'/public' => public_path('GP247/Templates/GP247Front')], 'gp247:front-public');
            $this->publishes([__DIR__.'/Views/front' => app_path('GP247/Templates/GP247Front')], 'gp247:front-view');
            $this->publishes([__DIR__.'/Views/admin' => resource_path('views/vendor/gp247-front-admin')], 'gp247:front-admin');
            $this->publishes([__DIR__.'/public/js/sweetalert2.all.min.js' => public_path('GP247/Core/js/sweetalert2.all.min.js')], 'gp247:front-assets');
        }
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

    //Event register
    protected function eventRegister()
    {
        //
    }
}
