<?php

namespace GP247\Front\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use GP247\Core\Models\AdminMenu;
use GP247\Front\Models\FrontBannerType;
use GP247\Front\Models\FrontLinkGroup;
use GP247\Front\Models\FrontPage;
use GP247\Front\Models\FrontPageDescription;
use GP247\Front\Models\FrontLink;

class DataFrontDefaultSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Preparing update data version
        $this->updateDataVersion();

        // Delete old data
        $checkIdBlock = AdminMenu::where('key', 'ADMIN_CONTENT')->first();
        if ($checkIdBlock) {
            AdminMenu::where('key', 'ADMIN_CONTENT')->delete();
            AdminMenu::where('key', 'TEMPLATE')->delete();
            AdminMenu::where('parent_id', $checkIdBlock->id)->delete();
        }

        // Delete old SEO menu group (modification 20260711T114155), mirrors the
        // ADMIN_CONTENT reseed pattern above so re-running the seeder stays idempotent.
        $checkIdBlockSeo = AdminMenu::where('key', 'ADMIN_SEO')->first();
        if ($checkIdBlockSeo) {
            AdminMenu::where('key', 'ADMIN_SEO')->delete();
            AdminMenu::where('parent_id', $checkIdBlockSeo->id)->delete();
        }


        // Insert new data
        $idBlockAdmin = AdminMenu::insertGetId(
            [
                'parent_id' => 0,
                'sort'      => 100,
                'title'     => 'admin.menu_titles.ADMIN_CONTENT',
                'icon'      => 'nav-icon fas fa-book-open',
                'key'       => 'ADMIN_CONTENT',
            ]
        );

        AdminMenu::insertOrIgnore(
            [
                ['parent_id' => $idBlockAdmin,'sort' => 1,'title' => 'admin.menu_titles.banner','icon' => 'fas fa-image','uri' => 'admin::banner','key' => null,'type' => 0],
                ['parent_id' => $idBlockAdmin,'sort' => 2,'title' => 'admin.menu_titles.page_manager','icon' => 'fas fa-clone','uri' => 'admin::page','key' => null,'type' => 0],
                ['parent_id' => $idBlockAdmin,'sort' => 3,'title' => 'admin.menu_titles.layout','icon' => 'far fa-object-group','uri' => '','key' => null,'type' => 0],
                ['parent_id' => $idBlockAdmin,'sort' => 4,'title' => 'admin.menu_titles.layout_block','icon' => 'far fa-newspaper','uri' => 'admin::layout_block','key' => null,'type' => 0],
                ['parent_id' => $idBlockAdmin,'sort' => 5,'title' => 'admin.menu_titles.link_block','icon' => 'fab fa-chrome','uri' => 'admin::link','key' => null,'type' => 0],
                // Template manager
                ['parent_id' => 3,'sort' => 1,'title' => 'admin.menu_titles.template','icon' => 'fab fa-windows','uri' => 'admin::template','key' => 'TEMPLATE','type' => 0],

            ]
        );

        // SEO menu group (US-SEO-004, modification 20260711T114155): top-level
        // group + 3 screens — "Meta & JSON-LD" and "Sitemap.xml" (split
        // modification 20260711T154553, each its own RBAC permission), plus
        // "Redirect 301" (modification 20260712T011152, US-SEO-006) — kept in
        // this group rather than a new Unit/menu, same "1 screen/1 permission"
        // pattern (see modification_analysis_20260712T011152.md).
        $idBlockSeo = AdminMenu::insertGetId(
            [
                'parent_id' => 0,
                'sort'      => 70,
                'title'     => 'admin.menu_titles.ADMIN_SEO',
                'icon'      => 'nav-icon fas fa-search',
                'key'       => 'ADMIN_SEO',
            ]
        );

        AdminMenu::insertOrIgnore(
            [
                ['parent_id' => $idBlockSeo,'sort' => 1,'title' => 'admin.menu_titles.seo_meta_settings','icon' => 'fas fa-code','uri' => 'admin::seo_meta','key' => null,'type' => 0],
                ['parent_id' => $idBlockSeo,'sort' => 2,'title' => 'admin.menu_titles.seo_sitemap_settings','icon' => 'fas fa-sitemap','uri' => 'admin::seo_sitemap','key' => null,'type' => 0],
                ['parent_id' => $idBlockSeo,'sort' => 3,'title' => 'admin.menu_titles.seo_redirect_settings','icon' => 'fas fa-random','uri' => 'admin::seo_redirect','key' => null,'type' => 0],
            ]
        );

        FrontBannerType::insertOrIgnore(
            [
                ['id' => 1,'code' => 'banner', 'name' => 'Banner main'],  
                ['id' => 2,'code' => 'background', 'name' => 'Background website'],
                ['id' => 3,'code' => 'breadcrumb', 'name' => 'Breadcrumb'],
                ['id' => 4,'code' => 'banner-store', 'name' => 'Banner store'],
                ['id' => 5,'code' => 'banner-left', 'name' => 'Banner left'],
                ['id' => 6,'code' => 'banner-right', 'name' => 'Banner right'],
                ['id' => 7,'code' => 'other', 'name' => 'Other'],
            ]
        );
        FrontLinkGroup::insertOrIgnore(
            [
                ['id' => '1','code' => 'menu','name' => 'Menu main'],
                ['id' => '2','code' => 'menu_left','name' =>'Menu left'],
                ['id' => '3','code' => 'menu_right','name' =>'Menu right'],
                ['id' => '4','code' => 'footer','name' =>'Footer main'],
                ['id' => '5','code' => 'footer_left','name' =>'Footer left'],
                ['id' => '6','code' => 'footer_right','name' =>'Footer right'],
                ['id' => '7','code' => 'sidebar','name' =>'Sidebar'],
            ]
        );


        // Cannot use gp247 helper during installation, as it may not be fully loaded.
        $pageId = (string)\Illuminate\Support\Str::orderedUuid();
        $page = FrontPage::create([
            'id' => $pageId,
            'alias' => 'home',
            'image' => '',
            'status' => 0,
        ]);

        $descriptions = [
            'vi' => [
                'name' => 'Trang chủ',
                'keyword' => 'trang chu, home',
                'description' => 'Trang chủ website',
                'content' => '<h3>Chào mừng đến với CMS được tạo bởi hệ thống GP247</h3>'
            ],
            'en' => [
                'name' => 'Home page',
                'keyword' => 'home page',
                'description' => 'Website homepage',
                'content' => '<h3>Welcome to CMS created by GP247 system</h3>'
            ]
        ];

        foreach ($descriptions as $lang => $description) {
            FrontPageDescription::create([
                'page_id' => $pageId,
                'lang' => $lang,
                'name' => $description['name'],
                'keyword' => $description['keyword'],
                'description' => $description['description'],
                'content' => $description['content']
            ]);
        }
        $page->stores()->attach(GP247_STORE_ID_ROOT);
        $page->save();

        $pageAboutId = (string)\Illuminate\Support\Str::orderedUuid();
        $pageAbout = FrontPage::create([
            'id' => $pageAboutId,
            'alias' => 'about',
            'image' => '',
            'status' => 1,
        ]);

        $descriptionsAbout = [
            'vi' => [
                'name' => 'Giới thiệu về GP247',
                'keyword' => 'giới thiệu GP247, giải pháp website miễn phí, doanh nghiệp',
                'description' => 'GP247 - Giải pháp website miễn phí dành cho doanh nghiệp',
                'content' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
            ],
            'en' => [
                'name' => 'About GP247',
                'keyword' => 'about GP247, free website solutions, business',
                'description' => 'GP247 - Free website solutions for businesses',
                'content' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
            ]
        ];

        foreach ($descriptionsAbout as $lang => $description) {
            FrontPageDescription::create([
                'page_id' => $pageAboutId,
                'lang' => $lang,
                'name' => $description['name'],
                'keyword' => $description['keyword'],
                'description' => $description['description'],
                'content' => $description['content']
            ]);
        }
        $pageAbout->stores()->attach(GP247_STORE_ID_ROOT);
        $pageAbout->save();

        $links = [
            // Menu links
            [
                'name' => 'GP247',
                'url' => 'https://gp247.net',
                'target' => '_self',
                'group' => 'menu', // menu main
                'sort' => 1,
                'status' => 1,
            ],
            [
                'name' => 'About',
                'url' => 'route_front::front.page.detail:alias__about',
                'target' => '_self', 
                'group' => 'menu', // menu main
                'sort' => 2,
                'status' => 1,
            ],
            // Footer links
            [
                'name' => 'Privacy Policy',
                'url' => '#',
                'target' => '_self',
                'group' => 'footer', // footer main
                'sort' => 1,
                'status' => 1,
            ],
            [
                'name' => 'Terms of Use',
                'url' => '#',
                'target' => '_self',
                'group' => 'footer', // footer main
                'sort' => 2,
                'status' => 1,
            ],
        ];

        foreach ($links as $link) {
            $frontLink = FrontLink::create([
                'id' => (string)\Illuminate\Support\Str::orderedUuid(),
                'name' => $link['name'],
                'url' => $link['url'],
                'target' => $link['target'],
                'group' => $link['group'],
                'sort' => $link['sort'],
                'status' => $link['status'],
            ]);

            // Attach to store using model relationship
            $frontLink->stores()->attach(GP247_STORE_ID_ROOT);
        }

        // Language rows are seeded by the dedicated DataFrontLanguageSeeder so
        // they can be refreshed independently of menu/banner/page/link data.
        $this->call(DataFrontLanguageSeeder::class);
    }

    public function updateDataVersion() {

    }

}
