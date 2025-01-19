<?php
#GP247/Front/Models/FrontBannerStore.php
namespace GP247\Front\Models;

use Illuminate\Database\Eloquent\Model;

class FrontBannerStore extends Model
{
   
    protected $primaryKey = ['store_id', 'banner_id'];
    public $incrementing  = false;
    protected $guarded    = [];
    public $timestamps    = false;
    public $table = GP247_DB_PREFIX.'front_banner_store';
    protected $connection = GP247_DB_CONNECTION;
}
