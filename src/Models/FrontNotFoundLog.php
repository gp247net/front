<?php

namespace GP247\Front\Models;

use GP247\Front\Support\SeoUrlLifecycleInstaller;
use Illuminate\Database\Eloquent\Model;

/**
 * One storefront address that answered 404, counted per store (SEO phase 2).
 * Written only through GP247\Front\Library\NotFoundLog.
 *
 * @property int $id
 * @property string $store_id
 * @property string $path
 * @property string $path_hash
 * @property int $hits
 * @property string|null $referer
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-not-found-log
 * @aidlc-adr ADR-seo-url-lifecycle
 */
class FrontNotFoundLog extends Model
{
    public $table = GP247_DB_PREFIX . SeoUrlLifecycleInstaller::TABLE_NOT_FOUND;

    protected $connection = GP247_DB_CONNECTION;

    public $timestamps = false;

    protected $fillable = ['store_id', 'path', 'path_hash', 'hits', 'referer', 'first_seen_at', 'last_seen_at'];

    protected $casts = ['first_seen_at' => 'datetime', 'last_seen_at' => 'datetime'];
}
