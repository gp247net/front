<?php

namespace GP247\Front\Support;

use GP247\Core\AdminShell\Domain\AdminAlertCheck;
use GP247\Core\AdminShell\Infrastructure\AdminAlert;
use GP247\Front\Library\SeoAudit;
use Illuminate\Support\Facades\Route;

/**
 * "System alerts" / gp247:doctor row `seo_audit`: a summary of the last SEO check of the
 * current store. Reads the kept result only — never scans (AdminAlertCheck rule: light,
 * CLI-safe). Registered from FrontServiceProvider, so core never references front.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-health-alert
 * @aidlc-adr ADR-seo-audit, admin-shell_system-alert-registry
 */
class SeoHealthCheck implements AdminAlertCheck
{
    public const KEY = 'seo_audit';

    /**
     * @return string
     */
    public function key(): string
    {
        return self::KEY;
    }

    /**
     * @return int Seconds the dashboard may cache the result.
     */
    public function ttl(): int
    {
        // WHY short: the result changes when the admin runs a new check; an hour-old
        // "problems to fix" after a clean run reads as a broken screen.
        return 600;
    }

    /**
     * @return AdminAlert info (never checked) · warning (problems to fix) · ok.
     */
    public function evaluate(): AdminAlert
    {
        $result = SeoAudit::latest(config('app.storeId'));
        $url = Route::has('admin_seo_audit.index') ? (string) gp247_route_admin('admin_seo_audit.index') : '';
        $label = (string) gp247_language_render('admin.seo_audit.title');

        if ($result === null) {
            return AdminAlert::info(self::KEY, (string) gp247_language_render('admin.seo_audit.alert_not_checked'), 'not checked yet', $url, $label);
        }
        $toFix = [];
        foreach ($result['findings'] ?? [] as $finding) {
            if (($finding['level'] ?? '') === SeoAudit::LEVEL_FIX) {
                $toFix[] = $finding['code'] . ': ' . (int) $finding['count'];
            }
        }
        if ($toFix === []) {
            return AdminAlert::ok(self::KEY, (string) gp247_language_render('admin.seo_audit.alert_ok'), 'checked ' . ($result['checked_at'] ?? ''));
        }

        return AdminAlert::warning(
            self::KEY,
            (string) gp247_language_render('admin.seo_audit.alert_fix', ['count' => count($toFix)]),
            count($toFix) . ' problem type(s) to fix, checked ' . ($result['checked_at'] ?? ''),
            $url,
            $label,
            '',
            $toFix
        );
    }
}
