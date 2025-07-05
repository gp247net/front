<?php

namespace GP247\Front\Middleware;

use Closure;
use GP247\Core\Models\AdminStore;

class CheckDomain
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

        if (gp247_config_global('domain_strict')) {
            $this->processDomainAllow();
        }
        return $next($request);
    }

    private function processDomainAllow()
    {
        $domain = gp247_store_process_domain(url('/')); //domain currently
        $domainRoot = gp247_store_process_domain(config('app.url')); //Domain root config in .env
        $arrDomainAllow = [];
        if (gp247_store_check_multi_partner_installed()) {
            $arrDomainAllow = AdminStore::getDomainPartner(); // List domain is partner active
        }
        if (gp247_store_check_multi_store_installed()) {
            $arrDomainAllow = AdminStore::getDomainStore(); // List domain is partner active
        }
        if (!in_array($domain, $arrDomainAllow) && $domain != $domainRoot) {
            // Check if view file exists before rendering
            if (!view()->exists('deny_domain')) {
                // If view doesn't exist, show a simple error message
                echo '<h1>Access Denied</h1>';
                echo '<p>This domain is not authorized to access this application.</p>';
                exit();
            }
            echo view('deny_domain')->render();
            exit();
        }
    }
}

