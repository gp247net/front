<?php
namespace GP247\Front\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GP247\Front\Models\FrontSubscribe;
class RootFrontController extends Controller
{
    public $GP247TemplatePath;
    public function __construct()
    {
        $this->GP247TemplatePath = 'GP247TemplatePath::' . gp247_store_info('template');
    }
    /**
     * Default page not found
     *
     * @return  [type]  [return description]
     */
    public function pageNotFound()
    {
        if (!view()->exists($this->GP247TemplatePath . '.screen.404')) {
            return abort(404);
        }
        return view(
            $this->GP247TemplatePath . '.screen.404',
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
        gp247_check_view( $this->GP247TemplatePath . '.screen.notfound');
        return view(
            $this->GP247TemplatePath . '.screen.notfound',
            [
                'title' => gp247_language_render('front.notfound'),
                'msg' => gp247_language_render('front.notfound_detail'),
                'description' => '',
                'keyword' => '',
            ]
        );
    }

    /**
     * email subscribe
     * @param  Request $request
     * @return json
     */
    public function emailSubscribe(Request $request)
    {
        $validator = $request->validate([
            'subscribe_email' => 'required|email',
            ], [
            'subscribe_email.required' => gp247_language_render('validation.required'),
            'subscribe_email.email'    => gp247_language_render('validation.email'),
        ]);
        $data       = $request->all();
        $checkEmail = FrontSubscribe::where('email', $data['subscribe_email'])
            ->where('store_id', config('app.storeId'))
            ->first();
        if (!$checkEmail) {
            FrontSubscribe::create(['email' => $data['subscribe_email'], 'store_id' => config('app.storeId')]);
        }
        return redirect()->back()
            ->with(['success' => gp247_language_render('subscribe.subscribe_success')]);
    }

}
