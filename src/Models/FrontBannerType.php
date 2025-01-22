<?php
#GP247/Front/Models/FrontBannerType.php
namespace GP247\Front\Models;

use Illuminate\Database\Eloquent\Model;

class FrontBannerType extends Model
{   
    public $table = GP247_DB_PREFIX.'front_banner_type';
    protected $guarded   = [];
    protected $connection = GP247_DB_CONNECTION;
}
