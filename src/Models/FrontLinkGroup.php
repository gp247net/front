<?php
#GP247/Front/Models/FrontLinkGroup.php
namespace GP247\Front\Models;

use Illuminate\Database\Eloquent\Model;

class FrontLinkGroup extends Model
{
    use \GP247\Core\Models\ModelTrait;
    
    public $table = GP247_DB_PREFIX.'front_link_group';
    protected $guarded   = [];
    protected $connection = GP247_DB_CONNECTION;
}
