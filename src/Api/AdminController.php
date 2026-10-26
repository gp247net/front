<?php
namespace GP247\Front\Api;


use GP247\Core\Api\Support\ApiErrorResponder;
use GP247\Front\Controllers\RootFrontController;
use GP247\Front\Models\FrontBanner;
use GP247\Front\Models\FrontPage;
use GP247\Core\Controllers\CustomFieldTrait;
use GP247\Core\Api\Support\ApiStoreScope;

class AdminController extends RootFrontController
{
    use CustomFieldTrait;


    public function getPageList()
    {
        $itemsList = ApiStoreScope::apply((new FrontPage)->newQuery())
            ->jsonPaginate();
        return response()->json($itemsList, 200);
    }

    public function getPageDetail($id)
    {
        $page = ApiStoreScope::apply((new FrontPage)->newQuery())
            ->where('id', $id)
            ->with('descriptions')
            ->first();
        if (!$page) {
            return ApiErrorResponder::notFound();
        }
        return response()->json($page, 200);
    }

    /**
     * display list banner
     * @return [json]
     */
    public function getBannerList()
    {
        $itemsList = ApiStoreScope::apply((new FrontBanner)->newQuery())
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
        $banner = ApiStoreScope::apply((new FrontBanner)->newQuery())
            ->where('id', $id)
            ->first();
        if ($banner) {
            return response()->json($banner, 200);
        } else {
            return ApiErrorResponder::notFound();
        }
    }
    
}
