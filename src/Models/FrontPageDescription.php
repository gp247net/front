<?php
#GP247/Front/Models/FrontPageDescription.php
namespace GP247\Front\Models;

use Illuminate\Database\Eloquent\Model;

class FrontPageDescription extends Model
{
    use \GP247\Core\Models\UuidTrait;
    
    protected $primaryKey = ['lang', 'page_id'];
    public $incrementing  = false;
    protected $guarded    = [];
    public $timestamps    = false;
    public $table = GP247_DB_PREFIX.'front_page_description';
    protected $connection = GP247_DB_CONNECTION;
}
