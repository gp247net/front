<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\GP247AdminComponent;
use GP247\Core\Models\AdminConfig;
use GP247\Front\Library\NotFoundLog;
use GP247\Front\Models\FrontNotFoundLog;
use Illuminate\Contracts\View\View;
use Livewire\WithPagination;

/**
 * "404 log" admin screen (SEO phase 2): the switch of the storefront 404 log, the logged
 * addresses of the current store (most hits first), delete one / delete all, and a
 * "Create redirect" link that opens the Redirect 301 screen with `from` prefilled.
 * Gated by `admin_seo_not_found`.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-not-found-log
 * @aidlc-adr ADR-seo-url-lifecycle
 */
class SeoNotFoundLog extends GP247AdminComponent
{
    use WithPagination;

    protected ?string $permission = 'admin_seo_not_found';

    private const PER_PAGE = 20;

    /** @var bool Switch of the 404 log for this store. */
    public bool $enabled = false;

    /** @var string Path filter. */
    public string $keyword = '';

    /**
     * The store this screen reads and writes — the store the middleware records under
     * for this domain (same choice as SeoMetaSettings).
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
        $this->enabled = NotFoundLog::enabled($this->storeId());
    }

    /**
     * Persist the switch (Layer-2 gated).
     *
     * @param mixed $value
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function updatedEnabled($value): void
    {
        $this->authorizeAction('update');
        $this->enabled = (bool) $value;
        AdminConfig::updateOrCreate(
            ['key' => NotFoundLog::CONFIG_KEY, 'store_id' => (string) $this->storeId()],
            ['value' => $this->enabled ? '1' : '0', 'group' => 'seo', 'code' => 'seo_not_found', 'sort' => 0]
        );
        $this->notify('success', gp247_language_render('admin.setting_saved'));
    }

    /**
     * @return void
     */
    public function updatedKeyword(): void
    {
        $this->resetPage();
    }

    /**
     * Delete one row of this store (Layer-2 gated).
     *
     * @param int|string $id
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function deleteRow($id): void
    {
        $this->authorizeAction('delete');
        FrontNotFoundLog::where('store_id', (string) $this->storeId())->whereKey((int) $id)->delete();
    }

    /**
     * Delete every row of this store (Layer-2 gated).
     *
     * @return void
     * @throws \GP247\Core\AdminShell\Domain\AuthorizationException When denied.
     */
    public function clearAll(): void
    {
        $this->authorizeAction('delete');
        FrontNotFoundLog::where('store_id', (string) $this->storeId())->delete();
        $this->resetPage();
    }

    /**
     * @return View
     */
    public function render(): View
    {
        $rows = null;
        if (NotFoundLog::tableReady()) {
            $query = FrontNotFoundLog::where('store_id', (string) $this->storeId());
            $keyword = trim($this->keyword);
            if ($keyword !== '') {
                $query->where('path', 'like', '%' . addcslashes($keyword, '%_\\') . '%');
            }
            $rows = $query->orderByDesc('hits')->orderByDesc('last_seen_at')->paginate(self::PER_PAGE);
        }

        return view('gp247-front-admin::seo-not-found-log', ['rows' => $rows])
            ->layout('gp247-admin::layouts.admin', ['title' => gp247_language_render('admin.seo_not_found.title')]);
    }
}
