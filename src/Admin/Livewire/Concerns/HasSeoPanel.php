<?php

namespace GP247\Front\Admin\Livewire\Concerns;

use GP247\Front\Library\SeoContentMeta;
use Illuminate\Support\Facades\DB;

/**
 * Shared SEO panel state for a ResourcePanel edit screen (product, category, brand,
 * page, plugin screens). The screen calls fillSeo() when it loads or resets the form,
 * merges seoRules() into rules(), and wraps its persist() body in persistWithSeo() so
 * the content and its SEO values are saved together. Rendered by the partial
 * `gp247-front-admin::partials.seo-panel` (contract: shop-admin__seo_integration).
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-content-meta, US-SEO-content-index-control, US-SEO-content-type-registry
 * @aidlc-adr ADR-seo-content-meta-table
 */
trait HasSeoPanel
{
    /**
     * Panel state: noindex, nofollow, og_image, desc.<lang>.{title, description, canonical}.
     *
     * @var array<string, mixed>
     */
    public array $seo = [];

    /**
     * Registered content-type key of the screen (e.g. `product`).
     *
     * @return string
     */
    abstract protected function seoType(): string;

    /**
     * Load the panel for a record, or the empty state for a new one.
     *
     * @param int|string|null $id Content id; null = create mode.
     * @return void
     */
    protected function fillSeo($id): void
    {
        $this->seo = SeoContentMeta::formState($this->seoType(), $id);
    }

    /**
     * Validation rules of the panel, keyed under `seo.`.
     *
     * @return array<string, array<int, string>>
     */
    protected function seoRules(): array
    {
        return SeoContentMeta::rules('seo');
    }

    /**
     * Rule key => language key, for screens using HasValidationLabels::attributeLabels().
     *
     * @return array<string, string>
     */
    protected function seoAttributeLabels(): array
    {
        return [
            'seo.og_image' => 'admin.seo.og_image',
            'seo.desc.*.title' => 'admin.seo.seo_title',
            'seo.desc.*.description' => 'admin.seo.seo_description',
            'seo.desc.*.canonical' => 'admin.seo.canonical',
        ];
    }

    /**
     * Rule key => rendered label, for screens overriding validationAttributes().
     *
     * @return array<string, string>
     */
    protected function seoValidationAttributes(): array
    {
        return array_map(static fn (string $key): string => (string) gp247_language_render($key), $this->seoAttributeLabels());
    }

    /**
     * Run the screen's persist body, then save the panel for the persisted record
     * (`$this->editingId`, set by persist() also after a create) in one transaction.
     *
     * @param callable $persist The screen's own persist logic.
     * @return void
     * @throws \Illuminate\Validation\ValidationException When the panel values are invalid.
     */
    protected function persistWithSeo(callable $persist): void
    {
        $editingBefore = $this->editingId;
        try {
            DB::connection(GP247_DB_CONNECTION)->transaction(function () use ($persist) {
                $persist();
                // WHY baseQuery(): editingId is a client-writable property — SEO is only
                // written for a record this screen (and its store scope) can load.
                if ($this->editingId !== null && $this->editingId !== ''
                    && $this->baseQuery()->whereKey($this->editingId)->exists()) {
                    SeoContentMeta::save($this->seoType(), $this->editingId, $this->seo, $this->seoStoreId());
                }
            });
        } catch (\Throwable $e) {
            // WHY: a create rolled back must not leave the form pointing at a row that no longer exists.
            $this->editingId = $editingBefore;
            throw $e;
        }
    }

    /**
     * Store whose sitemap cache is refreshed after a save; null = current store.
     *
     * @return int|string|null
     */
    private function seoStoreId()
    {
        $store = property_exists($this, 'formStoreId') ? (string) $this->formStoreId : '';

        return $store !== '' ? $store : null;
    }
}
