<?php
namespace GP247\Front\Admin\Controllers;

use GP247\Core\Models\AdminLanguage;
use GP247\Front\Models\FrontBanner;
use GP247\Front\Models\FrontBannerType;
use GP247\Front\Admin\Controllers\RootFrontAdminController;
use Illuminate\Support\Facades\Validator;

class AdminBannerController extends RootFrontAdminController
{
   
    protected $arrTarget;
    protected $dataType;
    public function __construct()
    {
        parent::__construct();
        $this->arrTarget = ['_blank' => '_blank', '_self' => '_self'];
        $this->dataType  = (new FrontBannerType)->pluck('name', 'code')->all();
        if (gp247_store_check_multi_partner_installed() ||  gp247_store_check_multi_store_installed()) {
            $this->dataType['background-store'] = 'Background store';
            $this->dataType['breadcrumb-store'] = 'Breadcrumb store';
        }
        ksort($this->dataType);
    }

    public function index()
    {
        $data = [
            'title'         => gp247_language_render('admin.banner.list'),
            'subTitle'      => '',
            'urlDeleteItem' => gp247_route_admin('admin_banner.delete'),
            'removeList'    => 0, // 1 - Enable function delete list item
            'buttonRefresh' => 1, // 1 - Enable button refresh
        ];
        $listTh = [
            'image'  => gp247_language_render('admin.banner.image'),
            'title'  => gp247_language_render('admin.banner.title'),
            'url'    => gp247_language_render('admin.banner.url'),
            'sort'   => gp247_language_render('admin.banner.sort'),
            'status' => gp247_language_render('admin.banner.status'),
            'click'  => gp247_language_render('admin.banner.click'),
            'target' => gp247_language_render('admin.banner.target'),
            'type'   => gp247_language_render('admin.banner.type'),
            'shop_store' => gp247_language_render('front.store_list'),
            'action' => gp247_language_render('action.title'),
        ];
        if ((gp247_store_check_multi_partner_installed() ||  gp247_store_check_multi_store_installed()) && session('adminStoreId') == GP247_STORE_ID_ROOT) {
            // Only show store info if store is root
            $listTh['shop_store'] = gp247_language_render('front.store_list');
        }
        $listTh['action'] = gp247_language_render('action.title');

        $sort = gp247_clean(request('sort') ?? 'id_desc');
        $keyword    = gp247_clean(request('keyword') ?? '');
        $arrSort = [
            'sort__desc'      => gp247_language_render('filter_sort.sort_desc'),
            'sort__asc'       => gp247_language_render('filter_sort.sort_asc'),
            'id__asc'         => gp247_language_render('filter_sort.id_asc'),
            'id__desc'        => gp247_language_render('filter_sort.id_desc'),
        ];
        $dataSearch = [
            'keyword'    => $keyword,
            'sort'       => $sort,
            'arrSort'    => $arrSort,
        ];

        $dataTmp = FrontBanner::getBannerListAdmin($dataSearch);
        if ((gp247_store_check_multi_partner_installed() ||  gp247_store_check_multi_store_installed()) && session('adminStoreId') == GP247_STORE_ID_ROOT) {
            $arrId = $dataTmp->pluck('id')->toArray();
            // Only show store info if store is root
            if (function_exists('gp247_get_list_store_of_banner')) {
                $dataStores = gp247_get_list_store_of_banner($arrId);
            } else {
                $dataStores = [];
            }
        }
        $dataTr = [];
        foreach ($dataTmp as $key => $row) {
            $arrAction = [
            '<a href="' . gp247_route_admin('admin_banner.edit', ['id' => $row['id'], 'banner' => request('banner')]) . '"  class="dropdown-item"><span title="' . gp247_language_render('action.edit') . '"><i class="fa fa-edit"></i> '.gp247_language_render('action.edit').'</span></a>',
            ];
            $arrAction[] = '<a href="#" onclick="deleteItem(\'' . $row['id'] . '\');"  title="' . gp247_language_render('action.delete') . '" class="dropdown-item"><i class="fas fa-trash-alt"></i> '.gp247_language_render('action.remove').'</a>';
            $action = $this->procesListAction($arrAction);
            $dataMap = [
                'image' => gp247_image_render($row->getThumb(), '50px', '', $row['title']),
                'title' => $row['title'],
                'url' => $row['url'],
                'sort' => $row['sort'],
                'status' => $row['status'] ? '<span class="badge badge-success">ON</span>' : '<span class="badge badge-danger">OFF</span>',
                'click' => number_format($row['click']),
                'target' => $row['target'],
                'type' => $this->dataType[$row['type']]??'N/A',
            ];
            if ((gp247_store_check_multi_partner_installed() ||  gp247_store_check_multi_store_installed()) && session('adminStoreId') == GP247_STORE_ID_ROOT) {
                // Only show store info if store is root
                if (!empty($dataStores[$row['id']])) {
                    $storeTmp = $dataStores[$row['id']]->pluck('code', 'id')->toArray();
                    $storeTmp = array_map(function ($code) {
                        if (is_null($code)) {
                            return ;
                        }
                        $domain = gp247_store_get_domain_from_code($code);
                        return '<a target=_new href="'.$domain.'">'.$code.'</a>';
                    }, $storeTmp);
                    $dataMap['shop_store'] = '<i class="nav-icon fab fa-shopify"></i> '.implode('<br><i class="nav-icon fab fa-shopify"></i> ', $storeTmp);
                } else {
                    $dataMap['shop_store'] = '';
                }
            }
            $dataMap['action'] = $action;

            $dataTr[] = $dataMap;
        }

        $data['listTh'] = $listTh;
        $data['dataTr'] = $dataTr;
        $data['pagination'] = $dataTmp->appends(request()->except(['_token', '_pjax']))->links('gp247-core::component.pagination');
        $data['resultItems'] = gp247_language_render('admin.result_item', ['item_from' => $dataTmp->firstItem(), 'item_to' => $dataTmp->lastItem(), 'total' =>  $dataTmp->total()]);

        //menuRight
        $data['menuRight'][] = '<a href="' . gp247_route_admin('admin_banner.create') . '" class="btn btn-sm  btn-success  btn-flat" title="New" id="button_create_new">
                           <i class="fa fa-plus" title="'.gp247_language_render('action.add').'"></i>
                           </a>';
        //=menuRight

        //menuSort
        $optionSort = '';
        foreach ($arrSort as $kSort => $vSort) {
            $optionSort .= '<option  ' . (($sort == $kSort) ? "selected" : "") . ' value="' . $kSort . '">' . $vSort . '</option>';
        }
        //=menuSort

        //menuSearch
        $data['topMenuRight'][] = '
                <form action="' . gp247_route_admin('admin_banner.index') . '" id="button_search">
                <div class="input-group input-group" style="width: 350px;">
                    <select class="form-control form-control-sm rounded-0 select2" name="sort" id="sort">
                    '.$optionSort.'
                    </select> &nbsp;
                    <input type="text" name="keyword" class="form-control form-control-sm rounded-0 float-right" placeholder="' . gp247_language_render('admin.user.search_place') . '" value="' . $keyword . '">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                </form>';
        //=menuSearch


        return view('gp247-core::screen.list')
            ->with($data);
    }

    /**
     * Form create new item in admin
     * @return [type] [description]
     */
    public function create()
    {
        $banner = (new FrontBanner);
        $banner = [];
        $data = [
            'title'             => gp247_language_render('admin.user.add_new_title'),
            'subTitle'          => '',
            'title_description' => gp247_language_render('admin.user.add_new_des'),
            'banner'              => $banner,
            'arrTarget'         => $this->arrTarget,
            'dataType'          => $this->dataType,
            'url_action'        => gp247_route_admin('admin_banner.create'),
        ];
        return view('gp247-front-admin::admin.banner')
            ->with($data);
    }

    /**
     * Post create new item in admin
     * @return [type] [description]
     */
    public function postCreate()
    {
        $data = request()->all();
        $arrValidation = [
            'title' => 'required|string|max:200',
            'sort' => 'numeric|min:0',
        ];
        
        // Get custom field validation rules
        $arrValidation = $this->getCustomFieldValidation($arrValidation, FrontBanner::class);

        $validator = $this->validateWithCustomFields(
            $data, 
            $arrValidation,
            [
                'alias.regex' => gp247_language_render('admin.banner.alias_validate'),
                'descriptions.*.title.required' => gp247_language_render('validation.required', ['attribute' => gp247_language_render('admin.banner.title')]),
            ]
        );

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($data);
        }
        $dataCreate = [
            'image'    => $data['image'],
            'url'      => $data['url'],
            'title'    => $data['title'],
            'html'     => $data['html'],
            'type'     => $data['type'] ?? 0,
            'target'   => $data['target'],
            'status'   => empty($data['status']) ? 0 : 1,
            'sort'     => (int) $data['sort'],
        ];
        $dataCreate = gp247_clean($dataCreate, ['html'], true);
        $banner = FrontBanner::createBannerAdmin($dataCreate);

        $shopStore = $data['shop_store'] ?? [session('adminStoreId')];

        $banner->stores()->detach();
        if ($shopStore) {
            $banner->stores()->attach($shopStore);
        }

        //Insert custom fields
        $fields = $data['fields'] ?? [];
        $this->updateCustomFields($fields, $banner->id, FrontBanner::class);

        return redirect()->route('admin_banner.index')->with('success', gp247_language_render('action.create_success'));
    }

    /**
     * Form edit
     */
    public function edit($id)
    {
        $banner = (new FrontBanner);

        $banner = $banner->getBannerAdmin($id);
        if (!$banner) {
            return redirect(gp247_route_admin('admin_banner.index'))->with('error', gp247_language_render('admin.data_not_found'));
        }
        $data = [
            'title'             => gp247_language_render('action.edit'),
            'subTitle'          => '',
            'title_description' =>  '',
            'banner'            => $banner,
            'arrTarget'         => $this->arrTarget,
            'dataType'          => $this->dataType,
            'url_action'        => gp247_route_admin('admin_banner.post_edit', ['id' => $banner['id']]),
        ];
        return view('gp247-front-admin::admin.banner')
            ->with($data);
    }

    /*
     * update status
     */
    public function postEdit($id)
    {
        $banner = FrontBanner::getBannerAdmin($id);
        if (!$banner) {
            return redirect()->route('admin.data_not_found')->with(['url' => url()->full()]);
        }

        $data = request()->all();
        $arrValidation = [
            'title' => 'required|string|max:200',
            'sort' => 'numeric|min:0',
        ];
        
        // Get custom field validation rules
        $arrValidation = $this->getCustomFieldValidation($arrValidation, FrontBanner::class);

        $validator = $this->validateWithCustomFields(
            $data, 
            $arrValidation,
            [
                'alias.regex' => gp247_language_render('admin.banner.alias_validate'),
                'descriptions.*.title.required' => gp247_language_render('validation.required', ['attribute' => gp247_language_render('admin.banner.title')]),
            ]
        );

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($data);
        }
        //Edit
        $dataUpdate = [
            'image' => $data['image'],
            'status' => empty($data['status']) ? 0 : 1,
            'sort' => (int) $data['sort'],
        ];
        $banner->update($dataUpdate);

        $shopStore = $data['shop_store'] ?? [session('adminStoreId')];
        
        $banner->stores()->detach();
        if ($shopStore) {
            $banner->stores()->attach($shopStore);
        }
        //Insert custom fields
        $fields = $data['fields'] ?? [];
        $this->updateCustomFields($fields, $banner->id, FrontBanner::class);

        return redirect()->route('admin_banner.index')->with('success', gp247_language_render('action.edit_success'));
    }

    /*
    Delete list Item
    Need mothod destroy to boot deleting in model
     */
    public function deleteList()
    {
        if (!request()->ajax()) {
            return response()->json(['error' => 1, 'msg' => gp247_language_render('admin.method_not_allow')]);
        } else {
            $ids = request('ids');
            $arrID = explode(',', $ids);
            FrontBanner::destroy($arrID);
            return response()->json(['error' => 0, 'msg' => gp247_language_render('action.delete_success')]);
        }
    }
}
