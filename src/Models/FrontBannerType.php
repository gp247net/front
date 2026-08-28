<?php
#GP247/Front/Models/FrontBannerType.php
namespace GP247\Front\Models;

use GP247\Core\Models\AdminStore;
use Illuminate\Database\Eloquent\Model;

class FrontBannerType extends Model
{
    public $table = GP247_DB_PREFIX.'front_banner_type';
    protected $guarded   = [];
    protected $connection = GP247_DB_CONNECTION;

    /**
     * The store that owns this banner type (1-1 ownership).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @aidlc-unit compat-foundation
     * @aidlc-story US-CMP-store-1to1-schema
     * @aidlc-adr multi-store_one-to-one-store-ownership
     */
    public function store()
    {
        return $this->belongsTo(AdminStore::class, 'store_id', 'id');
    }
}
