<?php
#S-Cart/Core/Front/Models/FrontLink.php
namespace SCart\Core\Front\Models;

use GP247\Core\Admin\Models\AdminStore;
use GP247\Front\Models\FrontLinkStore;
use Illuminate\Database\Eloquent\Model;

class FrontLink extends Model
{
    use \GP247\Core\Admin\Models\ModelTrait;
    use \GP247\Core\Admin\Models\UuidTrait;

    public $table = GP247_DB_PREFIX.'front_link';
    protected $guarded = [];
    protected $connection = GP247_DB_CONNECTION;
    protected static $getGroup = null;
    protected static $getLinksCollection = null;

    public function stores()
    {
        return $this->belongsToMany(AdminStore::class, FrontLinkStore::class, 'link_id', 'store_id');
    }

    public static function getGroup()
    {
        if (!self::$getGroup) {
            $tableLink = (new FrontLink)->getTable();

            $dataSelect = $tableLink.'.*';
            $links = self::selectRaw($dataSelect)
                ->where($tableLink.'.status', 1);
            $storeId = config('app.storeId');
            if (gp247_store_check_multi_domain_installed()) {
                $tableLinkStore = (new FrontLinkStore)->getTable();
                $tableStore = (new AdminStore)->getTable();
                $links = $links->join($tableLinkStore, $tableLinkStore.'.link_id', $tableLink . '.id');
                $links = $links->join($tableStore, $tableStore . '.id', $tableLinkStore.'.store_id');
                $links = $links->where($tableStore . '.status', '1');
                $links = $links->where($tableLinkStore.'.store_id', $storeId);
            }

            $links = $links
                ->orderBy($tableLink.'.sort', 'asc')
                ->orderBy($tableLink.'.id', 'desc')
                ->get()
                ->groupBy('group');
            self::$getGroup = $links;
        }
        return self::$getGroup;
    }

    public static function getLinksCollection()
    {
        if (!self::$getLinksCollection) {
            $tableLink = (new FrontLink)->getTable();

            $dataSelect = $tableLink.'.*';
            $links = self::selectRaw($dataSelect)
                ->where($tableLink.'.status', 1);
            $storeId = config('app.storeId');
            if (gp247_check_multi_shop_installed()) {
                $tableLinkStore = (new FrontLinkStore)->getTable();
                $tableStore = (new AdminStore)->getTable();
                $links = $links->join($tableLinkStore, $tableLinkStore.'.link_id', $tableLink . '.id');
                $links = $links->join($tableStore, $tableStore . '.id', $tableLinkStore.'.store_id');
                $links = $links->where($tableStore . '.status', '1');
                $links = $links->where($tableLinkStore.'.store_id', $storeId);
            }

            // Link not in collection
            $links = $links
            ->orderBy($tableLink.'.sort', 'asc')
            ->orderBy($tableLink.'.id', 'desc')
            ->get();

            $finalData = [];
            $finalChildData = [];
            if ($links->count()) {
                foreach ($links as $link) {
                    if ($link->type != 'collection' && $link->collection_id) {
                        $finalChildData[$link->collection_id][] = [
                            'type' => 'single',
                            'sort' => $link->sort,
                            'data' => $link,
                        ];
                    }
                }

                foreach ($links as $link) {
                    if ($link->type != 'collection' && empty($link->collection_id)) {
                        $finalData[$link->group][] = [
                            'type' => 'single',
                            'sort' => $link->sort,
                            'data' => $link,
                        ];
                    }

                    if ($link->type == 'collection') {
                        $childData = $finalChildData[$link->id] ?? [];
                        if ($childData) {
                            $childData = collect($childData)->sortBy('sort');
                        }
                        $finalData[$link->group][] = [
                            'type' => 'collection',
                            'sort' => $link->sort,
                            'data' => $link,
                            'childs' => $childData,
                        ];
                    }
                }
            }
            
            self::$getLinksCollection = collect($finalData)->sortBy('sort');
        }
        return self::$getLinksCollection;
    }

    protected static function boot()
    {
        parent::boot();
        // before delete() method call this
        static::deleting(
            function ($link) {
                $link->stores()->detach();
            }
        );

        //Uuid
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = gp247_generate_id($type = 'front_link');
            }
        });
    }
}
