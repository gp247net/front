<?php
/**
 * Template format 1.0
 */
#App\GP247\Templates\GP247Front\AppConfig.php
namespace App\GP247\Templates\GP247Front;

use GP247\Core\Models\AdminConfig;
use GP247\Core\Models\AdminStore;
use GP247\Core\Models\AdminHome;
use GP247\Front\Models\FrontLayoutBlock;
use GP247\Front\Models\FrontBanner;
use GP247\Front\Models\FrontBannerStore;
use GP247\Core\ExtensionConfigDefault;
class AppConfig extends ExtensionConfigDefault
{
    public function __construct()
    { 
        //Read config from gp247.json
        $config = file_get_contents(__DIR__.'/gp247.json');
        $config = json_decode($config, true);
    	$this->configGroup = $config['configGroup'];
        $this->configKey = $config['configKey'];
        $this->configCode = $config['configCode'] ?? $this->configKey;
        $this->requireCore = $config['requireCore'] ?? [];
        $this->requirePackages = $config['requirePackages'] ?? [];
        $this->requireExtensions = $config['requireExtensions'] ?? [];

        //Path
        $this->appPath = $this->configGroup . '/' . $this->configKey;
        //Language
        $this->title = trans($this->appPath.'::lang.title');
        //Image logo or thumb
        $this->image = $this->appPath.'/'.$config['image'];
        //
        $this->version = $config['version'];
        $this->auth = $config['auth'];
        $this->link = $config['link'];
    }

    public function install()
    {
        $check = AdminConfig::where('key', $this->configKey)
            ->where('group', $this->configGroup)->first();
        if ($check) {
            //Check Plugin key exist
            $return = ['error' => 1, 'msg' =>  gp247_language_render('admin.extension.plugin_exist')];
        } else {
            //Insert plugin to config
            $dataInsert = [
                [
                    'group'  => $this->configGroup,
                    'code'    => $this->configCode,
                    'key'    => $this->configKey,
                    'sort'   => 0,
                    'store_id' => GP247_STORE_ID_GLOBAL,
                    'value'  => self::ON, //Enable extension
                    'detail' => $this->appPath.'::lang.title',
                ],
            ];

            try {
                AdminConfig::insert(
                    $dataInsert
                );
                $return = ['error' => 0, 'msg' => gp247_language_render('admin.extension.install_success')];
            } catch (\Throwable $e) {
                $return = ['error' => 1, 'msg' => $e->getMessage()];
            }
        }

        //Setup store
        $this->setupStore();

        return $return;
    }

    public function uninstall()
    {
        $return = ['error' => 0, 'msg' => ''];
        //Please delete all values inserted in the installation step
        try {
            (new AdminConfig)
            ->where('key', $this->configKey)
            ->orWhere('code', $this->configKey.'_config')
            ->delete();

            //Admin config home
            AdminHome::where('extension', $this->appPath)->delete();

            $return = ['error' => 0, 'msg' => gp247_language_render('admin.extension.uninstall_success')];
        } catch (\Throwable $e) {
            $return = ['error' => 1, 'msg' => $e->getMessage()];
        }

        //Remove setup for all stores
        $this->removeStore();

        return $return;
    }
    
    public function enable()
    {
        $return = ['error' => 0, 'msg' => ''];
        $process = (new AdminConfig)
            ->where('group', $this->configGroup)
            ->where('key', $this->configKey)
            ->update(['value' => self::ON]);
            
        //Admin config home
        AdminHome::where('extension', $this->appPath)->update(['status' => 1]);

        if (!$process) {
            $return = ['error' => 1, 'msg' => gp247_language_render('admin.extension.action_error', ['action' => 'Enable'])];
        }
        $return = ['error' => 0, 'msg' => gp247_language_render('admin.extension.enable_success')];
        return $return;
    }

    public function disable()
    {
        $return = ['error' => 0, 'msg' => ''];
        $process = (new AdminConfig)
            ->where('group', $this->configGroup)
            ->where('key', $this->configKey)
            ->update(['value' => self::OFF]);
        if (!$process) {
            $return = ['error' => 1, 'msg' => gp247_language_render('admin.extension.action_error', ['action' => 'Disable'])];
        }

        //Admin config home
        AdminHome::where('extension', $this->appPath)->update(['status' => 0]);

        return $return;
    }

    /**
     * Get info template
     *
     * @return  [type]  [return description]
     */
    public function getInfo()
    {
        $arrData = [
            'title' => $this->title,
            'key' => $this->configKey,
            'image' => $this->image,
            'permission' => self::ALLOW,
            'version' => $this->version,
            'auth' => $this->auth,
            'link' => $this->link,
            'appPath' => $this->appPath
        ];

        return $arrData;
    }

    // Remove setup for store


    // Remove setup for store

    public function removeStore($storeId = null)
    {
        if ($storeId) {
            FrontLayoutBlock::where('template', $this->configKey)
                ->where('store_id', $storeId)
                ->delete();
            $tableBanner = (new FrontBanner)->getTable();
            $tableBannerStore = (new FrontBannerStore)->getTable();
            $idBanners = (new FrontBanner)
                ->join($tableBannerStore, $tableBannerStore.'.banner_id', $tableBanner.'.id')
                ->where($tableBanner.'.name', 'like', '%('.$this->configKey.')%')
                ->where($tableBannerStore.'.store_id', $storeId)
                ->pluck('id');
    
            if ($idBanners) {
                FrontBannerStore::whereIn('banner_id', $idBanners)
                ->delete();
                FrontBanner::whereIn('id', $idBanners)
                ->delete();
            }
        } else {
            // Remove from all stories
            FrontLayoutBlock::where('template', $this->configKey)
                ->delete();
            $idBanners = FrontBanner::where('name', 'like', '%('.$this->configKey.')%')
                ->pluck('id');
            if ($idBanners) {
                FrontBannerStore::whereIn('banner_id', $idBanners)
                ->delete();
                FrontBanner::whereIn('id', $idBanners)
                ->delete();
            }
        }
    }

    // Setup for store

    public function setupStore($storeId = null)
    {
        if ($storeId) {
        
            // Change template for store
            AdminStore::where('id', $storeId)
                ->update(['template' => $this->configKey]);

            // Insert layout block for store
            $dataInsert[] = [
                'id'       => $this->uuid(),
                'name'     => 'Banner top ('.$this->configKey.')',
                'position' => 'top',
                'page'     => 'front_home',
                'text'     => 'banner_image',
                'type'     => 'view',
                'sort'     => 10,
                'status'   => 1,
                'template' => $this->configKey,
                'store_id' => $storeId,
            ];

            $dataInsert[] = [
                'id'       => $this->uuid(),
                'name'     => 'Welcome ('.$this->configKey.')',
                'position' => 'bottom',
                'page'     => 'front_home',
                'text'     => 'welcome',
                'type'     => 'view',
                'sort'     => 25,
                'status'   => 1,
                'template' => $this->configKey,
                'store_id' => $storeId,
            ];

            $dataInsert[] = [
                'id'       => $this->uuid(),
                'name'     => 'Email subscribe ('.$this->configKey.')',
                'position' => 'bottom',
                'page'     => 'front_home',
                'text'     => 'email_subscribe',
                'type'     => 'view',
                'sort'     => 1,
                'status'   => 1,
                'template' => $this->configKey,
                'store_id' => $storeId,
            ];

            // gp247/shop is optional — only wire up shop-backed blocks if its models are installed
            if (class_exists(\GP247\Shop\Models\ShopCategory::class)) {
                $dataInsert[] = [
                    'id'       => $this->uuid(),
                    'name'     => 'Category home ('.$this->configKey.')',
                    'position' => 'bottom',
                    'page'     => 'front_home',
                    'text'     => 'shop_category_home',
                    'type'     => 'view',
                    'sort'     => 20,
                    'status'   => 1,
                    'template' => $this->configKey,
                    'store_id' => $storeId,
                ];
            }

            if (class_exists(\GP247\Shop\Models\ShopProduct::class)) {
                $dataInsert[] = [
                    'id'       => $this->uuid(),
                    'name'     => 'Flash sale ('.$this->configKey.')',
                    'position' => 'bottom',
                    'page'     => 'front_home',
                    'text'     => 'shop_flash_sale',
                    'type'     => 'view',
                    'sort'     => 15,
                    'status'   => 1,
                    'template' => $this->configKey,
                    'store_id' => $storeId,
                ];

                $dataInsert[] = [
                    'id'       => $this->uuid(),
                    'name'     => 'Product home ('.$this->configKey.')',
                    'position' => 'bottom',
                    'page'     => 'front_home',
                    'text'     => 'shop_product_home',
                    'type'     => 'view',
                    'sort'     => 5,
                    'status'   => 1,
                    'template' => $this->configKey,
                    'store_id' => $storeId,
                ];

                $dataInsert[] = [
                    'id'       => $this->uuid(),
                    'name'     => 'Product last view ('.$this->configKey.')',
                    'position' => 'left',
                    'page'     => 'shop_product_list',
                    'text'     => 'shop_product_last_view',
                    'type'     => 'view',
                    'sort'     => 5,
                    'status'   => 1,
                    'template' => $this->configKey,
                    'store_id' => $storeId,
                ];
            }

            FrontLayoutBlock::insert($dataInsert);
        
            $modelBanner = new FrontBanner;
            $modelBannerStore = new FrontBannerStore; 
        
            $idBanner1 = $modelBanner->create(['id' => $this->uuid(), 'name' => 'Banner home 1 ('.$this->configKey.')', 'image' => 'https://picsum.photos/1000/400?random=1', 'target' => '_self', 'html' => '', 'status' => 1, 'type' => 'banner']);
            $modelBannerStore->create(['banner_id' => $idBanner1->id, 'store_id' => $storeId]);
            $idBanner2 = $modelBanner->create(['id' => $this->uuid(), 'name' => 'Banner home 2 ('.$this->configKey.')', 'image' => 'https://picsum.photos/1000/400?random=2', 'target' => '_self', 'html' => '', 'status' => 1, 'type' => 'banner']);
            $modelBannerStore->create(['banner_id' => $idBanner2->id, 'store_id' => $storeId]);
            $idBanner3 = $modelBanner->create(['id' => $this->uuid(), 'name' => 'Banner breadcrumb ('.$this->configKey.')', 'image' => 'https://picsum.photos/1000/400?random=3', 'target' => '_self', 'html' => '', 'status' => 1, 'type' => 'breadcrumb']);
            $modelBannerStore->create(['banner_id' => $idBanner3->id, 'store_id' => $storeId]);
            $idBanner4 = $modelBanner->create(['id' => $this->uuid(), 'name' => 'Banner store ('.$this->configKey.')', 'image' => 'https://picsum.photos/1000/400?random=4', 'target' => '_self', 'html' => '', 'status' => 1, 'type' => 'banner-store']);
            $modelBannerStore->create(['banner_id' => $idBanner4->id, 'store_id' => $storeId]);
        } else {
            return null;
        }
    }
    private function uuid() {
        // While install template from command, cannot load helper gp247
        if(app()->runningInConsole( )) {
            return (string)\Illuminate\Support\Str::orderedUuid();
        } else {
            return gp247_uuid();
        }
    }
}
