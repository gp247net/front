<?php
namespace GP247\Front\Controllers;

use App\Http\Controllers\Controller;

class RootFrontController extends Controller
{
    public $gp247_templatePathFront;
    public $GP247TemplatePath;
    public function __construct()
    {
        $this->GP247TemplatePath = 'GP247TemplatePath::' . gp247_store_info('template');
        $this->gp247_templatePathFront = config('gp247-config.front.path_view').'::';
    }
    /**
     * Default page not found
     *
     * @return  [type]  [return description]
     */
    public function pageNotFound()
    {
        if (!view()->exists($this->GP247TemplatePath . '.404')) {
            return abort(404);
        }
        return view(
            $this->GP247TemplatePath . '.404',
                [
                'title' => gp247_language_render('front.404'),
                'msg' => gp247_language_render('front.404_detail'),
                'description' => '',
                'keyword' => ''
                ]
        );
    }

    /**
     * Default item not found
     *
     * @return  [view]
     */
    public function itemNotFound()
    {
        gp247_check_view( $this->GP247TemplatePath . '.notfound');
        return view(
            $this->GP247TemplatePath . '.notfound',
            [
                'title' => gp247_language_render('front.notfound'),
                'msg' => gp247_language_render('front.notfound_detail'),
                'description' => '',
                'keyword' => '',
            ]
        );
    }

}
