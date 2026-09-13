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
 * Render every plugin registered against a storefront extension point.
 *
 * A storefront screen is Blade owned by the active template, so a plugin cannot
 * inject markup into it on its own. A screen calls this helper at the places it
 * is willing to host plugin output; plugins append a renderer to
 * config('gp247-config.front.plugin_hooks') from their Provider.php.
 *
 * @param string $hook Extension point name, e.g. "shop_product_detail_bottom".
 * @param array<string, mixed> $data Context the screen hands to the renderers (e.g. ['product' => $product]).
 * @return string Concatenated HTML; empty when nothing is registered.
 *
 * @aidlc-unit frontend-template-dev
 * @aidlc-story US-TPL-storefront-plugin-hooks
 * @aidlc-adr front_storefront-plugin-hooks
 */
if (!function_exists('gp247_render_plugin_hook') && !in_array('gp247_render_plugin_hook', config('gp247_functions_except', []))) {
    function gp247_render_plugin_hook(string $hook, array $data = []): string
    {
        $renderers = config('gp247-config.front.plugin_hooks.' . $hook, []);

        if (!is_array($renderers) || $renderers === []) {
            return '';
        }

        $output = '';

        foreach ($renderers as $renderer) {
            $callback = is_array($renderer) ? ($renderer['callback'] ?? null) : $renderer;

            if (!is_callable($callback)) {
                continue;
            }

            // WHY each renderer is isolated: these run inside a product page a
            // shopper is looking at. One plugin throwing must cost its own block,
            // never the whole page — the same failure budget gp247_render_block
            // gives a template block.
            try {
                $output .= (string) call_user_func($callback, $data);
            } catch (\Throwable $e) {
                gp247_report('[gp247 plugin hook] "' . $hook . '" renderer '
                    . (is_array($renderer) ? ($renderer['key'] ?? '?') : '?')
                    . ' failed: ' . $e->getMessage());
            }
        }

        return $output;
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
                // Explode by comma and trim each value
                $arrPage = array_map('trim', explode(',', $layout->page));
                
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

//Function process view of plugin
// Prioritize checking the view exists in folder "Plugins" the current template
// If it does not exist, check view in the plugin
if (!function_exists('gp247_plugin_process_view') && !in_array('gp247_plugin_process_view', config('gp247_functions_except', []))) {
    function gp247_plugin_process_view(string $appPAth, string $prefix, string $subPath)
    {
        if (strpos($prefix, '.') === false) {
            $prefix = $prefix . '.';
        }
        // Convert plugin format from 'Plugins/Abc' to 'Plugins.Abc'
        if (strpos($appPAth, '/') !== false) {
            $stringPath = str_replace('/', '.', $appPAth);
        }
        $view = $prefix . $stringPath.'.'.$subPath;
        if (!view()->exists($view)) {
            $viewPlugin = $appPAth.'::'.$subPath;
            if (view()->exists($viewPlugin)) {
                $view = $viewPlugin;
            }   
        }
        return $view;
    }
}


if (! function_exists('gp247_front_is_rtl')) {
    /**
     * Determine whether the currently active locale's admin-configured
     * language is flagged RTL (`admin_language.rtl`).
     *
     * WHY a local helper instead of reusing a `gp247/core` helper: no
     * existing helper exposes the `rtl` column (gp247_language_all() /
     * LanguageSwitcher only expose code/name/icon/url), and this template
     * may not add PHP outside its own directory (US-TPL-009 AC), so RTL
     * resolution is self-contained here using the already-public
     * AdminLanguage::getListActive() model method.
     *
     * @return bool True when the active language's `rtl` flag is set.
     *
     * @aidlc-unit frontend-template-dev
     * @aidlc-story US-TPL-009
     * @aidlc-adr ADR-014
     */
    function gp247_front_is_rtl(): bool
    {
        $locale = app()->getLocale();
        $language = \GP247\Core\Models\AdminLanguage::getListActive()->get($locale);

        return (bool) ($language->rtl ?? false);
    }
}

if (!function_exists('gp247_template_source_roots') && !in_array('gp247_template_source_roots', config('gp247_functions_except', []))) {
    /**
     * Root directories a template's files are resolved from, in priority order.
     *
     * These are the hint paths Laravel registered for the GP247TemplatePath view
     * namespace: app/GP247/Templates first (published overrides and site-owned
     * templates), then each package's own copy of the template it ships.
     *
     * WHY read them from the finder instead of listing paths here: the view
     * finder is the single source of truth for where a template view comes from.
     * Anything that LISTS template files (the layout-block picker, the
     * template-publish/prune commands) must agree with what RENDER will pick, and
     * must keep agreeing when a package or a template adds a new source.
     *
     * @param bool $includeApp Keep the app/ override root (false = package defaults only).
     * @return array<int, string> Absolute directory paths, highest priority first.
     *
     * @aidlc-unit frontend-template-dev
     * @aidlc-story US-TPL-template-vendor-resident
     * @aidlc-adr frontend-template-dev_template-vendor-resident-views
     */
    function gp247_template_source_roots(bool $includeApp = true): array
    {
        // WHY delegate: gp247/core owns the one implementation so that
        // gp247:doctor (bootstrap tier, no helpers available) and this helper can
        // never disagree about where a template's files come from.
        return \GP247\Core\Support\TemplateSourceAudit::roots($includeApp);
    }
}

if (!function_exists('gp247_template_files') && !in_array('gp247_template_files', config('gp247_functions_except', []))) {
    /**
     * List a template's files across every source root, merged by relative path.
     *
     * The first root that provides a given relative path wins — the same rule the
     * view finder applies — so the result is exactly the set of files that would
     * render, with the winning copy's absolute path.
     *
     * @param string $template Template name (directory segment), e.g. "GP247Front".
     * @param string $subPath  Sub-directory inside the template, e.g. "blocks" ("" = template root).
     * @param string $pattern  glob pattern applied inside $subPath, e.g. "*.blade.php".
     * @return array<string, string> Relative path (inside $subPath) => absolute path of the winning file.
     *
     * @aidlc-unit frontend-template-dev
     * @aidlc-story US-TPL-template-vendor-resident
     * @aidlc-adr frontend-template-dev_template-vendor-resident-views
     */
    function gp247_template_files(string $template, string $subPath = '', string $pattern = '*.blade.php'): array
    {
        if ($template === '') {
            return [];
        }

        $files = [];
        foreach (gp247_template_source_roots() as $root) {
            $dir = $root.'/'.$template.($subPath === '' ? '' : '/'.$subPath);
            foreach (glob($dir.'/'.$pattern) ?: [] as $file) {
                $name = basename($file);
                // WHY array_key_exists and not overwrite: earlier roots have higher
                // priority, so the first copy found must survive.
                if (!array_key_exists($name, $files)) {
                    $files[$name] = $file;
                }
            }
        }

        ksort($files);

        return $files;
    }
}
