<?php

namespace GP247\Front;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use GP247\Front\Middleware\CheckDomain;
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
            $this->loadViewsFrom(__DIR__.'/Views', 'gp247-front-admin');

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
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'check.domain'     => CheckDomain::class,
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
            $this->publishes([__DIR__.'/Views/template/public' => public_path('GP247/Templates/Default')], 'gp247:public-front-template');
            $this->publishes([__DIR__.'/Views/template/view' => app_path('GP247/Templates/Default')], 'gp247:view-front-template');
            $this->publishes([__DIR__.'/Views/admin' => resource_path('views/vendor/gp247-front')], 'gp247:view-front-admin');
        }
    }

    //Event register
    protected function eventRegister()
    {
        //
    }
}
