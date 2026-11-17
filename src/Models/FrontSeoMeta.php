<?php

namespace GP247\Front\Models;

use GP247\Front\Support\SeoMetaInstaller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Content-level SEO settings of one piece of content (robots flags, share image).
 * Written only through GP247\Front\Library\SeoContentMeta.
 *
 * @property int $id
 * @property string $type
 * @property string $content_id
 * @property int $noindex
 * @property int $nofollow
 * @property string|null $og_image
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-content-meta, US-SEO-content-index-control
 * @aidlc-adr ADR-seo-content-meta-table
 */
class FrontSeoMeta extends Model
{
    public $table = GP247_DB_PREFIX . SeoMetaInstaller::TABLE_META;

    protected $connection = GP247_DB_CONNECTION;

    protected $fillable = ['type', 'content_id', 'noindex', 'nofollow', 'og_image'];

    protected $casts = ['noindex' => 'boolean', 'nofollow' => 'boolean'];

    /**
     * Language rows (title, description, canonical).
     *
     * @return HasMany
     */
    public function descriptions(): HasMany
    {
        return $this->hasMany(FrontSeoMetaDescription::class, 'seo_meta_id', 'id');
    }
}
