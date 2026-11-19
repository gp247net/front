<?php

namespace GP247\Front\Tracking\Application;

use GP247\Core\AdminShell\Domain\AdminAlertCheck;
use GP247\Core\AdminShell\Infrastructure\AdminAlert;
use GP247\Core\Models\AdminStore;
use GP247\Front\Models\FrontLayoutBlock;
use GP247\Front\Tracking\Domain\TrackingSettings;
use Illuminate\Support\Facades\Route;

/**
 * "System alerts" / gp247:doctor row `tracking_consent`, over every store of the site:
 * - warning: tracking code runs while the cookie banner is off (no visitor is asked);
 * - warning: the store's template does not call meta-head, so its code never runs;
 * - info: a layout HTML block of the store's template holds a <script>, which runs outside
 *   the consent gate.
 * Every store is checked because the result is cached once for the whole admin (not per
 * store) and the CLI has no store of its own. Light and CLI-safe: settings come from the
 * config memo, a template's layout files are read once per process, one count query per store.
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-health-check
 * @aidlc-adr tracking-consent_head-injection-via-meta-head, admin-shell_system-alert-registry
 */
class TrackingHealthCheck implements AdminAlertCheck
{
    public const KEY = 'tracking_consent';

    /**
     * @return string
     */
    public function key(): string
    {
        return self::KEY;
    }

    /**
     * @return int Seconds the dashboard may cache the result (saving the settings refreshes it).
     */
    public function ttl(): int
    {
        return 600;
    }

    /**
     * @return AdminAlert warning (code runs without asking / cannot run) · info (scripts in layout blocks) · ok.
     */
    public function evaluate(): AdminAlert
    {
        if (!TrackingSettingsService::installed()) {
            return AdminAlert::ok(self::KEY, (string) gp247_language_render('admin.tracking.health_ok'), 'tracking not installed yet');
        }
        $stores = $this->stores();
        $prefix = static fn (string $name): string => count($stores) > 1 ? '[' . $name . '] ' : '';

        $problems = [];
        $scriptBlocks = 0;
        foreach ($stores as $storeId => $name) {
            $settings = TrackingSettingsService::forStore($storeId);
            $hasCode = $settings->enabled && ($settings->hasAnyService() || SnippetService::activeFor($storeId, $settings) !== []);
            $template = TemplateSupport::templateOf($storeId);
            if ($hasCode && $settings->bannerMode === TrackingSettings::MODE_OFF) {
                $problems[] = $prefix($name) . gp247_language_render('admin.tracking.health_banner_off');
            }
            if ($hasCode && !TemplateSupport::callsMetaHead($template)) {
                $problems[] = $prefix($name) . gp247_language_render('admin.tracking.health_template', ['template' => $template]);
            }
            // WHY the template too: only blocks of the store's current template are rendered.
            $scriptBlocks += FrontLayoutBlock::query()
                ->where('store_id', $storeId)
                ->where('template', $template)
                ->where('status', 1)
                ->where('type', 'html')
                ->where('text', 'like', '%<script%')
                ->count();
        }

        if ($problems !== []) {
            return AdminAlert::warning(
                self::KEY,
                (string) gp247_language_render('admin.tracking.health_warning', ['count' => count($problems)]),
                count($problems) . ' tracking problem(s)',
                Route::has('admin_tracking.index') ? (string) gp247_route_admin('admin_tracking.index') : '',
                (string) gp247_language_render('admin.tracking.title'),
                '',
                $problems
            );
        }
        if ($scriptBlocks > 0) {
            return AdminAlert::info(
                self::KEY,
                (string) gp247_language_render('admin.tracking.health_layout_script', ['count' => $scriptBlocks]),
                $scriptBlocks . ' layout HTML block(s) with a script',
                Route::has('admin_layout_block.index') ? (string) gp247_route_admin('admin_layout_block.index') : '',
                (string) gp247_language_render('admin.tracking.health_layout_label')
            );
        }

        return AdminAlert::ok(self::KEY, (string) gp247_language_render('admin.tracking.health_ok'));
    }

    /**
     * @return array<string, string> store id => code, ROOT first; ROOT alone when the list cannot be read.
     */
    private function stores(): array
    {
        $root = TrackingSettingsService::rootId();
        $stores = [$root => 'root'];
        try {
            foreach (AdminStore::getListAll() as $id => $store) {
                $stores[(string) $id] = (string) ($store->code ?? $id);
            }
        } catch (\Throwable $e) {
            return $stores;
        }

        return $stores;
    }
}
