<?php

namespace GP247\Front\Api\V2;

use GP247\Core\Api\Support\ApiStoreScope;
use GP247\Core\Api\Support\ApiVisibility;
use GP247\Core\Api\V2\ApiQuery;
use GP247\Front\Controllers\RootFrontController;
use GP247\Front\Models\FrontBanner;
use GP247\Front\Models\FrontPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Banners, CMS pages and the API description of REST API v2. Public endpoints follow
 * the storefront visibility of the calling domain (shared rule with v1); admin
 * endpoints sit behind `api.admin:<screen>` and are narrowed by ApiStoreScope.
 *
 * @aidlc-unit compat-foundation
 * @aidlc-story US-CMP-api-v2-contract, US-CMP-api-v2-query-parameters
 * @aidlc-adr compat-foundation_rest-api-hardening
 */
class ContentController extends RootFrontController
{
    /**
     * API version, installed GP247 packages (names only — exact versions would help an
     * attacker pick an exploit), languages and store: lets a client detect what it talks to.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function meta()
    {
        $packages = array_values(array_filter(['gp247/core', 'gp247/front', 'gp247/shop'], fn ($name) => \Composer\InstalledVersions::isInstalled($name)));

        return response()->json(['data' => [
            'api_version' => '2',
            'packages'    => $packages,
            'languages'   => collect(gp247_language_all())->keys()->values(),
            'locale'      => gp247_get_locale(),
            'store_id'    => (string) config('app.storeId'),
        ]]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bannerList(Request $request)
    {
        return $this->banners($request, ApiVisibility::visible((new FrontBanner)->setType('all')->buildQuery()), false);
    }

    /**
     * @param Request $request
     * @param string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function bannerDetail(Request $request, $id)
    {
        $banner = ApiVisibility::visible((new FrontBanner)->setType('all')->buildQuery())
            ->where((new FrontBanner)->getTable() . '.id', $id)->first();

        return ApiQuery::one($banner, fn ($b) => self::banner($b, false));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pageList(Request $request)
    {
        return $this->pages($request, ApiVisibility::visible((new FrontPage)->buildQuery()));
    }

    /**
     * @param Request $request
     * @param string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function pageDetail(Request $request, $id)
    {
        $lang = ApiQuery::lang($request);
        $page = ApiVisibility::visible((new FrontPage)->buildQuery())->with('descriptions')
            ->where((new FrontPage)->getTable() . '.id', $id)->first();

        return ApiQuery::one($page, fn ($p) => self::page($p, $lang, true));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminBannerList(Request $request)
    {
        return $this->banners($request, ApiStoreScope::apply(FrontBanner::query()), true);
    }

    /**
     * @param Request $request
     * @param string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminBannerDetail(Request $request, $id)
    {
        return ApiQuery::one(ApiStoreScope::apply(FrontBanner::query())->where('id', $id)->first(), fn ($b) => self::banner($b, true));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminPageList(Request $request)
    {
        return $this->pages($request, ApiStoreScope::apply(FrontPage::query()));
    }

    /**
     * @param Request $request
     * @param string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminPageDetail(Request $request, $id)
    {
        $lang = ApiQuery::lang($request);

        return ApiQuery::one(ApiStoreScope::apply(FrontPage::query())->with('descriptions')->where('id', $id)->first(),
            fn ($p) => self::page($p, $lang, true));
    }

    /**
     * @param Request $request
     * @param Builder $query
     * @param bool    $admin
     * @return \Illuminate\Http\JsonResponse
     */
    private function banners(Request $request, Builder $query, bool $admin)
    {
        $t = (new FrontBanner)->getTable();

        return ApiQuery::page($query, $request, fn ($b) => self::banner($b, $admin),
            ['type' => fn (Builder $q, string $v) => $q->where($t . '.type', $v)],
            ['sort' => $t . '.sort', 'created_at' => $t . '.created_at'],
            fn (Builder $q, string $term) => $q->where($t . '.name', 'like', ApiQuery::like($term)));
    }

    /**
     * @param Request $request
     * @param Builder $query
     * @return \Illuminate\Http\JsonResponse
     */
    private function pages(Request $request, Builder $query)
    {
        $lang = ApiQuery::lang($request);
        $t = (new FrontPage)->getTable();

        return ApiQuery::page($query->with('descriptions'), $request, fn ($p) => self::page($p, $lang, false), [],
            ['created_at' => $t . '.created_at'],
            fn (Builder $q, string $term) => $q->whereHas('descriptions', fn ($d) => $d->where('name', 'like', ApiQuery::like($term))));
    }

    /**
     * Declared shape of a banner (the click counter only for admins).
     *
     * @param FrontBanner $b
     * @param bool        $admin
     * @return array<string, mixed>
     */
    private static function banner($b, bool $admin): array
    {
        $data = [
            'id' => $b->id, 'name' => $b->name, 'image' => $b->image, 'url' => $b->url, 'target' => $b->target,
            'html' => $b->html, 'type' => $b->type, 'sort' => (int) $b->sort, 'status' => (int) $b->status,
            'store_id' => $b->store_id,
            'created_at' => optional($b->created_at)->toIso8601String(), 'updated_at' => optional($b->updated_at)->toIso8601String(),
        ];
        if ($admin) {
            $data['click'] = (int) $b->click;
        }
        return $data;
    }

    /**
     * Declared shape of a CMS page; the body (content) only on detail.
     *
     * @param FrontPage $p
     * @param string    $lang
     * @param bool      $withContent
     * @return array<string, mixed>
     */
    private static function page($p, string $lang, bool $withContent): array
    {
        $fields = $withContent ? ['name', 'keyword', 'description', 'content'] : ['name', 'keyword', 'description'];

        return array_merge(['id' => $p->id, 'alias' => $p->alias, 'image' => $p->image],
            ApiQuery::described($p->descriptions, $lang, $fields),
            ['status' => (int) $p->status, 'store_id' => $p->store_id,
             'created_at' => optional($p->created_at)->toIso8601String(), 'updated_at' => optional($p->updated_at)->toIso8601String()]);
    }
}
