<?php
#GP247/Front/Models/FrontLink.php
namespace GP247\Front\Models;

use Illuminate\Database\Eloquent\Model;

class FrontSubscribe extends Model
{
    use \GP247\Core\Models\ModelTrait;
    use \GP247\Core\Models\UuidTrait;

    public $table = GP247_DB_PREFIX.'front_subscribe';
    protected $guarded = [];
    protected $connection = GP247_DB_CONNECTION;

    protected static function boot()
    {
        parent::boot();
        // before delete() method call this
        static::deleting(
            function ($model) {
                //
            }
        );

        //Uuid
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = gp247_generate_id();
            }
        });
    }
}
