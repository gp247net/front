<?php
namespace GP247\Front\Api;

use GP247\Front\Controllers\RootFrontController;
use GP247\Front\Models\FrontBanner;
use GP247\Front\Models\FrontPage;
use GP247\Core\Controllers\CustomFieldTrait;

class AdminController extends RootFrontController
{
    use CustomFieldTrait;


    public function getPageList()
    {
        $itemsList = (new FrontPage)
            ->jsonPaginate();
        return response()->json($itemsList, 200);
    }

    public function getPageDetail($id)
    {
        $page = (new FrontPage)
            ->where('id', $id)
            ->with('descriptions')
            ->first();
        return response()->json($page, 200);
    }

    /**
     * display list banner
     * @return [json]
     */
    public function getBannerList()
    {
        $itemsList = (new FrontBanner)
            ->jsonPaginate();
        return response()->json($itemsList, 200);
    }

    /**
     * Banner detail
     * @param  [int] $id
     * @return [json]
     */
    public function getBannerDetail($id)
    {
        $banner = (new FrontBanner)
            ->where('id', $id)
            ->first();
        if ($banner) {
            return response()->json($banner, 200);
        } else {
            return response()->json([], 404);
        }
    }
    
}
