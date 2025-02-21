<?php
#GP247/Front/Models/FrontLinkStore.php
namespace GP247\Front\Models;

use Illuminate\Database\Eloquent\Model;

class FrontLinkStore extends Model
{
    use \GP247\Core\Models\ModelTrait;
    
    protected $primaryKey = ['store_id', 'link_id'];
    public $incrementing  = false;
    protected $guarded    = [];
    public $timestamps    = false;
    public $table = GP247_DB_PREFIX.'front_link_store';
    protected $connection = GP247_DB_CONNECTION;
}
