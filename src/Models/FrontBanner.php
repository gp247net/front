<?php
#GP247/Front/Models/FrontBanner.php
namespace GP247\Front\Models;

use Illuminate\Database\Eloquent\Model;
use Cache;
use GP247\Core\Models\AdminStore;
use GP247\Front\Models\FrontBannerStore;

class FrontBanner extends Model
{
    
    use \GP247\Core\Models\ModelTrait;
    use \GP247\Core\Models\UuidTrait;

    public $table          = GP247_DB_PREFIX.'front_banner';
    protected $connection  = GP247_DB_CONNECTION;
    protected $guarded     = [];

    public function stores()
    {
        return $this->belongsToMany(AdminStore::class, FrontBannerStore::class, 'banner_id', 'store_id');
    }


    /*
    Get thumb
    */
    public function getThumb()
    {
        return gp247_image_get_path_thumb($this->image);
    }

    /*
    Get image
    */
    public function getImage()
    {
        return gp247_image_get_path($this->image);
    }
    
    public function scopeSort($query, $sortBy = null, $sortOrder = 'asc')
    {
        $sortBy = $sortBy ?? 'sort';
        return $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Get info detail
     *
     * @param   [int]  $id
     * @param   [int]  $checkActive
     *
     */
    public function getDetail($id, $checkActive = 1)
    {
        $storeId = config('app.storeId');
        $dataSelect = $this->getTable().'.*';
        $data =  $this->selectRaw($dataSelect)
            ->where('id', $id);
        if ($checkActive) {
            $data = $data->where($this->getTable() .'.status', 1);
        }
        if (gp247_store_check_multi_partner_installed() ||  gp247_store_check_multi_store_installed()) {
            $tableBannerStore = (new FrontBannerStore)->getTable();
            $tableStore = (new AdminStore)->getTable();
            $data = $data->join($tableBannerStore, $tableBannerStore.'.banner_id', $this->getTable() . '.id');
            $data = $data->join($tableStore, $tableStore . '.id', $tableBannerStore.'.store_id');
            $data = $data->where($tableStore . '.status', '1');
            $data = $data->where($tableBannerStore.'.store_id', $storeId);
        }
        $data = $data->first();
        return $data;
    }

    protected static function boot()
    {
        parent::boot();
        // before delete() method call this
        static::deleting(function ($banner) {
            $banner->stores()->detach();

            //Delete custom field
            (new \GP247\Core\Models\AdminCustomFieldDetail)
            ->join(GP247_DB_PREFIX.'admin_custom_field', GP247_DB_PREFIX.'admin_custom_field.id', GP247_DB_PREFIX.'admin_custom_field_detail.custom_field_id')
            ->where(GP247_DB_PREFIX.'admin_custom_field_detail.rel_id', $banner->id)
            ->where(GP247_DB_PREFIX.'admin_custom_field.type', $banner->getTable())
            ->delete();

        });


        //Uuid
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = gp247_generate_id('BN');
            }
        });
    }


    /**
     * Start new process get data
     *
     * @return  new model
     */
    public function start()
    {
        return new FrontBanner;
    }

    /**
     * Set type
     */
    public function setType($type)
    {
        $this->gp247_type = $type;
        return $this;
    }

    /**
     * Get banner
     */
    public function getBanner()
    {
        $this->setType('banner');
        return $this;
    }

    /**
     * Get banner
     */
    public function getBannerStore()
    {
        $this->setType('banner-store');
        return $this;
    }

    /**
     * Get background
     */
    public function getBackground()
    {
        $this->setType('background');
        $this->setLimit(1);
        return $this;
    }

    /**
     * Get background
     */
    public function getBackgroundStore()
    {
        $this->setType('background-store');
        $this->setLimit(1);
        return $this;
    }

    /**
     * Get banner
     */
    public function getBreadcrumb()
    {
        $this->setType('breadcrumb');
        $this->setLimit(1);
        return $this;
    }

    /**
     * Get banner
     */
    public function getBreadcrumbStore()
    {
        $this->setType('breadcrumb-store');
        $this->setLimit(1);
        return $this;
    }

    /**
     * Set store id
     *
     */
    public function setStore($id)
    {
        $this->gp247_store = $id;
        return $this;
    }

    /**
     * build Query
     */
    public function buildQuery()
    {
        $dataSelect = $this->getTable().'.*';
        $query =  $this->selectRaw($dataSelect)
            ->where($this->getTable() .'.status', 1);

        $storeId = config('app.storeId');

        if (gp247_store_check_multi_partner_installed() ||  gp247_store_check_multi_store_installed()) {
            if (!empty($this->gp247_store)) {
                //If sepcify store id
                $storeId = $this->gp247_store;
            }
            $tableBannerStore = (new FrontBannerStore)->getTable();
            $tableStore = (new AdminStore)->getTable();
            $query = $query->join($tableBannerStore, $tableBannerStore.'.banner_id', $this->getTable() . '.id');
            $query = $query->join($tableStore, $tableStore . '.id', $tableBannerStore.'.store_id');
            $query = $query->where($tableStore . '.status', '1');
            $query = $query->where($tableBannerStore.'.store_id', $storeId);
        }

        if ($this->gp247_type !== 'all') {
            $query = $query->where('type', $this->gp247_type);
        }

        $query = $this->processMoreQuery($query);

        if ($this->gp247_random) {
            $query = $query->inRandomOrder();
        } else {
            $checkSort = false;
            if (is_array($this->gp247_sort) && count($this->gp247_sort)) {
                foreach ($this->gp247_sort as  $rowSort) {
                    if (is_array($rowSort) && count($rowSort) == 2) {
                        if ($rowSort[0] == 'sort') {
                            $checkSort = true;
                        }
                        $query = $query->sort($rowSort[0], $rowSort[1]);
                    }
                }
            }
            //Use field "sort" if haven't above
            if (empty($checkSort)) {
                $query = $query->orderBy($this->getTable().'.sort', 'asc');
            }
            //Default, will sort id
            $query = $query->orderBy($this->getTable().'.id', 'desc');
        }

        return $query;
    }

    /**
     * Get banner detail in admin
     *
     * @param   [type]  $id  [$id description]
     *
     * @return  [type]       [return description]
     */
    public static function getBannerAdmin($id, $storeId = null)
    {
        $data = self::where('id', $id);
        if ($storeId) {
            $tableBannerStore = (new FrontBannerStore)->getTable();
            $tableBanner = (new FrontBanner)->getTable();
            $data = $data->leftJoin($tableBannerStore, $tableBannerStore . '.banner_id', $tableBanner . '.id');
            $data = $data->where($tableBannerStore . '.store_id', $storeId);
        }
        $data = $data->first();
        return $data;
    }

    /**
     * Get list banner in admin
     *
     * @param   [array]  $dataSearch  [$dataSearch description]
     *
     * @return  [type]               [return description]
     */
    public static function getBannerListAdmin(array $dataSearch, $storeId = null)
    {
        $sort       = $dataSearch['sort'] ?? '';
        $arrSort          = $dataSearch['arrSort'] ?? '';
        $keyword          = $dataSearch['keyword'] ?? '';
        $bannerList = (new FrontBanner);
        $tableBanner = $bannerList->getTable();
        if ($storeId) {
            $tableBannerStore = (new FrontBannerStore)->getTable();
            $bannerList = $bannerList->leftJoin($tableBannerStore, $tableBannerStore . '.banner_id', $tableBanner . '.id');
            $bannerList = $bannerList->where($tableBannerStore . '.store_id', $storeId);
        }
        if ($keyword) {
            $bannerList->where($tableBanner.'.title', 'like', '%'.$keyword.'%');
        }
        if ($sort && array_key_exists($sort, $arrSort)) {
            $field = explode('__', $sort)[0];
            $sort_field = explode('__', $sort)[1];
            if ($field == 'id') {
                $field = 'created_at';
            }
            $bannerList = $bannerList->sort($field, $sort_field);
        } else {
            $bannerList = $bannerList->sort($tableBanner.'.created_at', 'desc');
        }
        $bannerList = $bannerList->paginate(20);

        return $bannerList;
    }

    /**
     * Create a new banner
     *
     * @param   array  $dataCreate  [$dataCreate description]
     *
     * @return  [type]              [return description]
     */
    public static function createBannerAdmin(array $dataCreate)
    {
        return self::create($dataCreate);
    }

}
