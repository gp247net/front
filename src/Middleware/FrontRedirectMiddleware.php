<?php

namespace GP247\Front\Middleware;

use Closure;
use GP247\Front\Models\FrontRedirect;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Intercepts requests matching an active FrontRedirect rule and issues the
 * configured HTTP redirect (301 or 302) before the route handler runs.
 *
 * Must be registered in the 'front' middleware group (via FrontServiceProvider)
 * to execute before the catch-all {alias}.html route (US-SEO-006).
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-006
 */
class FrontRedirectMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-006
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = '/' . ltrim($request->getPathInfo(), '/');

        $redirect = FrontRedirect::findActive($path);

        if ($redirect !== null) {
            return redirect($redirect->to, $redirect->code);
        }

        return $next($request);
    }
}
