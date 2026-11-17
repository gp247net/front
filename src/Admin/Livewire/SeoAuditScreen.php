<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\GP247AdminComponent;
use GP247\Front\Library\SeoAudit;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

/**
 * "SEO check" admin screen (SEO phase 3): shows the last kept result of the current store,
 * runs a new check on demand and cleans SEO rows of deleted content. Gated by
 * `admin_seo_audit`.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-audit
 * @aidlc-adr ADR-seo-audit
 */
class SeoAuditScreen extends GP247AdminComponent
{
    protected ?string $permission = 'admin_seo_audit';

    /** @var array|null Last result (see SeoAudit::run()); server-set only. */
    #[Locked]
    public ?array $result = null;

    /** @var int|null Rows removed by the last clean-up; server-set only. */
    #[Locked]
    public ?int $cleaned = null;

    /**
     * The store this screen checks — the store of the current domain, like the other SEO screens.
     *
     * @return mixed
     */
    private function storeId()
    {
        return config('app.storeId');
    }

    /**
     * @return void
     */
    public function mount(): void
    {
        parent::mount();
        $this->result = SeoAudit::latest($this->storeId());
    }

    /**
     * Run a new check (Layer-2 gated).
     *
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function runAudit(): void
    {
        $this->authorizeAction('update');
        $this->result = SeoAudit::run($this->storeId());
    }

    /**
     * Delete SEO rows of deleted content, then check again (Layer-2 gated).
     *
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function cleanOrphans(): void
    {
        $this->authorizeAction('delete');
        $this->cleaned = SeoAudit::cleanOrphans();
        $this->result = SeoAudit::run($this->storeId());
        $this->notify('success', gp247_language_render('admin.seo_audit.cleaned', ['count' => $this->cleaned]));
    }

    /**
     * @return View
     */
    public function render(): View
    {
        $groups = [SeoAudit::LEVEL_FIX => [], SeoAudit::LEVEL_REVIEW => []];
        foreach ($this->result['findings'] ?? [] as $finding) {
            $groups[$finding['level']][] = $finding;
        }

        return view('gp247-front-admin::seo-audit', ['groups' => $groups])
            ->layout('gp247-admin::layouts.admin', ['title' => gp247_language_render('admin.seo_audit.title')]);
    }
}
