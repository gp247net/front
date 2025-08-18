<?php

namespace GP247\Front\Middleware;

use Closure;
use GP247\Core\Models\AdminStore;

class CheckActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (gp247_store_info('active') == '0') {
            if (auth()->guard('admin')->user()) {
                $view = 'GP247TemplatePath::' . gp247_store_info('template') . '.layout.maintenance_note';
                if (!view()->exists($view)) {
                    $this->_renderMaintenanceModeNote();
                } else {
                    echo view($view)->render();
                }
                return $next($request);
            } else {
                $view = 'GP247TemplatePath::' . gp247_store_info('template') . '.layout.maintenance_content';
                if (!view()->exists($view)) {
                    $this->_renderMaintenanceModeContent();
                } else {
                    echo view($view)->render();
                }
                exit();
            }
        }
        return $next($request);
    }

    private function _renderMaintenanceModeNote()
    {
        echo '<h1 style="color: #956b6b;
        text-align: center;
        font-size: 25px;
        background: #d3d3d3;
        padding: 5px !important;
        line-height: 30px;">Maintenance Mode</h1>';
    }

    private function _renderMaintenanceModeContent()
    {
        echo '<section>
        <div class="container">
          <div class="row">
            <div id="columns" class="container"  style="color:red;text-align: center;">
              '.gp247_store_info('maintain_content').'
            </div>
          </div>
        </div>
      </section>';
    }
}

