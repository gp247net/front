<?php
namespace GP247\Front\Controllers\Admin;

use GP247\Core\Admin\Models\AdminLanguage;
use GP247\Front\Models\FrontPage;
use GP247\Front\Controllers\Admin\RootFrontAdminController;
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
        $listTh['action'] = gp247_language_render('action.title');

        $sort_order = gp247_clean(request('sort_order') ?? 'id_desc');
        $keyword    = gp247_clean(request('keyword') ?? '');
        $arrSort = [
            'id__desc'       => gp247_language_render('filter_sort.id_desc'),
            'id__asc'        => gp247_language_render('filter_sort.id_asc'),
            'name__desc'     => gp247_language_render('filter_sort.name_desc'),
            'name__asc'      => gp247_language_render('filter_sort.name_asc'),
        ];
        $dataSearch = [
            'keyword'    => $keyword,
            'sort_order' => $sort_order,
            'arrSort'    => $arrSort,
        ];
        $dataTmp = FrontPage::getPageListAdmin($dataSearch);

        $dataTr = [];
        foreach ($dataTmp as $key => $row) {
            $arrAction = [
            '<a href="' . gp247_route_admin('admin_page.edit', ['id' => $row['id'], 'page' => request('page')]) . '"  class="dropdown-item"><i class="fa fa-edit"></i> '.gp247_language_render('action.edit').'</a>',
            ];
            $arrAction[] = '<a href="#" onclick="deleteItem(\'' . $row['id'] . '\');"  title="' . gp247_language_render('action.delete') . '" class="dropdown-item"><i class="fas fa-trash-alt"></i> '.gp247_language_render('action.remove').'</a>';
            $action = $this->procesListAction($arrAction);


            $dataTr[] = [
                'title' => $row['title'],
                'image' => gp247_image_render($row['image'], '50px', '', $row['title']),
                'alias' => '<a href="'.gp247_route_front('front.page.detail', ['alias' => $row['alias']]).'" target="_blank">'.$row['alias'].' <i class="fas fa-link"></i></a>',
                'status' => $row['status'] ? '<span class="badge badge-success">ON</span>' : '<span class="badge badge-danger">OFF</span>',
                'action' => $action,
            ];
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
        foreach ($arrSort as $key => $status) {
            $optionSort .= '<option  ' . (($sort_order == $key) ? "selected" : "") . ' value="' . $key . '">' . $status . '</option>';
        }
        //=menuSort

        //menuSearch
        $data['topMenuRight'][] = '
                <form action="' . gp247_route_admin('admin_page.index') . '" id="button_search">
                <div class="input-group input-group" style="width: 350px;">
                    <select class="form-control form-control-sm rounded-0 select2" name="sort_order" id="sort_order">
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
        return view('gp247-front::admin.page')
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
            'alias' => 'required|regex:/(^([0-9A-Za-z\-_]+)$)/|string|max:100',
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
            return redirect(gp247_route_admin('admin_page.index'))->with('error', gp247_language_render('admin.data_not_found'));
        }
        $data = [
            'title'             => gp247_language_render('action.edit'),
            'subTitle'          => '',
            'title_description' =>  '',
            'languages'         => $this->languages,
            'page'              => $page,
            'url_action'        => gp247_route_admin('admin_page.post_edit', ['id' => $page['id']]),
        ];
        return view('gp247-front::admin.page')
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
            'alias' => 'required|regex:/(^([0-9A-Za-z\-_]+)$)/|string|max:100',
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

