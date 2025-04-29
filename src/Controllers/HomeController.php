<?php
namespace GP247\Front\Controllers;

use GP247\Front\Controllers\RootFrontController;
use GP247\Front\Models\FrontPage;
use GP247\Front\Models\FrontBanner;

class HomeController extends RootFrontController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $contentHome = (new FrontPage)->getDetail('home', $type = 'alias', $checkActive = 0);
        $view = $this->GP247TemplatePath . '.screen.home';
        gp247_check_view($view);
        return view(
            $view,
            array(
                'title'       => gp247_store_info('title'),
                'keyword'     => gp247_store_info('keyword'),
                'description' => gp247_store_info('description'),
                'storeId'     => config('app.storeId'),
                'contentHome' => $contentHome,
                'layout_page' => 'front_home',
            )
        );
    }

        /**
     * Process front form page detail
     *
     * @param [type] ...$params
     * @return void
     */
    public function pageDetailProcessFront(...$params)
    {
        if (GP247_SEO_LANG) {
            $lang = $params[0] ?? '';
            $alias = $params[1] ?? '';
            gp247_lang_switch($lang);
        } else {
            $alias = $params[0] ?? '';
        }
        return $this->_pageDetail($alias);
    }

    
    /**
     * Render page
     * @param  [string] $alias
     */
    private function _pageDetail($alias)
    {
        $page = (new FrontPage)->getDetail($alias, $type = 'alias');
        if ($page) {
            $view = $this->GP247TemplatePath . '.screen.page_detail';
            gp247_check_view($view);
            return view(
                $view,
                array(
                    'title'       => $page->title,
                    'description' => $page->description,
                    'keyword'     => $page->keyword,
                    'page'        => $page,
                    'og_image'    => gp247_file($page->getImage()),
                    'layout_page' => 'front_page_detail',
                    'breadcrumbs' => [
                        ['url'    => '', 'title' => $page->title],
                    ],
                )
            );
        } else {
            return $this->pageNotFound();
        }
    }

    
    /**
     * Process front search page
     *
     * @param [type] ...$params
     * @return void
     */
    public function searchProcessFront(...$params)
    {
        if (GP247_SEO_LANG) {
            $lang = $params[0] ?? '';
            gp247_lang_switch($lang);
        }
        return $this->_search();
    }

    /**
     * search product
     * @return [view]
     */
    private function _search()
    {
        $keyword = request('keyword');
        $keyword = gp247_clean(data:$keyword, hight:true);

        $searchMode = config('gp247-config.front.GP247_SEARCH_MODE');

        if (strtoupper($searchMode) === 'PRODUCT' && class_exists('\GP247\Shop\Models\ShopProduct')) {
            if ($keyword) {
                $itemsList = (new \GP247\Shop\Models\ShopProduct)
                ->setLimit(gp247_config('product_list'))
                ->setKeyword($keyword)
                ->setPaginate()
                ->getData();
            } else {
                $itemsList = collect([]);
            }
            $view = $this->GP247TemplatePath . '.screen.shop_search';
            $layout_page = 'shop_search';
            $subPath = 'screen.shop_search';
            if (!view()->exists($view)) {
                if (!view()->exists('gp247-shop-front::'.$subPath)) {
                    gp247_report('View not found '.$view);
                    echo  gp247_language_render('front.view_not_exist', ['view' => $view]);
                    exit();
                }
                $view = 'gp247-shop-front::'.$subPath;
            }
        } else {
            if ((strtoupper($searchMode) === 'NEWS' && gp247_config_global('News') && class_exists('\App\GP247\Plugins\News\Models\NewsContent'))) {
                if ($keyword) {
                    $itemsList = (new \App\GP247\Plugins\News\Models\NewsContent)
                    ->setLimit(gp247_config('page_list'))
                    ->setKeyword($keyword)
                    ->setPaginate()
                    ->getData();
                } else {
                    $itemsList = collect([]);
                }
            } else {
                if ($keyword) {
                    $itemsList = (new FrontPage)
                    ->setLimit(gp247_config('page_list'))
                    ->setKeyword($keyword)
                    ->setPaginate()
                    ->getData();
                } else {
                    $itemsList = collect([]);
                }
            }
            $view = $this->GP247TemplatePath . '.screen.front_search';
            $layout_page = 'front_search';
        }

        gp247_check_view($view);

        return view(
            $view,
            array(
                'title'       => gp247_language_render('action.search') . ($keyword ? ': ' . $keyword : ''),
                'itemsList'   => $itemsList,
                'layout_page' => $layout_page,
                'breadcrumbs' => [
                    ['url'    => '', 'title' => gp247_language_render('action.search')],
                ],
            )
        );
    }



    /**
     * Process click banner
     *
     * @param   [int]  $id
     *
     */
    public function clickBanner($id = 0)
    {
        $banner = FrontBanner::find($id);
        if ($banner) {
            $banner->click +=1;
            $banner->save();
            return redirect(url($banner->url??'/'));
        }
        return redirect(url('/'));
    }
}
