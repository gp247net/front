<?php

namespace GP247\Front\Models;

use GP247\Front\Tracking\Infrastructure\TrackingInstaller;
use Illuminate\Database\Eloquent\Model;

/**
 * Snapshot of a store's custom code blocks before and after one save (JSON lists),
 * who saved and when. Append-only; SnippetService keeps the newest 30 per store.
 *
 * @property int $id
 * @property string $store_id
 * @property string|null $admin_user_id
 * @property string|null $admin_user_name
 * @property string $before
 * @property string $after
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-custom-code
 * @aidlc-adr tracking-consent_config-storage-and-authority
 */
class FrontTrackingRevision extends Model
{
    public $table = GP247_DB_PREFIX . TrackingInstaller::TABLE_REVISION;

    protected $connection = GP247_DB_CONNECTION;

    public $timestamps = false;

    protected $fillable = ['store_id', 'admin_user_id', 'admin_user_name', 'before', 'after', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];
}
