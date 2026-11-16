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
        // WHY: always respond with HTTP 404 so search engines do not index
        // "not found" pages as valid content (US-SEO-006).
        return response()->view(
            $this->GP247TemplatePath . '.screen.404',
            [
                'title'       => gp247_language_render('front.404'),
                'msg'         => gp247_language_render('front.404_detail'),
                'description' => '',
                'keyword'     => '',
                'canonical'   => '',
            ],
            404
        );
    }

    /**
     * The "not found" page every shop screen and plugin shows when the record a URL
     * points at does not exist — always with HTTP 404.
     *
     * WHY 404 and not a plain view (200): a 200 "not found" page is a soft 404 that
     * search engines index as real, empty content and keep in results after the
     * record is deleted (modification 20261117T055341). Plugins must call this
     * instead of returning the notfound view themselves (RISK-TECH-plugin-soft-404).
     *
     * @return \Illuminate\Http\Response
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-006
     * @aidlc-adr ADR-seo-indexing-signals
     */
    public function itemNotFound()
    {
        gp247_check_view( $this->GP247TemplatePath . '.screen.notfound');
        return response()->view(
            $this->GP247TemplatePath . '.screen.notfound',
            [
                'title' => gp247_language_render('front.notfound'),
                'msg' => gp247_language_render('front.notfound_detail'),
                'description' => '',
                'keyword' => '',
            ],
            404
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
