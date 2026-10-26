<?php
namespace GP247\Front\Api;

use GP247\Core\Api\Support\ApiErrorResponder;
use GP247\Core\Api\Support\ApiFieldAllowlist;
use GP247\Core\Api\Support\ApiVisibility;
use GP247\Front\Controllers\RootFrontController;
use GP247\Front\Models\FrontBanner;
use GP247\Front\Models\FrontPage;

/**
 * Public banner/page REST API (v1). Rows follow storefront visibility of the calling
 * domain's store (FrontBanner/FrontPage::buildQuery()); payloads keep the allowlisted
 * fields only; unknown records answer 404.
 *
 * @aidlc-unit compat-foundation
 * @aidlc-story US-CMP-api-public-field-allowlist, US-CMP-api-error-contract
 * @aidlc-adr compat-foundation_rest-api-hardening
 */
class FrontController extends RootFrontController
{
    /**
     * display list banner
     * @return [json]
     */
    public function getBannerList()
    {
        $itemsList = ApiVisibility::visible((new FrontBanner)->setType('all')->buildQuery())
            ->jsonPaginate();
        return response()->json(ApiFieldAllowlist::page($itemsList, FrontApiFields::BANNER), 200);
    }

    /**
     * Banner detail
     * @param  [int] $id
     * @return [json]
     */
    public function getBannerDetail($id)
    {
        $banner = ApiVisibility::visible((new FrontBanner)->setType('all')->buildQuery())
            ->where((new FrontBanner)->getTable() . '.id', $id)
            ->first();
        if ($banner) {
            return response()->json(ApiFieldAllowlist::one($banner, FrontApiFields::BANNER), 200);
        } else {
            return ApiErrorResponder::notFound();
        }
    }

    public function getPageList()
    {
        $itemsList = ApiVisibility::visible((new FrontPage)->buildQuery())
            ->jsonPaginate();
        return response()->json(ApiFieldAllowlist::page($itemsList, FrontApiFields::PAGE), 200);
    }

    public function getPageDetail($id)
    {
        $page = ApiVisibility::visible((new FrontPage)->buildQuery())
            ->where((new FrontPage)->getTable() . '.id', $id)
            ->with('descriptions')
            ->first();
        if (!$page) {
            return ApiErrorResponder::notFound();
        }
        return response()->json(ApiFieldAllowlist::one($page, FrontApiFields::PAGE), 200);
    }
}
