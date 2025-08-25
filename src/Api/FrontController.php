<?php
namespace GP247\Front\Api;

use GP247\Front\Controllers\RootFrontController;
use GP247\Front\Models\FrontBanner;
use GP247\Front\Models\FrontPage;

class FrontController extends RootFrontController
{
    /**
     * display list banner
     * @return [json]
     */
    public function getBannerList()
    {
        $itemsList = (new FrontBanner)
            ->where('status', 1)
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
            ->where('status', 1)
            ->first();
        if ($banner) {
            return response()->json($banner, 200);
        } else {
            return response()->json([], 404);
        }
    }

    public function getPageList()
    {
        $itemsList = (new FrontPage)
            ->where('status', 1)
            ->jsonPaginate();
        return response()->json($itemsList, 200);
    }

    public function getPageDetail($id)
    {
        $page = (new FrontPage)
            ->where('id', $id)
            ->with('descriptions')
            ->where('status', 1)
            ->first();
        return response()->json($page, 200);
    }
}
