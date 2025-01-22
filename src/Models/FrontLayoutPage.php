<?php
#GP247/Front/Models/FrontLayoutPage.php
namespace GP247\Front\Models;

use Illuminate\Database\Eloquent\Model;
class FrontLayoutPage extends Model
{
    use \GP247\Core\Admin\Models\ModelTrait;
    
    public $table = GP247_DB_PREFIX.'front_layout_page';
    protected $connection = GP247_DB_CONNECTION;

    public static function getPages()
    {
        return self::pluck('name', 'key')->all();
    }

    //Function get text description
    protected static function boot()
    {
        parent::boot();
        // before delete() method call this
        static::deleting(
            function ($obj) {
                //
            }
        );
    }
}
