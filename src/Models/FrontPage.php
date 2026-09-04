<?php
#GP247/Front/Models/FrontPage.php
namespace GP247\Front\Models;

use Illuminate\Database\Eloquent\Model;
use Cache;
use GP247\Core\Models\AdminStore;

class FrontPage extends Model
{
    
    use \GP247\Core\Models\ModelTrait;
    use \GP247\Core\Models\UuidTrait;

    public $table          = GP247_DB_PREFIX.'front_page';
    protected $connection  = GP247_DB_CONNECTION;
    protected $guarded     = [];

    // WHY: keyed by store id (see getListTitleAdmin) so per-store title lists do not
    // bleed within one request. ADR multi-store_admin-store-scope-seam (leak L3).
    protected static $getListTitleAdmin = [];
    protected static $getListPageGroupByParentAdmin = null;

    /**
     * The store that owns this page (1-1 ownership).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @aidlc-unit compat-foundation
     * @aidlc-story US-FADM-store-single-owner
     * @aidlc-adr multi-store_one-to-one-store-ownership
     */
    public function store()
    {
        return $this->belongsTo(AdminStore::class, 'store_id', 'id');
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
        return $this->getText()->name ?? '';
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
            // WHY: 1-1 ownership — filter by the page's own store_id column and
            // still require the owning store to be active (join admin_store).
            $tableStore = (new AdminStore)->getTable();
            $page = $page->join($tableStore, $tableStore . '.id', $this->getTable() . '.store_id');
            $page = $page->where($tableStore . '.status', '1');
            $page = $page->where($this->getTable() . '.store_id', $storeId);
        }

        if ($type === null) {
            $page = $page->where($this->getTable() .'.id', $key);
        } else {
            // WHY: qualify with the base table. This query left-joins the
            // description table (and admin_store when multi-store is installed),
            // so a bare column name would be ambiguous (SQL 1052).
            $page = $page->where($this->getTable() . '.' . $type, $key);
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
                // Store ownership is a scalar column now; nothing to detach.

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
            // WHY: 1-1 ownership — filter by the page's own store_id column and
            // still require the owning store to be active (join admin_store).
            $tableStore = (new AdminStore)->getTable();
            $query = $query->join($tableStore, $tableStore . '.id', $this->getTable() . '.store_id');
            $query = $query->where($tableStore . '.status', '1');
            $query = $query->where($this->getTable() . '.store_id', $storeId);
        }

        //search keyword
        if ($this->gp247_keyword !='') {
            $query = $query->where(function ($sql) use ($tableDescription) {
                $sql->where($tableDescription . '.name', 'like', '%' . $this->gp247_keyword . '%')
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
            $pageList = $pageList->where($tablePage . '.store_id', $storeId);
        }

        if ($keyword) {
            $pageList = $pageList->where(function ($sql) use ($tableDescription, $keyword) {
                $sql->where($tableDescription . '.name', 'like', '%' . $keyword . '%');
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
            $tablePage = (new FrontPage)->getTable();
            $data = $data->where($tablePage . '.store_id', $storeId);
        }
        $data = $data->first();
        return $data;
    }

    
    /**
     * Get array title page (id => name) for admin, scoped to the active store.
     *
     * WHY store scope: since 1-1 ownership every page carries its own store_id, so a
     * sub-store admin must only see its own pages. Resolve the store from the explicit
     * arg, else the session, else ROOT; ROOT = all (single-store unchanged, no extra
     * where). The no-arg call previously read the session but never filtered — that was
     * the L2 leak. The static memo is keyed by store so two stores in one request do not
     * bleed (L3). ADR multi-store_admin-store-scope-seam.
     *
     * @param int|string|null $storeId Explicit store id; null = use the session context.
     * @return array<int|string, string> Page id => localized name for the resolved store.
     *
     * @aidlc-unit multi-store-pro
     * @aidlc-story US-multi-store-pro-admin-store-switcher
     * @aidlc-adr multi-store_admin-store-scope-seam
     */
    public static function getListTitleAdmin($storeId = null)
    {
        $storeCache = $storeId ?: (session('adminStoreId') ?: GP247_STORE_ID_ROOT);
        $tableDescription = (new FrontPageDescription)->getTable();
        // WHY: (new self) — this model IS the front_page table; the former (new AdminPage)
        // referenced a class that does not exist (a latent fatal on any call).
        $table = (new self)->getTable();
        $buildForStore = function () use ($tableDescription, $table, $storeCache) {
            if (!isset(self::$getListTitleAdmin[$storeCache])) {
                $query = self::join($tableDescription, $tableDescription.'.page_id', $table.'.id')
                    ->where('lang', gp247_get_locale());
                // WHY: ROOT = all (single-store unchanged); sub-store filters to its own rows.
                if ($storeCache != GP247_STORE_ID_ROOT) {
                    $query = $query->where($table.'.store_id', $storeCache);
                }
                self::$getListTitleAdmin[$storeCache] = $query->pluck('name', 'id')->toArray();
            }
            return self::$getListTitleAdmin[$storeCache];
        };
        if (gp247_config_global('cache_status') && gp247_config_global('cache_page')) {
            // Embed the group version so gp247_cache_clear('cache_page') (a version
            // bump) invalidates every store x locale variant at once — the `database`
            // cache driver cannot wildcard-forget the old per-store/locale keys.
            $cacheKey = $storeCache.'_cache_page_'.gp247_get_locale().'_v'.gp247_cache_version('page');
            if (!Cache::has($cacheKey)) {
                gp247_cache_set($cacheKey, $buildForStore());
            }
            return Cache::get($cacheKey);
        }
        return $buildForStore();
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
        $data = $this;
        if ($storeId) {
            $data = $this->where($tablePage . '.store_id', $storeId);
        }
        $arrReturn = $data->pluck('alias')->toArray();
        return $arrReturn;
    }
}
