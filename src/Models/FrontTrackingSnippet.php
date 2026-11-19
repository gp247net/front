<?php

namespace GP247\Front\Models;

use GP247\Front\Tracking\Infrastructure\TrackingInstaller;
use Illuminate\Database\Eloquent\Model;

/**
 * One custom code block of a store. Written only through
 * GP247\Front\Tracking\Application\SnippetService.
 *
 * @property int $id
 * @property string $store_id
 * @property string $region
 * @property string $category
 * @property string $label
 * @property string $content
 * @property int $sort
 * @property int $status
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-custom-code
 * @aidlc-adr tracking-consent_config-storage-and-authority
 */
class FrontTrackingSnippet extends Model
{
    public $table = GP247_DB_PREFIX . TrackingInstaller::TABLE_SNIPPET;

    protected $connection = GP247_DB_CONNECTION;

    protected $fillable = ['store_id', 'region', 'category', 'label', 'content', 'sort', 'status'];

    protected $casts = ['sort' => 'integer', 'status' => 'integer'];
}
