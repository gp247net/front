<?php
namespace GP247\Front\Admin\Controllers;

use GP247\Front\Admin\Controllers\RootFrontAdminController;
use GP247\Front\Models\FrontLinkGroup;
use Illuminate\Support\Facades\Validator;

class AdminLinkGroupController extends RootFrontAdminController
{
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        $data = $this->processDataScreen();
        $data['title_action'] = '<i class="fa fa-plus" aria-hidden="true"></i> ' . gp247_language_render('admin.link_group.add_new_title');
        $data['url_action'] = gp247_route_admin('admin_link_group.create');
        $data['layout'] = 'index';
        return view('gp247-front-admin::admin.link_group')
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
            'name' => 'required',
            'code' => 'required|unique:"'.FrontLinkGroup::class.'",code',
        ], [
            'name.required' => gp247_language_render('validation.required'),
        ]);

        if ($validator->fails()) {

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $data['code'] = gp247_word_format_url($data['code']);
        $data['code'] = gp247_word_limit($data['code'], 100);
        $dataCreate = [
            'code' => $data['code'],
            'name' => $data['name'],
        ];
        $dataCreate = gp247_clean($dataCreate, [], true);
        FrontLinkGroup::create($dataCreate);

        return redirect()->route('admin_link_group.index')->with('success', gp247_language_render('action.create_success'));
    }

    /**
     * Form edit
     */
    public function edit($id)
    {
        $banner_type = FrontLinkGroup::find($id);
        if (!$banner_type) {
            return redirect(gp247_route_admin('admin_link_group.index'))->with('error', gp247_language_render('display.data_not_found'));
        }
        $data = $this->processDataScreen($id);
        $data['title_action'] = '<i class="fa fa-edit" aria-hidden="true"></i> ' . gp247_language_render('action.edit');
        $data['url_action'] = gp247_route_admin('admin_link_group.edit', ['id' => $banner_type['id']]);
        $data['banner_type'] = $banner_type;
        $data['layout'] = 'edit';
        return view('gp247-front-admin::admin.link_group')
        ->with($data);
    }

    /**
     * update status
     */
    public function postEdit($id)
    {
        $data = request()->all();
        $dataOrigin = request()->all();
        $obj = FrontLinkGroup::find($id);
        $validator = Validator::make($dataOrigin, [
            'code' => 'required|unique:"'.FrontLinkGroup::class.'",code,' . $obj->id . ',id',
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
        $data['code'] = gp247_word_format_url($data['code']);
        $data['code'] = gp247_word_limit($data['code'], 100);
        $dataUpdate = [
            'code' => $data['code'],
            'name' => $data['name'],
        ];
        $dataUpdate = gp247_clean($dataUpdate, [], true);
        $obj->update($dataUpdate);

        return redirect()->back()->with('success', gp247_language_render('action.edit_success'));
    }

    private function processDataScreen(string $id = null) {
        $data = [
            'subTitle' => '',
            'icon' => 'fa fa-indent',
            'urlDeleteItem' => gp247_route_admin('admin_link_group.delete'),
            'removeList' => 0, // 1 - Enable function delete list item
            'buttonRefresh' => 0, // 1 - Enable button refresh
            'buttonSort' => 0, // 1 - Enable button sort
            'css' => '',
            'js' => '',
            'id' => $id,
        ];
    
        $listTh = [
            'code' => gp247_language_render('admin.link_group.code'),
            'name' => gp247_language_render('admin.link_group.name'),
            'action' => gp247_language_render('action.title'),
        ];
            $obj = new FrontLinkGroup;
            $obj = $obj->orderBy('id', 'desc');
            $dataTmp = $obj->paginate(20);
    
            $dataTr = [];
            foreach ($dataTmp as $key => $row) {
                $dataTr[$row['id']] = [
                'code' => $row['code'] ?? 'N/A',
                'name' => $row['name'] ?? 'N/A',
                'action' => '
                    <a href="' . gp247_route_admin('admin_link_group.edit', ['id' => $row['id'] ? $row['id'] : 'not-found-id']) . '"><span title="' . gp247_language_render('action.edit') . '" type="button" class="btn btn-flat btn-sm btn-primary"><i class="fa fa-edit"></i></span></a>&nbsp;
    
                  <span onclick="deleteItem(\'' . $row['id'] . '\');"  title="' . gp247_language_render('action.delete') . '" class="btn btn-flat btn-sm btn-danger"><i class="fas fa-trash-alt"></i></span>
                  ',
            ];
            }
    
            $data['listTh'] = $listTh;
            $data['dataTr'] = $dataTr;
            $data['pagination'] = $dataTmp->appends(request()->except(['_token', '_pjax']))->links('gp247-core::component.pagination');
            $data['resultItems'] = gp247_language_render('admin.result_item', ['item_from' => $dataTmp->firstItem(), 'item_to' => $dataTmp->lastItem(), 'total' =>  $dataTmp->total()]);
            return $data;
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
            FrontLinkGroup::destroy($arrID);
            return response()->json(['error' => 0, 'msg' => gp247_language_render('action.delete_success')]);
        }
    }
}
