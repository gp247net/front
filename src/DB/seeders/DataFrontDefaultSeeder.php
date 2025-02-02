<?php

namespace GP247\Front\DB\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use GP247\Core\Admin\Models\AdminMenu;
use GP247\Core\Admin\Models\Languages;
use GP247\Front\Models\FrontBannerType;
use GP247\Front\Models\FrontLinkGroup;
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


        // Insert new data
        $idBlockAdmin = AdminMenu::insertGetId(
            [
                'parent_id' => 0,
                'sort'      => 5,
                'title'     => 'admin.menu_titles.ADMIN_CONTENT',
                'icon'      => 'nav-icon fas fa-city',
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


        Languages::insertOrIgnore(
            [
                ['code' => 'admin.menu_titles.ADMIN_CONTENT','text' => 'CMS','position' => 'admin.menu_titles','location' => 'vi'],
                ['code' => 'admin.menu_titles.ADMIN_CONTENT','text' => 'CMS','position' => 'admin.menu_titles','location' => 'en'],
                ['code' => 'admin.menu_titles.layout_block','text' => 'Khối bố cục','position' => 'admin.menu_titles','location' => 'vi'],
                ['code' => 'admin.menu_titles.layout_block','text' => 'Layout block','position' => 'admin.menu_titles','location' => 'en'],
                ['code' => 'admin.menu_titles.link_block','text' => 'Quản lý liên kết','position' => 'admin.menu_titles','location' => 'vi'],
                ['code' => 'admin.menu_titles.link_block','text' => 'Link manager','position' => 'admin.menu_titles','location' => 'en'],
                ['code' => 'admin.menu_titles.page_manager','text' => 'Quản lý trang','position' => 'admin.menu_titles','location' => 'vi'],
                ['code' => 'admin.menu_titles.page_manager','text' => 'Page manager','position' => 'admin.menu_titles','location' => 'en'],
                ['code' => 'admin.menu_titles.banner','text' => 'Banner','position' => 'admin.menu_titles','location' => 'vi'],
                ['code' => 'admin.menu_titles.banner','text' => 'Banners','position' => 'admin.menu_titles','location' => 'en'],
                ['code' => 'admin.menu_titles.page_manager','text' => 'Quản lý trang','position' => 'admin.menu_titles','location' => 'vi'],
                ['code' => 'admin.menu_titles.page_manager','text' => 'Page manager','position' => 'admin.menu_titles','location' => 'en'],
                ['code' => 'admin.link.list','text' => 'Danh sách liên kết','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link.list','text' => 'Link list','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link.type','text' => 'Loại liên kết','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link.type','text' => 'Link type','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link.collection','text' => 'Bộ sưu tập','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link.collection','text' => 'Collection','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link.name','text' => 'Tên','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link.url','text' => 'Đường dẫn','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link.target','text' => 'Target','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link.group','text' => 'Nhóm','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link.sort','text' => 'Thứ tự','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link.status','text' => 'Trạng thái','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link.add_new','text' => 'Thêm mới','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link.add_collection_new','text' => 'Thêm bộ sưu tập mới','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link.add_collection_new','text' => 'Add collection new','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link.add_new_title','text' => 'Tạo url','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link.add_new_collection_title','text' => 'Tạo bộ sưu tập mới','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link.add_new_collection_title','text' => 'Add new collection','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link.add_new_des','text' => 'Tạo mới url','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link.add_new_collection_des','text' => 'Tạo bộ sưu tập mới','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link.add_new_collection_des','text' => 'Add new collection','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link.select_group','text' => 'Chọn nhóm','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link.select_target','text' => 'Chọn target','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link.select_collection','text' => 'Chọn bộ sưu tập','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link.helper_url','text' => 'Ví dụ: url, path, hoặc route::name','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link.name','text' => 'Name','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link.url','text' => 'Url','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link.target','text' => 'Target','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link.group','text' => 'Group','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link.status','text' => 'Status','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link.sort','text' => 'Sort','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link.add_new','text' => 'Add new','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link.add_new_title','text' => 'Add layout url','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link.add_new_des','text' => 'Create a new layout url','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link.select_group','text' => 'Select group','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link.select_target','text' => 'Select target','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link.select_collection','text' => 'Select collection','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link.helper_url','text' => 'Ex: url, path, or route::name','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link_position.menu','text' => 'Menu','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link_position.menu_left','text' => 'Menu trái','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link_position.menu_right','text' => 'Menu phải','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link_position.footer','text' => 'Footer','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link_position.footer_left','text' => 'Footer trái','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link_position.footer_right','text' => 'Footer phải','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link_position.sidebar','text' => 'Thanh bên','position' => 'admin.link','location' => 'vi'],
                ['code' => 'admin.link_position.menu','text' => 'Menu','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link_position.menu_left','text' => 'Menu left','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link_position.menu_right','text' => 'Menu right','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link_position.footer','text' => 'Footer','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link_position.footer_left','text' => 'Footer left','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link_position.footer_right','text' => 'Footer right','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link_position.sidebar','text' => 'Sidebar','position' => 'admin.link','location' => 'en'],
                ['code' => 'admin.link_group.list','text' => 'Danh sách nhóm link','position' => 'admin.link_group','location' => 'vi'],
                ['code' => 'admin.link_group.list','text' => 'Link group list','position' => 'admin.link_group','location' => 'en'],
                ['code' => 'admin.link_group.add_new_title','text' => 'Thêm loại mới','position' => 'admin.link_group','location' => 'vi'],
                ['code' => 'admin.link_group.add_new_title','text' => 'Add new type','position' => 'admin.link_group','location' => 'en'],
                ['code' => 'admin.link_group.code','text' => 'Mã','position' => 'admin.link_group','location' => 'vi'],
                ['code' => 'admin.link_group.code','text' => 'Code','position' => 'admin.link_group','location' => 'en'],
                ['code' => 'admin.link_group.name','text' => 'Tên','position' => 'admin.link_group','location' => 'vi'],
                ['code' => 'admin.link_group.name','text' => 'Name','position' => 'admin.link_group','location' => 'en'],
                ['code' => 'admin.layout_block_page.all','text' => 'All Page','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block_page.home','text' => 'Home page','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block_page.all','text' => 'Tất cả trang','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block_page.page_detail','text' => 'Trang chi tiết','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block_page.page_detail','text' => 'Page detail','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block_page.list','text' => 'Trang danh sách','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block_page.list','text' => 'Page list','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block_page.contact','text' => 'Trang liên hệ','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block_page.contact','text' => 'Contact page','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block_page.search','text' => 'Trang tìm kiếm','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block_page.search','text' => 'Search page','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block_page.home','text' => 'Trang chủ','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block_position.header','text' => 'Head code :meta, css, javascript,...','position' => 'admin.layout_page_block','location' => 'vi'],
                ['code' => 'admin.layout_block_position.top','text' => 'Block Top','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block_position.bottom','text' => 'Block Bottom','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block_position.left','text' => 'BlockLeft - Cột trái','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block_position.right','text' => 'Block Right - Cột phải','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block_position.banner_top','text' => 'Block banner top','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block_position.header','text' => 'Head code: meta, css, javascript, ...','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block_position.top','text' => 'Block Top','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block_position.bottom','text' => 'Block Bottom','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block_position.left','text' => 'Block Left','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block_position.right','text' => 'Block Right','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block_position.banner_top','text' => 'Block banner top','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block.list','text' => 'Danh sách block','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block.list','text' => 'Block list','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block.name','text' => 'Tên','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block.position','text' => 'Vị trí','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block.page','text' => 'Trang','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block.type','text' => 'Loại','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block.text','text' => 'Nội dung','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block.status','text' => 'Trạng thái','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block.name','text' => 'Name','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block.position','text' => 'Position','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block.page','text' => 'Page','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block.type','text' => 'Type','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block.text','text' => 'Text','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block.status','text' => 'Status','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block.sort','text' => 'Sort','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block.sort','text' => 'Sắp xếp','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block.add_new_title','text' => 'Add layout','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block.add_new_des','text' => 'Create a new layout','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block.select_position','text' => 'Select position','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block.select_page','text' => 'Select page','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block.helper_html','text' => 'Basic HTML content.','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block.helper_view','text' => 'View files are in "app/GP247/Templates/:template/block" directory.','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block.helper_module','text' => 'File in "app/Plugins/Block". Module must have render() method available to display content.','position' => 'admin.layout_block','location' => 'en'],
                ['code' => 'admin.layout_block.add_new_title','text' => 'Tạo bố cục','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block.add_new_des','text' => 'Tạo mới bố cục','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block.select_position','text' => 'Chọn vị trí','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block.select_page','text' => 'Chọn trang','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block.helper_html','text' => 'Basic HTML content.','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block.helper_view','text' => 'File trong thư mục "app/GP247/Templates/:template/block".','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.layout_block.helper_module','text' => 'File trong "app/Plugins/Block". Module phải có hàm render().','position' => 'admin.layout_block','location' => 'vi'],
                ['code' => 'admin.page.title','text' => 'Tiêu đề','position' => 'admin.page','location' => 'vi'],
                ['code' => 'admin.page.image','text' => 'Hình ảnh','position' => 'admin.page','location' => 'vi'],
                ['code' => 'admin.page.alias','text' => 'URL tùy chỉnh <span class="seo" title="SEO"><i class="fa fa-coffee" aria-hidden="true"></i></span>','position' => 'admin.page','location' => 'vi'],
                ['code' => 'admin.page.alias_validate','text' => 'Tối đa 100 kí tự trong nhóm: "A-Z", "a-z", "0-9" and "-_" ','position' => 'admin.page','location' => 'vi'],
                ['code' => 'admin.page.key_validate','text' => 'Chỉ sử dụng kí tự trong nhóm: "A-Z", "a-z", "0-9" and ".-_" ','position' => 'admin.page','location' => 'vi'],
                ['code' => 'admin.page.status','text' => 'Trạng thái','position' => 'admin.page','location' => 'vi'],
                ['code' => 'admin.page.keyword','text' => 'Từ khóa','position' => 'admin.page','location' => 'vi'],
                ['code' => 'admin.page.description','text' => 'Mô tả','position' => 'admin.page','location' => 'vi'],
                ['code' => 'admin.page.content','text' => 'Nội dung','position' => 'admin.page','location' => 'vi'],
                ['code' => 'admin.page.title','text' => 'Title','position' => 'admin.page','location' => 'en'],
                ['code' => 'admin.page.image','text' => 'Image','position' => 'admin.page','location' => 'en'],
                ['code' => 'admin.page.alias','text' => 'Url customize <span class="seo" title="SEO"><i class="fa fa-coffee" aria-hidden="true"></i></span>','position' => 'admin.page','location' => 'en'],
                ['code' => 'admin.page.alias_validate','text' => 'Maximum 100 characters in the group: "A-Z", "a-z", "0-9" and "-_" ','position' => 'admin.page','location' => 'en'],
                ['code' => 'admin.page.status','text' => 'Status','position' => 'admin.page','location' => 'en'],
                ['code' => 'admin.page.keyword','text' => 'Keyword','position' => 'admin.page','location' => 'en'],
                ['code' => 'admin.page.description','text' => 'Description','position' => 'admin.page','location' => 'en'],
                ['code' => 'admin.page.content','text' => 'Content','position' => 'admin.page','location' => 'en'],
                ['code' => 'admin.page.key_validate','text' => 'Only characters in the group: "A-Z", "a-z", "0-9" and ".-_" ','position' => 'admin.page','location' => 'en'],
                ['code' => 'admin.page.add_new','text' => 'Add new','position' => 'admin.page','location' => 'en'],
                ['code' => 'admin.page.add_new_title','text' => 'Add page','position' => 'admin.page','location' => 'en'],
                ['code' => 'admin.page.add_new_des','text' => 'Create a new page','position' => 'admin.page','location' => 'en'],
                ['code' => 'admin.page.add_new_title','text' => 'Tạo trang','position' => 'admin.page','location' => 'vi'],
                ['code' => 'admin.page.add_new_des','text' => 'Tạo mới trang','position' => 'admin.page','location' => 'vi'],
                ['code' => 'admin.page.choose_image','text' => 'Chọn ảnh','position' => 'admin.page','location' => 'vi'],
                ['code' => 'admin.page.choose_image','text' => 'Chose image','position' => 'admin.page','location' => 'en'],
                ['code' => 'admin.page.list','text' => 'Danh sách trang','position' => 'admin.page','location' => 'vi'],
                ['code' => 'admin.page.list','text' => 'Page list','position' => 'admin.page','location' => 'en'],
                ['code' => 'admin.page.search_place','text' => 'Tìm tên','position' => 'admin.page','location' => 'vi'],
                ['code' => 'admin.page.search_place','text' => 'Search name','position' => 'admin.page','location' => 'en'],
                ['code' => 'admin.banner.type','text' => 'Loại','position' => 'admin.banner','location' => 'vi'],
                ['code' => 'admin.banner.type','text' => 'Type','position' => 'admin.banner','location' => 'en'],
                ['code' => 'admin.banner.add_new','text' => 'Thêm mới banner','position' => 'admin.banner','location' => 'vi'],
                ['code' => 'admin.banner.add_new','text' => 'Add new banner','position' => 'admin.banner','location' => 'en'],
                ['code' => 'admin.banner.image','text' => 'Hình ảnh','position' => 'admin.banner','location' => 'vi'],
                ['code' => 'admin.banner.image','text' => 'Image','position' => 'admin.banner','location' => 'en'],
                ['code' => 'admin.banner.title','text' => 'Tiêu đề','position' => 'admin.banner','location' => 'vi'],
                ['code' => 'admin.banner.title','text' => 'Title','position' => 'admin.banner','location' => 'en'],
                ['code' => 'admin.banner.url','text' => 'URL','position' => 'admin.banner','location' => 'vi'],
                ['code' => 'admin.banner.url','text' => 'URL','position' => 'admin.banner','location' => 'en'],
                ['code' => 'admin.banner.sort','text' => 'Thứ tự','position' => 'admin.banner','location' => 'vi'],
                ['code' => 'admin.banner.sort','text' => 'Sort','position' => 'admin.banner','location' => 'en'],
                ['code' => 'admin.banner.status','text' => 'Trạng thái','position' => 'admin.banner','location' => 'vi'],
                ['code' => 'admin.banner.status','text' => 'Status','position' => 'admin.banner','location' => 'en'],
                ['code' => 'admin.banner.click','text' => 'Bấm chuột','position' => 'admin.banner','location' => 'vi'],
                ['code' => 'admin.banner.click','text' => 'Click','position' => 'admin.banner','location' => 'en'],
                ['code' => 'admin.banner.target','text' => 'Target','position' => 'admin.banner','location' => 'vi'],
                ['code' => 'admin.banner.target','text' => 'Target','position' => 'admin.banner','location' => 'en'],
                ['code' => 'admin.banner.select_target','text' => 'Chọn target','position' => 'admin.banner','location' => 'vi'],
                ['code' => 'admin.banner.select_target','text' => 'Select target','position' => 'admin.banner','location' => 'en'],
                ['code' => 'admin.banner.list','text' => 'Danh sách banner','position' => 'admin.banner','location' => 'vi'],
                ['code' => 'admin.banner.list','text' => 'Banner list','position' => 'admin.banner','location' => 'en'],
                ['code' => 'admin.banner_type.list','text' => 'Loại banner','position' => 'admin.banner','location' => 'vi'],
                ['code' => 'admin.banner_type.list','text' => 'Banner type list','position' => 'admin.banner','location' => 'en'],
                ['code' => 'admin.banner_type.add_new_title','text' => 'Thêm loại mới','position' => 'admin.banner','location' => 'vi'],
                ['code' => 'admin.banner_type.add_new_title','text' => 'Add new type','position' => 'admin.banner','location' => 'en'],
                ['code' => 'admin.banner_type.code','text' => 'Mã','position' => 'admin.banner','location' => 'vi'],
                ['code' => 'admin.banner_type.code','text' => 'Code','position' => 'admin.banner','location' => 'en'],
                ['code' => 'admin.banner_type.name','text' => 'Tên','position' => 'admin.banner','location' => 'vi'],
                ['code' => 'admin.banner_type.name','text' => 'Name','position' => 'admin.banner','location' => 'en'],

                ['code' => 'front.404','text' => 'Trang không tồn tại','position' => 'front','location' => 'vi'],
                ['code' => 'front.404_detail','text' => 'Trang bạn đang truy cập không tồn tại','position' => 'front','location' => 'vi'],
                ['code' => 'front.404_detail','text' => 'The page you are accessing does not exist','position' => 'front','location' => 'en'],
                ['code' => 'front.404','text' => 'Page not exist','position' => 'front','location' => 'en'],
                ['code' => 'front.no_item','text' => 'Chưa có mục nào','position' => 'front','location' => 'vi'],
                ['code' => 'front.no_item','text' => 'No items yet','position' => 'front','location' => 'en'],
                ['code' => 'front.notfound','text' => 'Không tìm thấy dữ liệu','position' => 'front','location' => 'vi'], 
                ['code' => 'front.notfound_detail','text' => 'Dữ liệu bạn đang truy cập không tìm thấy','position' => 'front','location' => 'vi'], 
                ['code' => 'front.notfound','text' => 'Data not found','position' => 'front','location' => 'en'], 
                ['code' => 'front.notfound_detail','text' => 'The data you are accessing is not found','position' => 'front','location' => 'en'], 
                ['code' => 'front.backhome','text' => 'Trở về trang chủ','position' => 'front','location' => 'vi'], 
                ['code' => 'front.backhome','text' => 'Back home','position' => 'front','location' => 'en'], 
                ['code' => 'front.view_not_exist','text' => 'View ":view" không tồn tại','position' => 'front','location' => 'vi'],
                ['code' => 'front.view_not_exist','text' => 'View ":view" not exist','position' => 'front','location' => 'en'],


                ['code' => 'front.home','text' => 'Trang chủ','position' => 'front','location' => 'vi'],
                ['code' => 'front.home','text' => 'Home','position' => 'front','location' => 'en'],
                ['code' => 'front.link_useful','text' => 'Liên kết hữu ích','position' => 'front','location' => 'vi'],
                ['code' => 'front.link_useful','text' => 'Link useful','position' => 'front','location' => 'en'],
                ['code' => 'front.brands','text' => 'Nhãn hàng','position' => 'front','location' => 'vi'],
                ['code' => 'front.brands','text' => 'Brands','position' => 'front','location' => 'en'],
                ['code' => 'front.blog','text' => 'Tin Tức','position' => 'front','location' => 'vi'],
                ['code' => 'front.blog','text' => 'Blogs','position' => 'front','location' => 'en'],
                ['code' => 'front.news','text' => 'Tin tức','position' => 'front','location' => 'vi'],
                ['code' => 'front.news','text' => 'News','position' => 'front','location' => 'en'],
                ['code' => 'front.about','text' => 'About us','position' => 'front','location' => 'en'],
                ['code' => 'front.about','text' => 'Giới thiệu','position' => 'front','location' => 'vi'],
                ['code' => 'front.contact','text' => 'Contact us','position' => 'front','location' => 'en'],
                ['code' => 'front.contact','text' => 'Liên hệ','position' => 'front','location' => 'vi'],
                ['code' => 'front.my_profile','text' => 'Tài khoản','position' => 'front','location' => 'vi'],
                ['code' => 'front.my_profile','text' => 'My profile','position' => 'front','location' => 'en'],
                ['code' => 'front.my_account','text' => 'Tài khoản','position' => 'front','location' => 'vi'],
                ['code' => 'front.my_account','text' => 'My account','position' => 'front','location' => 'en'],
                ['code' => 'front.account','text' => 'Tài khoản','position' => 'front','location' => 'vi'],
                ['code' => 'front.account','text' => 'customer','position' => 'front','location' => 'en'],
                ['code' => 'front.currency','text' => 'Tiền tệ','position' => 'front','location' => 'vi'],
                ['code' => 'front.currency','text' => 'Currency','position' => 'front','location' => 'en'],
                ['code' => 'front.language','text' => 'Ngôn ngữ','position' => 'front','location' => 'vi'],
                ['code' => 'front.language','text' => 'Language','position' => 'front','location' => 'en'],
                ['code' => 'front.login','text' => 'Đăng nhập','position' => 'front','location' => 'vi'],
                ['code' => 'front.login','text' => 'Login','position' => 'front','location' => 'en'],
                ['code' => 'front.logout','text' => 'Đăng xuất','position' => 'front','location' => 'vi'],
                ['code' => 'front.logout','text' => 'Logout','position' => 'front','location' => 'en'],
                ['code' => 'front.maintenace','text' => 'Bảo trì','position' => 'front','location' => 'vi'],
                ['code' => 'front.maintenace','text' => 'Maintenace','position' => 'front','location' => 'en'],
                ['code' => 'front.result_item','text' => 'Showing <b>:item_from</b>-<b>:item_to</b> of <b>:total</b> results</b>','position' => 'front','location' => 'en'],
                ['code' => 'front.result_item','text' => 'Hiển thị <b>:item_from</b>-<b>:item_to</b> của <b>:total</b> kết quả</b>','position' => 'front','location' => 'vi'],
                ['code' => 'front.all_product','text' => 'Tất cả sản phẩm','position' => 'front','location' => 'vi'],
                ['code' => 'front.all_product','text' => 'All products','position' => 'front','location' => 'en'],
                ['code' => 'front.data_notfound','text' => 'Không tìm thấy dữ liệu!','position' => 'front','location' => 'vi'],
                ['code' => 'front.data_notfound','text' => 'Data not found!','position' => 'front','location' => 'en'],
                ['code' => 'front.data_not_found','text' => 'Không tìm thấy dữ liệu!','position' => 'front','location' => 'vi'],
                ['code' => 'front.data_not_found','text' => 'Data not found!','position' => 'front','location' => 'en'],
                ['code' => 'front.data_not_found_title','text' => 'Không tìm thấy dữ liệu!','position' => 'front','location' => 'vi'],
                ['code' => 'front.data_not_found_title','text' => 'Data not found!','position' => 'front','location' => 'en'],
                ['code' => 'front.page_notfound','text' => 'Không tìm thấy trang!','position' => 'front','location' => 'vi'],
                ['code' => 'front.page_notfound','text' => 'Page not found!','position' => 'front','location' => 'en'],
                ['code' => 'front.page_not_found','text' => 'Không tìm thấy trang!','position' => 'front','location' => 'vi'],
                ['code' => 'front.page_not_found','text' => 'Page not found!','position' => 'front','location' => 'en'],
                ['code' => 'front.page_not_found_title','text' => 'Không tìm thấy trang!','position' => 'front','location' => 'vi'],
                ['code' => 'front.page_not_found_title','text' => 'Page not found!','position' => 'front','location' => 'en'],
                ['code' => 'front.search_result','text' => 'Kết quả tìm kiếm','position' => 'front','location' => 'vi'],
                ['code' => 'front.search_result','text' => 'Search result','position' => 'front','location' => 'en'],
                ['code' => 'front.view_not_exist','text' => 'Không có view :view','position' => 'front','location' => 'vi'],
                ['code' => 'front.view_not_exist','text' => 'View not found :view','position' => 'front','location' => 'en'],
                ['code' => 'front.welcome_back','text' => 'Chào mừng trở lại','position' => 'front','location' => 'vi'],
                ['code' => 'front.welcome_back','text' => 'Welcome back!','position' => 'front','location' => 'en'],
            ]
        );

    }

    public function updateDataVersion() {

    }

}
