<?php

namespace GP247\Front\Middleware;

use Closure;
use GP247\Core\Admin\Models\AdminStore;

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

        if (gp247_store_check_multi_domain_installed() && gp247_config_global('domain_strict')) {
            //Check domain exist
            $domain = gp247_store_process_domain(url('/')); //domain currently
            $domainRoot = gp247_store_process_domain(config('app.url')); //Domain root config in .env
            $arrDomainPartner = AdminStore::getDomainPartner(); // List domain is partner active
            $arrDomainActive = AdminStore::getDomainStore(); // List domain is unlock domain

            if (gp247_store_check_multi_partner_installed()) {
                if (!in_array($domain, $arrDomainPartner) && $domain != $domainRoot) {
                    echo view('deny_domain')->render();
                    exit();
                }
            }

            if (gp247_store_check_multi_store_installed()) {
                if (!in_array($domain, $arrDomainActive) && $domain != $domainRoot) {
                    echo view('deny_domain')->render();
                    exit();
                }
            }
        }
        return $next($request);
    }
}
