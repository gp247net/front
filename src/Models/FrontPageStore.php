<?php
#GP247/Front/Models/FrontPageStore.php
namespace GP247\Front\Models;

use Illuminate\Database\Eloquent\Model;

class FrontPageStore extends Model
{
    use \GP247\Core\Admin\Models\ModelTrait;
    
    protected $primaryKey = ['store_id', 'page_id'];
    public $incrementing  = false;
    protected $guarded    = [];
    public $timestamps    = false;
    public $table = GP247_DB_PREFIX.'front_page_store';
    protected $connection = GP247_DB_CONNECTION;
}
