<?php
#GP247/Front/Models/FrontLayoutBlock.php
namespace GP247\Front\Models;

use Illuminate\Database\Eloquent\Model;
use GP247\Core\Models\AdminStore;
class FrontLayoutBlock extends Model
{
    use \GP247\Core\Models\ModelTrait;
    use \GP247\Core\Models\UuidTrait;

    public $table = GP247_DB_PREFIX.'front_layout_block';
    protected $guarded = [];
    private static $getLayout = null;
    protected $connection = GP247_DB_CONNECTION;
    public $incrementing  = false;
    
    public static function getLayout()
    {
        if (self::$getLayout === null) {
            $store = AdminStore::find(config('app.storeId'));
            $template = '';
            if ($store) {
                $template = $store->template;
            }
            self::$getLayout = self::where('status', 1)
                ->where('store_id', config('app.storeId'))
                ->where('template', $template)
                ->orderBy('sort', 'desc')
                ->get()
                ->groupBy('position');
        }
        return self::$getLayout;
    }

    protected static function boot()
    {
        parent::boot();
        // before delete() method call this
        static::deleting(
            function ($obj) {
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

    /**
     * Get blockContent detail in admin
     *
     * @param   [type]  $id  [$id description]
     *
     * @return  [type]       [return description]
     */
    public function getStoreBlockContentAdmin($id, $storeId = null)
    {
        $data  = $this->where('id', $id);
        if ($storeId) {
            $data = $data->where('store_id', $storeId);
        }
        return $data->first();
    }

    /**
     * Get list blockContent in admin
     *
     * @param   [array]  $dataSearch  [$dataSearch description]
     *
     * @return  [type]               [return description]
     */
    public function getStoreBlockContentListAdmin($storeId = null)
    {
        if ($storeId) {
            $data = $this->where('store_id', $storeId)
                ->orderBy('id', 'desc');
        } else {
            $data = $this->orderBy('id', 'desc');
        }
        return $data->paginate(20);
    }

    /**
     * Create a new blockContent
     *
     * @param   array  $dataInsert  [$dataInsert description]
     *
     * @return  [type]              [return description]
     */
    public static function createStoreBlockContentAdmin(array $dataCreate)
    {
        return self::create($dataCreate);
    }

}
