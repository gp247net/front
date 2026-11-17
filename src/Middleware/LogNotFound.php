<?php

namespace GP247\Front\Middleware;

use Closure;
use GP247\Front\Library\NotFoundLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global middleware: records storefront 404 responses in the 404 log when the store
 * switched it on. Global (not in the `front` group) so addresses that match no route
 * are logged too — router 404s never reach route middleware. Never changes the
 * response and never fails the request.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-not-found-log
 * @aidlc-adr ADR-seo-url-lifecycle
 */
class LogNotFound
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() !== 404) {
            return $response;
        }
        try {
            $storeId = config('app.storeId');
            if ($storeId !== null && NotFoundLog::shouldRecord($request) && NotFoundLog::enabled($storeId)) {
                NotFoundLog::record($storeId, $request->getPathInfo(), $request->headers->get('referer'));
            }
        } catch (\Throwable $e) {
            Log::warning('404 log: could not record', ['error' => $e->getMessage()]);
        }

        return $response;
    }
}
