<?php
#GP247/Front/Models/FrontPage.php
namespace GP247\Front\Models;

use Illuminate\Database\Eloquent\Model;
use Cache;
use GP247\Core\Models\AdminStore;
use GP247\Front\Models\FrontPageStore;

class FrontPage extends Model
{
    
    use \GP247\Core\Models\ModelTrait;
    use \GP247\Core\Models\UuidTrait;

    public $table          = GP247_DB_PREFIX.'front_page';
    protected $connection  = GP247_DB_CONNECTION;
    protected $guarded     = [];

    protected static $getListTitleAdmin = null;
    protected static $getListPageGroupByParentAdmin = null;

    public function stores()
    {
        return $this->belongsToMany(AdminStore::class, FrontPageStore::class, 'page_id', 'store_id');
    }

    public function descriptions()
    {
        return $this->hasMany(FrontPageDescription::class, 'page_id', 'id');
    }

    //Function get text description
    public function getText()
    {
        return $this->descriptions()->where('lang', gp247_get_locale())->first();
    }
    public function getTitle()
    {
        return $this->getText()->title ?? '';
    }
    public function getDescription()
    {
        return $this->getText()->description ?? '';
    }
    public function getKeyword()
    {
        return $this->getText()->keyword?? '';
    }
    public function getContent()
    {
        return $this->getText()->content;
    }
    //End  get text description


    /*
    *Get thumb
    */
    public function getThumb()
    {
        return gp247_image_get_path_thumb($this->image);
    }

    /*
    *Get image
    */
    public function getImage()
    {
        return gp247_image_get_path($this->image);
    }

    public function getUrl($lang = null)
    {
        return gp247_route_front('front.page.detail', ['alias' => $this->alias, 'lang' => $lang ?? app()->getLocale()]);
    }

    /**
     * Get page detail
     *
     * @param   [string]  $key     [$key description]
     * @param   [string]  $type  [id, alias]
     * @param   [int]  $checkActive
     *
     */
    public function getDetail($key, $type = null, $checkActive = 1)
    {
        if (empty($key)) {
            return null;
        }
        $tableDescription = (new FrontPageDescription)->getTable();

        $dataSelect = $this->getTable().'.*, '.$tableDescription.'.*';
        $page = $this->selectRaw($dataSelect)
            ->leftJoin($tableDescription, $tableDescription . '.page_id', $this->getTable() . '.id')
            ->where($tableDescription . '.lang', gp247_get_locale());

        $storeId = config('app.storeId');
        if (gp247_store_check_multi_partner_installed() ||  gp247_store_check_multi_store_installed()) {
            $tablePageStore = (new FrontPageStore)->getTable();
            $tableStore = (new AdminStore)->getTable();
            $page = $page->join($tablePageStore, $tablePageStore.'.page_id', $this->getTable() . '.id');
            $page = $page->join($tableStore, $tableStore . '.id', $tablePageStore.'.store_id');
            $page = $page->where($tableStore . '.status', '1');
            $page = $page->where($tablePageStore.'.store_id', $storeId);
        }

        if ($type === null) {
            $page = $page->where($this->getTable() .'.id', $key);
        } else {
            $page = $page->where($type, $key);
        }
        if ($checkActive) {
            $page = $page->where($this->getTable() .'.status', 1);
        }

        return $page->first();
    }

    protected static function boot()
    {
        parent::boot();
        // before delete() method call this
        static::deleting(
            function ($page) {
                $page->descriptions()->delete();
                $page->stores()->detach();

                //Delete custom field
                (new \GP247\Core\Models\AdminCustomFieldDetail)
                ->join(GP247_DB_PREFIX.'admin_custom_field', GP247_DB_PREFIX.'admin_custom_field.id', GP247_DB_PREFIX.'admin_custom_field_detail.custom_field_id')
                ->where(GP247_DB_PREFIX.'admin_custom_field_detail.rel_id', $page->id)
                ->where(GP247_DB_PREFIX.'admin_custom_field.type', $page->getTable())
                ->delete();
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
     * Start new process get data
     *
     * @return  new model
     */
    public function start()
    {
        return new FrontPage;
    }

    /**
     * build Query
     */
    public function buildQuery()
    {
        $tableDescription = (new FrontPageDescription)->getTable();

        $dataSelect = $this->getTable().'.*, '.$tableDescription.'.*';
        $query = $this->selectRaw($dataSelect)
            ->leftJoin($tableDescription, $tableDescription . '.page_id', $this->getTable() . '.id')
            ->where($tableDescription . '.lang', gp247_get_locale());

        $storeId = config('app.storeId');
        if (gp247_store_check_multi_partner_installed() ||  gp247_store_check_multi_store_installed()) {
            $tablePageStore = (new FrontPageStore)->getTable();
            $tableStore = (new AdminStore)->getTable();
            $query = $query->join($tablePageStore, $tablePageStore.'.page_id', $this->getTable() . '.id');
            $query = $query->join($tableStore, $tableStore . '.id', $tablePageStore.'.store_id');
            $query = $query->where($tableStore . '.status', '1');
            $query = $query->where($tablePageStore.'.store_id', $storeId);
        }

        //search keyword
        if ($this->gp247_keyword !='') {
            $query = $query->where(function ($sql) use ($tableDescription) {
                $sql->where($tableDescription . '.title', 'like', '%' . $this->gp247_keyword . '%')
                ->orWhere($tableDescription . '.keyword', 'like', '%' . $this->gp247_keyword . '%')
                ->orWhere($tableDescription . '.description', 'like', '%' . $this->gp247_keyword . '%');
            });
        }

        $query = $query->where($this->getTable() .'.status', 1);

        $query = $this->processMoreQuery($query);
        

        if ($this->random) {
            $query = $query->inRandomOrder();
        } else {
            if (is_array($this->gp247_sort) && count($this->gp247_sort)) {
                foreach ($this->gp247_sort as  $rowSort) {
                    if (is_array($rowSort) && count($rowSort) == 2) {
                        $query = $query->sort($rowSort[0], $rowSort[1]);
                    }
                }
            }
        }

        return $query;
    }

    public static function getPageListAdmin(array $dataSearch, $storeId = null)
    {
        $keyword          = $dataSearch['keyword'] ?? '';
        $sort       = $dataSearch['sort'] ?? '';
        $arrSort          = $dataSearch['arrSort'] ?? '';
        $tableDescription = (new FrontPageDescription)->getTable();
        $tablePage     = (new FrontPage)->getTable();

        $pageList = (new FrontPage)
            ->leftJoin($tableDescription, $tableDescription . '.page_id', $tablePage . '.id')
            ->where($tableDescription . '.lang', gp247_get_locale());

        $tablePage = (new FrontPage)->getTable();
        if ($storeId) {
            $tablePageStore = (new FrontPageStore)->getTable();
            $pageList = $pageList->leftJoin($tablePageStore, $tablePageStore . '.page_id', $tablePage . '.id');
            $pageList = $pageList->where($tablePageStore . '.store_id', $storeId);
        }

        if ($keyword) {
            $pageList = $pageList->where(function ($sql) use ($tableDescription, $keyword) {
                $sql->where($tableDescription . '.title', 'like', '%' . $keyword . '%');
            });
        }

        if ($sort && array_key_exists($sort, $arrSort)) {
            $field = explode('__', $sort)[0];
            $sort_field = explode('__', $sort)[1];
            if ($field == 'id') {
                $field = 'created_at';
            }
            $pageList = $pageList->orderBy($field, $sort_field);
        } else {
            $pageList = $pageList->orderBy($tablePage.'.created_at', 'desc');
        }
        $pageList = $pageList->paginate(20);

        return $pageList;
    }

    public static function getPageAdmin($id, $storeId = null)
    {
        $data = self::where('id', $id);
        if ($storeId) {
            $tablePageStore = (new FrontPageStore)->getTable();
            $tablePage = (new FrontPage)->getTable();
            $data = $data->leftJoin($tablePageStore, $tablePageStore . '.page_id', $tablePage . '.id');
            $data = $data->where($tablePageStore . '.store_id', $storeId);
        }
        $data = $data->first();
        return $data;
    }

    
    /**
     * Get array title page
     * user for admin
     *
     * @return  [type]  [return description]
     */
    public static function getListTitleAdmin($storeId = null)
    {
        $storeCache = $storeId ? $storeId : session('adminStoreId');
        $tableDescription = (new FrontPageDescription)->getTable();
        $table = (new AdminPage)->getTable();
        if (gp247_config_global('cache_status') && gp247_config_global('cache_page')) {
            if (!Cache::has($storeCache.'_cache_page_'.gp247_get_locale())) {
                if (self::$getListTitleAdmin === null) {
                    $data = self::join($tableDescription, $tableDescription.'.page_id', $table.'.id')
                    ->where('lang', gp247_get_locale());
                    if ($storeId) {
                        $tablePageStore = (new FrontPageStore)->getTable();
                        $data = $data->leftJoin($tablePageStore, $tablePageStore . '.page_id', $table . '.id');
                        $data = $data->where($tablePageStore . '.store_id', $storeId);
                    }
                    $data = $data->pluck('title', 'id')->toArray();
                    self::$getListTitleAdmin = $data;
                }
                gp247_cache_set($storeCache.'_cache_page_'.gp247_get_locale(), self::$getListTitleAdmin);
            }
            return Cache::get($storeCache.'_cache_page_'.gp247_get_locale());
        } else {
            if (self::$getListTitleAdmin === null) {
                $data = self::join($tableDescription, $tableDescription.'.page_id', $table.'.id')
                ->where('lang', gp247_get_locale());
                if ($storeId) {
                    $tablePageStore = (new FrontPageStore)->getTable();
                    $data = $data->leftJoin($tablePageStore, $tablePageStore . '.page_id', $table . '.id');
                    $data = $data->where($tablePageStore . '.store_id', $storeId);
                }
                $data = $data->pluck('title', 'id')->toArray();
                self::$getListTitleAdmin = $data;
            }
            return self::$getListTitleAdmin;
        }
    }


    /**
     * Create a new page
     *
     * @param   array  $dataCreate  [$dataCreate description]
     *
     * @return  [type]              [return description]
     */
    public static function createPageAdmin(array $dataCreate)
    {
        return self::create($dataCreate);
    }


    /**
     * Insert data description
     *
     * @param   array  $dataCreate  [$dataCreate description]
     *
     * @return  [type]              [return description]
     */
    public static function insertDescriptionAdmin(array $dataCreate)
    {
        return FrontPageDescription::create($dataCreate);
    }

    /**
     * [getListPageAlias description]
     *
     * @param   [type]  $storeId  [$storeId description]
     *
     * @return  array             [return description]
     */
    public function getListPageAlias($storeId = null):array 
    {
        $storeId = $storeId ? $storeId : session('adminStoreId');
        $arrReturn = [];
        $tablePage = $this->getTable();
        $tablePageStore = (new FrontPageStore)->getTable();
        $data = $this;
        if ($storeId) {
            $data = $this->leftJoin($tablePageStore, $tablePageStore . '.page_id', $tablePage . '.id');
            $data = $data->where($tablePageStore . '.store_id', $storeId);
        }
        $arrReturn = $data->pluck('alias')->toArray();
        return $arrReturn;
    }
}
