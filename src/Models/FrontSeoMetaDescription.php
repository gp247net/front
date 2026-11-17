<?php

namespace GP247\Front\Models;

use GP247\Front\Support\SeoMetaInstaller;
use Illuminate\Database\Eloquent\Model;

/**
 * Language-level SEO values of one piece of content (title, description, canonical).
 *
 * @property int $seo_meta_id
 * @property string $lang
 * @property string|null $title
 * @property string|null $description
 * @property string|null $canonical
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-content-meta, US-SEO-content-index-control
 * @aidlc-adr ADR-seo-content-meta-table
 */
class FrontSeoMetaDescription extends Model
{
    public $table = GP247_DB_PREFIX . SeoMetaInstaller::TABLE_DESCRIPTION;

    protected $connection = GP247_DB_CONNECTION;

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = ['seo_meta_id', 'lang', 'title', 'description', 'canonical'];
}
