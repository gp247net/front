<?php

namespace GP247\Front\Middleware;

use Closure;
use GP247\Front\Models\FrontRedirect;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global middleware: a storefront GET/HEAD answered with 404 is checked against the
 * redirects of the store. FrontRedirectMiddleware sits in the `front` route group, so
 * it never sees an address that matches no route — old links of another platform
 * (`/products/a`, `/product/a/`) or paths taken from the 404 log. Costs one query
 * per 404 response only; never fails the request.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-006, US-SEO-legacy-path-redirect
 * @aidlc-adr ADR-seo-url-lifecycle
 */
class RedirectOnNotFound
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return Response The 404, or the stored redirect of its path.
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-006, US-SEO-legacy-path-redirect
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() !== 404 || !in_array($request->getMethod(), ['GET', 'HEAD'], true) || !self::isStorefrontPath($request)) {
            return $response;
        }
        try {
            $redirect = FrontRedirect::findActive('/' . ltrim($request->getPathInfo(), '/'));
            if ($redirect !== null) {
                return redirect($redirect->to, (int) $redirect->code);
            }
        } catch (\Throwable $e) {
            // WHY: front tables not installed yet must not fail the 404 page; the report keeps the cause.
            gp247_report('Redirect lookup on 404 failed: ' . $e->getMessage());
        }

        return $response;
    }

    /**
     * @param Request $request
     * @return bool False for the admin area and the APIs.
     */
    private static function isStorefrontPath(Request $request): bool
    {
        $path = strtolower(trim($request->getPathInfo(), '/'));
        $admin = strtolower(trim(defined('GP247_ADMIN_PREFIX') ? (string) GP247_ADMIN_PREFIX : 'gp247_admin', '/'));
        $inside = static fn (string $prefix) => $prefix !== '' && ($path === $prefix || str_starts_with($path, $prefix . '/'));

        return !$inside('api') && !$inside($admin);
    }
}
