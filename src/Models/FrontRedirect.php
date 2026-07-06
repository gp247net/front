<?php

namespace GP247\Front\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for URL redirect rules (301/302).
 *
 * Each row maps an incoming `from` path to a `to` destination with
 * a configurable HTTP code, scoped to a store.
 *
 * @property int    $id
 * @property string $from      Incoming URL path (e.g. /old-page.html)
 * @property string $to        Target URL path or absolute URL
 * @property int    $code      HTTP redirect code (301 or 302)
 * @property string $store_id  Store UUID
 * @property int    $status    1 = active, 0 = disabled
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-006
 */
class FrontRedirect extends Model
{
    protected $connection = GP247_DB_CONNECTION;

    protected $table = GP247_DB_PREFIX . 'front_redirects';

    protected $fillable = ['from', 'to', 'code', 'store_id', 'status'];

    /**
     * Find an active redirect for the given path within the current store.
     *
     * @param string $from  URL path to look up (e.g. /old-page.html)
     * @return self|null
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-006
     */
    public static function findActive(string $from): ?self
    {
        return static::where('from', $from)
            ->where('store_id', config('app.storeId'))
            ->where('status', 1)
            ->first();
    }
}
