<?php
namespace GP247\Front\Admin\Controllers;

use GP247\Front\Admin\Controllers\RootFrontAdminController;
use GP247\Front\Models\FrontLayoutBlock;
use GP247\Core\Models\AdminStore;
use GP247\Front\Models\FrontPage;
use Illuminate\Support\Facades\Validator;

class AdminLayoutBlockController extends RootFrontAdminController
{
    public $layoutType;
    public $layoutPage;
    public $layoutPosition;
    public function __construct()
    {
        parent::__construct();
        $this->layoutPage = config('gp247-config.front.layout_page');
        $this->layoutPosition = config('gp247-config.front.layout_position');
        $this->layoutType = ['html'=>'Html', 'view' => 'View', 'page' => 'Page'];
    }

    public function index()
    {
        $data = [
            'title'         => gp247_language_render('admin.layout_block.list'),
            'subTitle'      => '',
            'icon'          => 'fa fa-indent',
            'urlDeleteItem' => gp247_route_admin('admin_layout_block.delete'),
            'removeList'    => 0, // 1 - Enable function delete list item
            'buttonRefresh' => 1, // 1 - Enable button refresh
            'css'           => '',
            'js'            => '',
        ];

        $listTh = [
            'name'     => gp247_language_render('admin.layout_block.name'),
            'type'     => gp247_language_render('admin.layout_block.type'),
            'position' => gp247_language_render('admin.layout_block.position'),
            'page'     => gp247_language_render('admin.layout_block.page'),
            'text'     => gp247_language_render('admin.layout_block.text'),
            'sort'     => gp247_language_render('admin.layout_block.sort'),
            'status'   => gp247_language_render('admin.layout_block.status'),
            'template'   => 'Template',
        ];
        if ((gp247_store_check_multi_partner_installed() ||  gp247_store_check_multi_store_installed()) && session('adminStoreId') == GP247_STORE_ID_ROOT) {
            // Only show store info if store is root
            $listTh['shop_store'] = gp247_language_render('front.store_list');
        }
        $listTh['action'] = gp247_language_render('action.title');

        $dataTmp = (new FrontLayoutBlock)->getStoreBlockContentListAdmin();

        $dataTr = [];
        foreach ($dataTmp as $key => $row) {
            $htmlPage = '';
            if (!$row['page']) {
                $htmlPage .= '';
            } elseif (strpos($row['page'], '*') !== false) {
                $htmlPage .= gp247_language_render('admin.layout_block_page.all');
            } else {
                $arrPage = explode(',', $row['page']);
                foreach ($arrPage as $key => $value) {
                    $htmlPage .= '+' . $value . '<br>';
                }
            }

            $type_name = $this->layoutType[$row['type']] ?? '';
            if ($row['type'] == 'view') {
                $type_name = '<span class="badge badge-warning">' . $type_name . '</span>';
            } elseif ($row['type'] == 'html') {
                $type_name = '<span class="badge badge-primary">' . $type_name . '</span>';
            }        

            $storeTmp = [
                'name' => $row['name'],
                'type' => $type_name,
                'position' => htmlspecialchars(gp247_language_render($this->layoutPosition[$row['position']] ?? $row['position']) ?? ''),
                'page' => $htmlPage,
                'text' => htmlspecialchars($row['text']),
                'sort' => $row['sort'],
                'status' => $row['status'] ? '<span class="badge badge-success">ON</span>' : '<span class="badge badge-danger">OFF</span>',
                'template' => $row['template'],
            ];

            if ((gp247_store_check_multi_partner_installed() ||  gp247_store_check_multi_store_installed()) && session('adminStoreId') == GP247_STORE_ID_ROOT) {
                $storeCode = gp247_store_get_list_code()[$row['store_id']] ?? '';
                // Only show store info if store is root
                $storeTmp['shop_store'] = '<i class="nav-icon fab fa-shopify"></i><a target=_new href="'.gp247_store_get_domain_from_code($storeCode).'">'.$storeCode.'</a>';
            }
            $arrAction = [];
            $arrAction[] =  '<a href="' . gp247_route_admin('admin_layout_block.edit', ['id' => $row['id'] ? $row['id'] : 'not-found-id']) . '" class="dropdown-item"><span title="' . gp247_language_render('action.edit') . '"><i class="fa fa-edit"></i> '.gp247_language_render('action.edit').'</span></a>';
            $arrAction[] =  '<a href="#" onclick="deleteItem(\'' . $row['id'] . '\');"  title="' . gp247_language_render('action.delete') . '" class="dropdown-item"><i class="fas fa-trash-alt"></i> '.gp247_language_render('action.delete').'</a>';
            $action = $this->procesListAction($arrAction);

            $storeTmp['action'] = $action;
            $dataTr[$row['id']] = $storeTmp;

        }

        $data['listTh'] = $listTh;
        $data['dataTr'] = $dataTr;
        $data['pagination'] = $dataTmp->appends(request()->except(['_token', '_pjax']))->links('gp247-core::component.pagination');
        $data['resultItems'] = gp247_language_render('admin.result_item', ['item_from' => $dataTmp->firstItem(), 'item_to' => $dataTmp->lastItem(), 'total' =>  $dataTmp->total()]);

        //menuRight
        $data['menuRight'][] = '
                           <a href="' . gp247_route_admin('admin_layout_block.create') . '" class="btn  btn-success  btn-flat" title="New" id="button_create_new">
                           <i class="fa fa-plus" title="'.gp247_language_render('action.add').'"></i>
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
        $storeId = session('adminStoreId');
        $listViewBlock = $this->getListViewBlock($storeId);
        $listViewPage = $this->getListPageBlock($storeId);
        $data = [
            'title'             => gp247_language_render('admin.layout_block.add_new_title'),
            'subTitle'          => '',
            'title_description' => gp247_language_render('admin.layout_block.add_new_des'),
            'icon'              => 'fa fa-plus',
            'layoutPosition'    => $this->layoutPosition,
            'layoutPage'        => $this->layoutPage,
            'layoutType'        => $this->layoutType,
            'listViewBlock'     => $listViewBlock,
            'listViewPage'     => $listViewPage,
            'storeId'           => $storeId,
            'layout'            => [],
            'url_action'        => gp247_route_admin('admin_layout_block.post_create'),
        ];
        return view('gp247-front-admin::admin.layout_block')
            ->with($data);
    }

    /**
     * Post create new item in admin
     * @return [type] [description]
     */
    public function postCreate()
    {
        $data = request()->all();
        $storeId = $data['store_id'] ?? session('adminStoreId');
        $store = AdminStore::find($storeId);
        $dataOrigin = request()->all();
        $validator = Validator::make($dataOrigin, [
            'name' => 'required',
            'page' => 'required',
            'position' => 'required',
            'text' => 'required',
            'type' => 'required',
        ]);

        if ($validator->fails()) {

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $dataCreate = [
            'id'       => gp247_uuid(),
            'name'     => $data['name'],
            'position' => $data['position'],
            'page'     => in_array('*', $data['page'] ?? []) ? '*' : implode(',', $data['page'] ?? []),
            'text'     => $data['text'],
            'type'     => $data['type'],
            'sort'     => (int) $data['sort'],
            'template' => $store->template ?? GP247_TEMPLATE_FRONT_DEFAULT,
            'status'   => (empty($data['status']) ? 0 : 1),
            'store_id' => $storeId,
        ];
        $dataCreate = gp247_clean($dataCreate, ['text'], true);
        FrontLayoutBlock::createStoreBlockContentAdmin($dataCreate);
        
        return redirect()->route('admin_layout_block.index')->with('success', gp247_language_render('action.create_success'));
    }

    /**
     * Form edit
     */
    public function edit($id)
    {
        $layout = (new FrontLayoutBlock)->getStoreBlockContentAdmin($id);
        if (!$layout) {
            return redirect()->route('admin.data_not_found')->with(['url' => url()->full()]);
        }

        $listViewBlock = $this->getListViewBlock($layout->store_id);
        $listViewPage = $this->getListPageBlock($layout->store_id);

        $data = [
            'title' => gp247_language_render('action.edit'),
            'subTitle' => '',
            'title_description' => '',
            'icon' => 'fa fa-edit',
            'layoutPosition' => $this->layoutPosition,
            'layoutPage' => $this->layoutPage,
            'layoutType' => $this->layoutType,
            'listViewBlock' => $listViewBlock,
            'listViewPage' => $listViewPage,
            'layout' => $layout,
            'storeId' => $layout->store_id,
            'url_action' => gp247_route_admin('admin_layout_block.edit', ['id' => $layout['id']]),
        ];
        return view('gp247-front-admin::admin.layout_block')
            ->with($data);
    }

    /**
     * update status
     */
    public function postEdit($id)
    {
        $data = request()->all();
        $dataOrigin = request()->all();
        $storeId = $data['store_id'] ?? session('adminStoreId');
        $store = AdminStore::find($storeId);

        $layout = (new FrontLayoutBlock)->getStoreBlockContentAdmin($id, $storeId);
        if (!$layout) {
            return redirect()->route('admin.data_not_found')->with(['url' => url()->full()]);
        }
        $validator = Validator::make($dataOrigin, [
            'name' => 'required',
        ], [
            'name.required' => gp247_language_render('validation.required'),
        ]);

        if ($validator->fails()) {

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        //Edit
        $dataUpdate = [
            'name' => $data['name'],
            'position' => $data['position'],
            'page' => in_array('*', $data['page'] ?? []) ? '*' : implode(',', $data['page'] ?? []),
            'text' => $data['text'],
            'type' => $data['type'],
            'sort' => (int) $data['sort'],
            'template' => $store->template ?? GP247_TEMPLATE_FRONT_DEFAULT,
            'status' => (empty($data['status']) ? 0 : 1),
            'store_id' => $storeId,
        ];
        $dataUpdate = gp247_clean($dataUpdate, ['text'], true);
        $layout->update($dataUpdate);
        
        return redirect()->route('admin_layout_block.index')->with('success', gp247_language_render('action.edit_success'));
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
            FrontLayoutBlock::destroy($arrID);
            return response()->json(['error' => 0, 'msg' => '']);
        }
    }

    /**
     * Get view block
     *
     * @return  [type]  [return description]
     */
    public function getListViewBlock($storeId = null)
    {
        $arrView = [];
        foreach (glob(app_path() . "/GP247/Templates/".gp247_store_info(key:'template', storeId: $storeId)."/blocks/*.blade.php") as $file) {
            if (file_exists($file)) {
                $arr = explode('/', $file);
                $arrView[substr(end($arr), 0, -10)] = substr(end($arr), 0, -10);
            }
        }
        return $arrView;
    }

    /**
     * Get list alias page
     *
     * @return  [type]  [return description]
     */
    public function getListPageBlock($storeId = null)
    {
        $arrPage = (new FrontPage)->getListPageAlias($storeId);
        return $arrPage;
    }

    
    /**
     * Check permisison item
     */
    public function checkPermisisonItem($id)
    {
        return (new FrontLayoutBlock)->getStoreBlockContentAdmin($id);
    }

    /**
     * Get json list view block
     *
     * @return void
     */
    public function getListViewBlockHtml() {
        if (!request()->ajax()) {
            $html =  '';
        } else {
            $html = '<select name="text" class="form-control text">';
            $storeId = request('store_id');
            $arrView = $this->getListViewBlock($storeId);
            foreach ($arrView as $key => $value) {
                $html .='<option value="'.$key.'">'.$value;
                $html .='</option>';
            }
            $html .='</select>';
            $html .='<span class="form-text"><i class="fa fa-info-circle"></i>';
            $html .= gp247_language_render('admin.layout_block.helper_view', ['template' => gp247_store_info(key:'template', storeId:$storeId)]);
            $html .='</span>';
        }
        return $html;
    }

    /**
     * Get json list page block html
     *
     * @return void
     */
    public function getListPageBlockHtml() {
        if (!request()->ajax()) {
            $html =  '';
        } else {
            $html = '<select name="text" class="form-control text">';
            $storeId = request('store_id');
            $arrPage = $this->getListPageBlock($storeId);
            foreach ($arrPage as $value) {
                $html .='<option value="'.$value.'">'.$value;
                $html .='</option>';
            }
            $html .='</select>';
        }
        return $html;
    }
}
