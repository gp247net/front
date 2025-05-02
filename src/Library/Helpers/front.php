<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

if (!function_exists('gp247_route_front') && !in_array('gp247_route_front', config('gp247_functions_except', []))) {
    /**
     * Render route
     *
     * @param   [string]  $name
     * @param   [array]  $param
     *
     * @return  [type]         [return description]
     */
    function gp247_route_front($name, $param = [])
    {
        $name = trim($name);
        if (!GP247_SEO_LANG) {
            $param = Arr::except($param, ['lang']);
        } else {
            $arrRouteExcludeLanguage = explode(',', config('gp247-config.front.route.GP247_ROUTE_EXCLUDE_LANGUAGE', ''));
            // add default route exclude language
            $arrRouteExcludeLanguage = array_merge($arrRouteExcludeLanguage, ['front.home','front.locale', 'front.banner.click']);
            if (!key_exists('lang', $param) && !in_array($name, $arrRouteExcludeLanguage)) {
                $param['lang'] = app()->getLocale();
            }
        }
        
        if (Route::has($name)) {
            try {
                $route = route($name, $param);
            } catch (\Throwable $th) {
                $route = url('#'.$name.'#'.implode(',', $param));
            }
            return $route;
        } else {
            if ($name == 'front.home') {
                return url('/');
            } else {
                return url('#'.$name);
            }
        }
    }
}

/**
 * Get all template installed
 *
 * @return  [type]  [return description]
 */
if (!function_exists('gp247_front_get_all_template_installed') && !in_array('gp247_front_get_all_template_installed', config('gp247_functions_except', []))) {
    function gp247_front_get_all_template_installed()
    {
        $allTemplate = \GP247\Core\Models\AdminConfig::where('group', 'Templates')->where('value', 1)->get();
        $arrTemplate = [
            GP247_TEMPLATE_FRONT_DEFAULT => GP247_TEMPLATE_FRONT_DEFAULT,
        ];
        if ($allTemplate) {
            foreach ($allTemplate as $template) {
                $arrTemplate[$template->key] = $template->key;
            }
        }
        return $arrTemplate;
    }
}

if (!function_exists('gp247_link') && !in_array('gp247_link', config('gp247_functions_except', []))) {
    /**
     * Get all link
     *
     * @return  [type]  [return description]
     */
    function gp247_link()
    {
        return \GP247\Front\Models\FrontLink::getGroup();
    }
}


if (!function_exists('gp247_link_collection') && !in_array('gp247_link_collection', config('gp247_functions_except', []))) {
    /**
     * Get all link collection
     *
     * @return  [type]  [return description]
     */
    function gp247_link_collection()
    {
        return \GP247\Front\Models\FrontLink::getLinksCollection();
    }
}

/*
Get all layouts
 */
if (!function_exists('gp247_front_layout_block') && !in_array('gp247_front_layout_block', config('gp247_functions_except', []))) {
    function gp247_front_layout_block()
    {
        return \GP247\Front\Models\FrontLayoutBlock::getLayout();
    }
}

/**
 * Render block function
 * @param string $positionBlock Position of block
 * @param string|null $layout_page Current layout page
 * @return string HTML content
 */
if (!function_exists('gp247_render_block') && !in_array('gp247_render_block', config('gp247_functions_except', []))) {
    function gp247_render_block($positionBlock = '', $layout_page = null)
    {
        // Get layout block data
        $GP247LayoutBlock = gp247_front_layout_block();
        $GP247TemplatePath = 'GP247TemplatePath::'.gp247_store_info('template');
        $output = '';

        if (isset($GP247LayoutBlock[$positionBlock])) {
            foreach ($GP247LayoutBlock[$positionBlock] as $layout) {
                $arrPage = explode(',', $layout->page);
                
                if ($layout->page == '*' || ($layout_page !== null && in_array($layout_page, $arrPage))) {
                    if ($layout->type == 'html') {
                        $output .= $layout->text;
                    } elseif ($layout->type == 'view') {
                        //check view exist
                        $viewPath = $GP247TemplatePath.'.blocks.'.$layout->text;
                        if (view()->exists($viewPath)) {
                            $view = view($viewPath)->render();
                            $output .= $view;
                        }
                    } elseif ($layout->type == 'page') {
                        //Check class exist
                        $modelPage = null;
                        if (class_exists('\GP247\Front\Models\FrontPage')) {
                            $modelPage = new \GP247\Front\Models\FrontPage;
                        }
                        if ($modelPage) {
                            $content = $modelPage->start()->getDetail($layout->text, $type = 'alias', $checkActive = 0)->content ?? '';
                            $htmlContent = gp247_html_render($content);
                            $output .= $htmlContent;
                        }
                    }
                }
            }
        }

        return $output;
    }
}