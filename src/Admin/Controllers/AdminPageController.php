<?php
namespace GP247\Front\Admin\Controllers;

use GP247\Core\Models\AdminLanguage;
use GP247\Front\Models\FrontPage;
use GP247\Front\Admin\Controllers\RootFrontAdminController;
use Illuminate\Support\Facades\Validator;
use GP247\Front\Models\FrontPageDescription;

class AdminPageController extends RootFrontAdminController
{
   
    public $languages;
    public function __construct()
    {
        parent::__construct();
        $this->languages = AdminLanguage::getListActive();
    }

    public function index()
    {
        $data = [
            'title'         => gp247_language_render('admin.page.list'),
            'subTitle'      => '',
            'urlDeleteItem' => gp247_route_admin('admin_page.delete'),
            'removeList'    => 0, // 1 - Enable function delete list item
            'buttonRefresh' => 1, // 1 - Enable button refresh
        ];
        $listTh = [
            'title'  => gp247_language_render('admin.page.title'),
            'image'  => gp247_language_render('admin.page.image'),
            'alias'  => gp247_language_render('admin.page.alias'),
            'status' => gp247_language_render('admin.page.status'),
        ];
        if ((gp247_store_check_multi_partner_installed() ||  gp247_store_check_multi_store_installed()) && session('adminStoreId') == GP247_STORE_ID_ROOT) {
            // Only show store info if store is root
            $listTh['shop_store'] = gp247_language_render('front.store_list');
        }
        $listTh['action'] = gp247_language_render('action.title');

        $sort = gp247_clean(request('sort') ?? 'id_desc');
        $keyword    = gp247_clean(request('keyword') ?? '');
        $arrSort = [
            'id__desc'       => gp247_language_render('filter_sort.id_desc'),
            'id__asc'        => gp247_language_render('filter_sort.id_asc'),
        ];
        $dataSearch = [
            'keyword'    => $keyword,
            'sort' => $sort,
            'arrSort'    => $arrSort,
        ];
        $dataTmp = FrontPage::getPageListAdmin($dataSearch);
        if ((gp247_store_check_multi_partner_installed() ||  gp247_store_check_multi_store_installed()) && session('adminStoreId') == GP247_STORE_ID_ROOT) {
            $arrId = $dataTmp->pluck('id')->toArray();
            // Only show store info if store is root
            if (function_exists('gp247_get_list_store_of_page')) {
                $dataStores = gp247_get_list_store_of_page($arrId);
            } else {
                $dataStores = [];
            }
        }
        $dataTr = [];
        foreach ($dataTmp as $key => $row) {
            $arrAction = [
            '<a href="' . gp247_route_admin('admin_page.edit', ['id' => $row['id'], 'page' => request('page')]) . '"  class="dropdown-item"><i class="fa fa-edit"></i> '.gp247_language_render('action.edit').'</a>',
            ];
            $arrAction[] = '<a href="#" onclick="deleteItem(\'' . $row['id'] . '\');"  title="' . gp247_language_render('action.delete') . '" class="dropdown-item"><i class="fas fa-trash-alt"></i> '.gp247_language_render('action.remove').'</a>';
            $arrAction[] = '<a target=_new href="' . gp247_route_front('front.page.detail', ['alias' => $row['alias']]) . '" class="dropdown-item"><i class="fas fa-external-link-alt"></i> '.gp247_language_render('action.link').'</a>';
            $action = $this->procesListAction($arrAction);
            $dataMap = [
                'title' => $row['title'],
                'image' => gp247_image_render($row['image'], '50px', '', $row['title']),
                'alias' => $row['alias'],
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
            $dataMap['action'] = $action;
            $dataTr[] = $dataMap;
        }

        $data['listTh'] = $listTh;
        $data['dataTr'] = $dataTr;
        $data['pagination'] = $dataTmp->appends(request()->except(['_token', '_pjax']))->links('gp247-core::component.pagination');
        $data['resultItems'] = gp247_language_render('admin.result_item', ['item_from' => $dataTmp->firstItem(), 'item_to' => $dataTmp->lastItem(), 'total' =>  $dataTmp->total()]);

        //menuRight
        $data['menuRight'][] = '<a href="' . gp247_route_admin('admin_page.create') . '" class="btn btn-sm  btn-success  btn-flat" title="New" id="button_create_new">
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
                <form action="' . gp247_route_admin('admin_page.index') . '" id="button_search">
                <div class="input-group input-group">
                    <select class="form-control form-control-sm rounded-0 select2" name="sort" id="sort">
                    '.$optionSort.'
                    </select> &nbsp;
                    <input type="text" name="keyword" class="form-control form-control-sm rounded-0 float-right" placeholder="' . gp247_language_render('search.placeholder') . '" value="' . $keyword . '">
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
        $page = (new FrontPage);
        $page = [];
        $data = [
            'title'             => gp247_language_render('admin.user.add_new_title'),
            'subTitle'          => '',
            'title_description' => gp247_language_render('admin.user.add_new_des'),
            'languages'         => $this->languages,
            'page'              => $page,
            'url_action'        => gp247_route_admin('admin_page.create'),
        ];
        return view('gp247-front-admin::admin.page')
            ->with($data);
    }

    /**
     * Post create new item in admin
     * @return [type] [description]
     */
    public function postCreate()
    {
        $data = request()->all();
        $langFirst = array_key_first(gp247_language_all()->toArray());
        $data['alias'] = !empty($data['alias'])?$data['alias']:$data['descriptions'][$langFirst]['title'];
        $data['alias'] = gp247_word_format_url($data['alias']);
        $data['alias'] = gp247_word_limit($data['alias'], 100);
        $arrValidation = [
            'alias' => 'required|string|max:100',
            'descriptions.*.title' => 'required|string|max:200',
            'descriptions.*.keyword' => 'nullable|string|max:200',
            'descriptions.*.description' => 'nullable|string|max:500',
            'descriptions.*.content' => 'nullable|string',
        ];
        
        // Get custom field validation rules
        $arrValidation = $this->getCustomFieldValidation($arrValidation, FrontPage::class);

        $validator = $this->validateWithCustomFields(
            $data, 
            $arrValidation,
            [
                'alias.regex' => gp247_language_render('admin.page.alias_validate'),
                'descriptions.*.title.required' => gp247_language_render('validation.required', ['attribute' => gp247_language_render('admin.page.title')]),
            ]
        );

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($data);
        }
        $dataCreate = [
            'image'    => $data['image'],
            'alias'    => $data['alias'],
            'status'   => !empty($data['status']) ? 1 : 0,
        ];
        $page = FrontPage::create($dataCreate);
        $dataDes = [];
        $languages = $this->languages;
        foreach ($languages as $code => $value) {
            $dataDes[] = [
                'page_id'     => $page->id,
                'lang'        => $code,
                'title'       => $data['descriptions'][$code]['title'],
                'keyword'     => $data['descriptions'][$code]['keyword'],
                'description' => $data['descriptions'][$code]['description'],
                'content'     => $data['descriptions'][$code]['content'],
            ];
        }
        $dataDes = gp247_clean($dataDes, ['content'], true);
        FrontPageDescription::create($dataDes);

        $shopStore = $data['shop_store'] ?? [session('adminStoreId')];

        $page->stores()->detach();
        if ($shopStore) {
            $page->stores()->attach($shopStore);
        }

        //Insert custom fields
        $fields = $data['fields'] ?? [];
        $this->updateCustomFields($fields, $page->id, FrontPage::class);

        return redirect()->route('admin_page.index')->with('success', gp247_language_render('action.create_success'));
    }

    /**
     * Form edit
     */
    public function edit($id)
    {
        $page = (new FrontPage);

        $page = $page->getPageAdmin($id);
        if (!$page) {
            return redirect(gp247_route_admin('admin_page.index'))->with('error', gp247_language_render('display.data_not_found'));
        }
        $data = [
            'title'             => gp247_language_render('action.edit'),
            'subTitle'          => '',
            'title_description' =>  '',
            'languages'         => $this->languages,
            'page'              => $page,
            'url_action'        => gp247_route_admin('admin_page.post_edit', ['id' => $page['id']]),
        ];
        return view('gp247-front-admin::admin.page')
            ->with($data);
    }

    /*
     * update status
     */
    public function postEdit($id)
    {
        $page = FrontPage::getPageAdmin($id);
        if (!$page) {
            return redirect()->route('admin.data_not_found')->with(['url' => url()->full()]);
        }

        $data = request()->all();
        $langFirst = array_key_first(gp247_language_all()->toArray()); //get first code language active
        $data['alias'] = !empty($data['alias'])?$data['alias']:$data['descriptions'][$langFirst]['title'];
        $data['alias'] = gp247_word_format_url($data['alias']);
        $data['alias'] = gp247_word_limit($data['alias'], 100);
        $arrValidation = [
            'descriptions.*.title' => 'required|string|max:200',
            'descriptions.*.keyword' => 'nullable|string|max:200',
            'descriptions.*.description' => 'nullable|string|max:500',
            'descriptions.*.content' => 'nullable|string',
            'alias' => 'required|string|max:100',
        ];
        
        // Get custom field validation rules
        $arrValidation = $this->getCustomFieldValidation($arrValidation, FrontPage::class);

        $validator = $this->validateWithCustomFields(
            $data, 
            $arrValidation,
            [
                'alias.regex' => gp247_language_render('admin.page.alias_validate'),
                'descriptions.*.title.required' => gp247_language_render('validation.required', ['attribute' => gp247_language_render('admin.page.title')]),
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
        ];
        if (!empty($data['alias'])) {
            $dataUpdate['alias'] = $data['alias'];
        }
        $page->update($dataUpdate);
        $page->descriptions()->delete();
        $dataDes = [];
        foreach ($data['descriptions'] as $code => $row) {
            $dataDes[] = [
                'page_id'     => $id,
                'lang'        => $code,
                'title'       => $row['title'],
                'keyword'     => $row['keyword'],
                'description' => $row['description'],
                'content'     => $row['content'],
            ];
        }
        $dataDes = gp247_clean($dataDes, ['content'], true);
        FrontPageDescription::create($dataDes);

        $shopStore = $data['shop_store'] ?? [session('adminStoreId')];
        
        $page->stores()->detach();
        if ($shopStore) {
            $page->stores()->attach($shopStore);
        }
        //Insert custom fields
        $fields = $data['fields'] ?? [];
        $this->updateCustomFields($fields, $page->id, FrontPage::class);

        return redirect()->route('admin_page.index')->with('success', gp247_language_render('action.edit_success'));
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
            FrontPage::destroy($arrID);
            return response()->json(['error' => 0, 'msg' => gp247_language_render('action.delete_success')]);
        }
    }
}

