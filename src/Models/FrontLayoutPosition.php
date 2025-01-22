<?php
#GP247/Front/Models/FrontLayoutPosition.php
namespace GP247\Front\Models;

use Illuminate\Database\Eloquent\Model;

class FrontLayoutPosition extends Model
{
    use \GP247\Core\Admin\Models\ModelTrait;
    
    public $table = GP247_DB_PREFIX.'front_layout_position';
    protected $connection = GP247_DB_CONNECTION;
    
    public static function getPositions()
    {
        return self::pluck('name', 'key')->all();
    }
}
