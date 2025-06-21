<?php
namespace GP247\Front\Admin\Controllers;

use GP247\Front\Admin\Controllers\RootFrontAdminController;
use GP247\Front\Models\FrontLink;
use GP247\Front\Models\FrontLinkGroup;
use Illuminate\Support\Facades\Validator;

class AdminLinkController extends RootFrontAdminController
{
    protected $arrTarget;

    public function __construct()
    {
        parent::__construct();
        $this->arrTarget = ['_blank' => '_blank', '_self' => '_self'];
    }

    public function arrGroup()
    {
        return  (new FrontLinkGroup)->pluck('name', 'code')->all();
    }

    public function arrCollection()
    {
        return  (new FrontLink)->where('type', 'collection')
            ->pluck('name', 'id')
            ->all();
    }
    public function index()
    {
        $data = [
            'title' => gp247_language_render('admin.link.list'),
            'subTitle' => '',
            'icon' => 'fa fa-indent',
            'urlDeleteItem' => gp247_route_admin('admin_link.delete'),
            'removeList' => 0, // 1 - Enable function delete list item
            'buttonRefresh' => 1, // 1 - Enable button refresh
            'css' => '',
            'js' => '',
        ];

        $listTh = [
            'name' => gp247_language_render('admin.link.name'),
            'url' => gp247_language_render('admin.link.url'),
            'collection' => gp247_language_render('admin.link.collection'),
            'group' => gp247_language_render('admin.link.group'),
            'sort' => gp247_language_render('admin.link.sort'),
            'status' => gp247_language_render('admin.link.status'),
        ];

        if ((gp247_store_check_multi_partner_installed() ||  gp247_store_check_multi_store_installed()) && session('adminStoreId') == GP247_STORE_ID_ROOT) {
            // Only show store info if store is root
            $listTh['shop_store'] = gp247_language_render('front.store_list');
        }
        $listTh['action'] = gp247_language_render('action.title');

        $dataTmp = FrontLink::getLinkListAdmin();

        if ((gp247_store_check_multi_partner_installed() ||  gp247_store_check_multi_store_installed()) && session('adminStoreId') == GP247_STORE_ID_ROOT) {
            $arrId = $dataTmp->pluck('id')->toArray();
            // Only show store info if store is root

            if (function_exists('gp247_store_get_list_domain_of_array_link')) {
                $dataStores = gp247_store_get_list_domain_of_array_link($arrId);
            } else {
                $dataStores = [];
            }
        }

        $dataTr = [];
        foreach ($dataTmp as $key => $row) {
            $dataMap = [
                'name' => ($row['type'] == 'collection' ? '<span class="badge badge-warning"><i class="fas fa-folder-open"></i></span> ' : ' ').gp247_language_render($row['name']),
                'url' => $row['url'],
                'collection' => $this->arrCollection()[$row['collection_id']] ?? $row['collection_id'],
                'group' => $this->arrGroup()[$row['group']] ?? $row['group'],
                'sort' => $row['sort'],
                'status' => $row['status'] ? '<span class="badge badge-success">ON</span>' : '<span class="badge badge-danger">OFF</span>',
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
                        return '<a target="_blank" href="'.$domain.'" title="'.$code.'">'.$code.'</a>';
                    }, $storeTmp);
                    $dataMap['shop_store'] = '<i class="nav-icon fab fa-shopify"></i> '.implode('<br><i class="nav-icon fab fa-shopify"></i> ', $storeTmp);
                } else {
                    $dataMap['shop_store'] = '';
                }
            }
            $arrAction = [];
            $arrAction[] = '<a href="' . gp247_route_admin('admin_link.edit', ['id' => $row['id'] ? $row['id'] : 'not-found-id']) . '" class="dropdown-item"><span title="' . gp247_language_render('action.edit') . '"><i class="fa fa-edit"></i> '.gp247_language_render('action.edit').'</span></a>';
            $arrAction[] = '<a href="#" onclick="deleteItem(\'' . $row['id'] . '\');"  title="' . gp247_language_render('action.delete') . '" class="dropdown-item"><i class="fas fa-trash-alt"></i> '.gp247_language_render('action.delete').'</a>';
            $action = $this->procesListAction($arrAction);
            $dataMap['action'] = $action;
            $dataTr[$row['id']] = $dataMap;
        }

        $data['listTh'] = $listTh;
        $data['dataTr'] = $dataTr;
        $data['pagination'] = $dataTmp->appends(request()->except(['_token', '_pjax']))->links('gp247-core::component.pagination');
        $data['resultItems'] = gp247_language_render('admin.result_item', ['item_from' => $dataTmp->firstItem(), 'item_to' => $dataTmp->lastItem(), 'total' =>  $dataTmp->total()]);

        //menuRight
        $data['menuRight'][] = '<a href="' . gp247_route_admin('admin_link.create') . '" class="btn  btn-success  btn-flat" title="New" id="button_create_new">
        <i class="fa fa-plus" title="' . gp247_language_render('admin.link.add_new') . '"></i>
        </a>';
        $data['menuRight'][] = '<a href="' . gp247_route_admin('admin_link.collection_create') . '" class="btn btn-success btn-flat" title="'.gp247_language_render('admin.link.add_collection_new').'" id="button_create_new">
        <i class="fas fa-network-wired"></i>
        </a>';
        //=menuRight

        return view('gp247-core::screen.list')
            ->with($data);
    }

    /**
     * Form create new item in admin
     * @return [type] [description]
     */
    public function create()
    {
        $data = [
            'title'             => gp247_language_render('admin.link.add_new_title'),
            'subTitle'          => '',
            'title_description' => gp247_language_render('admin.link.add_new_des'),
            'icon'              => 'fa fa-plus',
            'link'              => [],
            'arrTarget'         => $this->arrTarget,
            'arrGroup'          => $this->arrGroup(),
            'arrCollection'     => $this->arrCollection(),
            'layout'            => 'single',
            'url_action'        => gp247_route_admin('admin_link.create'),
        ];
        return view('gp247-front-admin::admin.link')
            ->with($data);
    }

    /**
     * Form create new item in admin
     * @return [type] [description]
     */
    public function collectionCreate()
    {
        $data = [
            'title'             => gp247_language_render('admin.link.add_new_collection_title'),
            'subTitle'          => '',
            'title_description' => gp247_language_render('admin.link.add_new_collection_des'),
            'icon'              => 'fa fa-plus',
            'link'              => [],
            'arrTarget'         => $this->arrTarget,
            'arrGroup'          => $this->arrGroup(),
            'arrCollection'           => $this->arrCollection(),
            'layout'            => 'collection',
            'url_action'        => gp247_route_admin('admin_link.collection_create'),
        ];
        return view('gp247-front-admin::admin.link')
            ->with($data);
    }

    /**
     * Post create new item in admin
     * @return [type] [description]
     */
    public function postCreate()
    {
        $data = request()->all();
        $dataOrigin = request()->all();
        $validator = Validator::make($dataOrigin, [
            'name'   => 'required|string',
            'url'    => 'required|string',
            'group'  => 'required|string',
            'target' => 'required|string',
            'collection_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $dataCreate = [
            'name'     => $data['name'],
            'url'      => $data['url'],
            'target'   => $data['target'],
            'group'    => $data['group'],
            'collection_id'  => $data['collection_id'],
            'type'     => '', // link single
            'sort'     => (int)$data['sort'],
            'status'   => empty($data['status']) ? 0 : 1,
        ];
        $dataCreate = gp247_clean($dataCreate, [], true);
        $link = FrontLink::createLinkAdmin($dataCreate);

        $shopStore        = $data['shop_store'] ?? [session('adminStoreId')];
        $link->stores()->detach();
        if ($shopStore) {
            $link->stores()->attach($shopStore);
        }

        return redirect()->route('admin_link.index')->with('success', gp247_language_render('action.create_success'));
    }

    /**
     * Post create new item in admin
     * @return [type] [description]
     */
    public function postCollectionCreate()
    {
        $data = request()->all();
        $dataOrigin = request()->all();
        $validator = Validator::make($dataOrigin, [
            'name'   => 'required|string',
            'group'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $dataCreate = [
            'name'     => $data['name'],
            'url'      => 'collection',
            'type'     => 'collection',
            'target'   => 'blank',
            'group'    => $data['group'],
            'sort'     => (int)$data['sort'],
            'status'   => empty($data['status']) ? 0 : 1,
        ];
        $dataCreate = gp247_clean($dataCreate, [], true);
        $link = FrontLink::createLinkAdmin($dataCreate);

        $shopStore        = $data['shop_store'] ?? [session('adminStoreId')];
        $link->stores()->detach();
        if ($shopStore) {
            $link->stores()->attach($shopStore);
        }

        return redirect()->route('admin_link.index')->with('success', gp247_language_render('action.create_success'));
    }

    /**
     * Form edit
     */
    public function edit($id)
    {
        $link = FrontLink::getLinkAdmin($id);
        if (!$link) {
            return redirect()->route('admin.data_not_found')->with(['url' => url()->full()]);
        }
        $data = [
            'title'             => gp247_language_render('action.edit'),
            'subTitle'          => '',
            'title_description' => '',
            'icon'              => 'fa fa-edit',
            'link'              => $link,
            'arrTarget'         => $this->arrTarget,
            'arrCollection'           => $this->arrCollection(),
            'arrGroup'          => $this->arrGroup(),
            'layout'            => $link->type == 'collection' ? 'collection': 'single',
            'url_action'        => gp247_route_admin('admin_link.edit', ['id' => $link['id']]),
        ];
        return view('gp247-front-admin::admin.link')
            ->with($data);
    }

    /**
     * update status
     */
    public function postEdit($id)
    {
        $link = FrontLink::getLinkAdmin($id);
        if (!$link) {
            return redirect()->route('admin.data_not_found')->with(['url' => url()->full()]);
        }
        $type = $link->type;

        $data = request()->all();
        $dataOrigin = request()->all();
        $arrValidate = [
            'name'   => 'required|string',
            'group'  => 'required',
        ];
        
        if ($type != "collection") {
            $arrValidate['collection_id'] = 'nullable|string';
            $arrValidate['url'] = 'required|string';
            $arrValidate['target'] = 'required|string';
        }

        $validator = Validator::make($dataOrigin, $arrValidate);

        if ($validator->fails()) {

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        //Edit
        $dataUpdate = [
            'name'     => $data['name'],
            'group'    => $data['group'],
            'sort'     => (int)$data['sort'],
            'status'   => empty($data['status']) ? 0 : 1,
        ];

        if ($type != "collection") {
            $dataUpdate['url'] = $data['url'];
            $dataUpdate['collection_id'] = $data['collection_id'];
            $dataUpdate['target'] = $data['target'];
        }

        $dataUpdate = gp247_clean($dataUpdate, [], true);
        $link->update($dataUpdate);

        $shopStore        = $data['shop_store'] ?? [session('adminStoreId')];
        $link->stores()->detach();
        if ($shopStore) {
            $link->stores()->attach($shopStore);
        }

        return redirect()->route('admin_link.index')->with('success', gp247_language_render('action.edit_success'));
    }

    /*
    Delete list item
    Need mothod destroy to boot deleting in model
    */
    public function deleteList()
    {
        if (!request()->ajax()) {
            return response()->json(['error' => 1, 'msg' => gp247_language_render('admin.method_not_allow')]);
        } else {
            $ids = request('ids');
            $arrID = explode(',', $ids);
            $arrDontPermission = [];
            foreach ($arrID as $key => $id) {
                if (!$this->checkPermisisonItem($id)) {
                    $arrDontPermission[] = $id;
                }
            }
            if (count($arrDontPermission)) {
                return response()->json(['error' => 1, 'msg' => gp247_language_render('admin.remove_dont_permisison') . ': ' . json_encode($arrDontPermission)]);
            }
            FrontLink::destroy($arrID);
            return response()->json(['error' => 0, 'msg' => gp247_language_render('action.delete_success')]);
        }
    }

    /**
     * Check permisison item
     */
    public function checkPermisisonItem($id)
    {
        return FrontLink::getLinkAdmin($id);
    }
}
