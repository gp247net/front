<?php

namespace GP247\Front\DB\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use GP247\Core\Admin\Models\AdminMenu;
use GP247\Core\Admin\Models\Languages;
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
                ['parent_id' => $idBlockAdmin,'sort' => 4,'title' => 'admin.menu_titles.block_content','icon' => 'far fa-newspaper','uri' => 'admin::store_block','key' => null,'type' => 0],
                ['parent_id' => $idBlockAdmin,'sort' => 5,'title' => 'admin.menu_titles.block_link','icon' => 'fab fa-chrome','uri' => 'admin::store_link','key' => null,'type' => 0],
                
            ]
        );

        Languages::insertOrIgnore(
            [
                ['code' => 'admin.menu_titles.ADMIN_CONTENT','text' => 'Nội dung','position' => 'admin.menu_titles','location' => 'vi'],
                ['code' => 'admin.menu_titles.ADMIN_CONTENT','text' => 'Content','position' => 'admin.menu_titles','location' => 'en'],
                ['code' => 'admin.menu_titles.block_content','text' => 'Khối nội dung','position' => 'admin.menu_titles','location' => 'vi'],
                ['code' => 'admin.menu_titles.block_content','text' => 'Block content','position' => 'admin.menu_titles','location' => 'en'],
                ['code' => 'admin.menu_titles.block_link','text' => 'Quản lý liên kết','position' => 'admin.menu_titles','location' => 'vi'],
                ['code' => 'admin.menu_titles.block_link','text' => 'Link manager','position' => 'admin.menu_titles','location' => 'en'],
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
                ['code' => 'admin.layout_page_position.all','text' => 'All Page','position' => 'admin.layout_page_position','location' => 'en'],
                ['code' => 'admin.layout_page_position.home','text' => 'Home page','position' => 'admin.layout_page_position','location' => 'en'],
                ['code' => 'admin.layout_page_position.shop_home','text' => 'Home shop','position' => 'admin.layout_page_position','location' => 'en'],
                ['code' => 'admin.layout_page_position.shop_search','text' => 'Search page','position' => 'admin.layout_page_position','location' => 'en'],
                ['code' => 'admin.layout_page_position.vendor_home','text' => 'Store: home','position' => 'admin.layout_page_position','location' => 'en'],
                ['code' => 'admin.layout_page_position.store_product_list','text' => 'Store: product list','position' => 'admin.layout_page_position','location' => 'en'],
                ['code' => 'admin.layout_page_position.product_list','text' => 'List: product','position' => 'admin.layout_page_position','location' => 'en'],
                ['code' => 'admin.layout_page_position.product_detail','text' => 'Detail: product','position' => 'admin.layout_page_position','location' => 'en'],
                ['code' => 'admin.layout_page_position.shop_cart','text' => 'Cart: cart, wishlist, compare, checkout','position' => 'admin.layout_page_position','location' => 'en'],
                ['code' => 'admin.layout_page_position.shop_auth','text' => 'Auth: login, forgot, register','position' => 'admin.layout_page_position','location' => 'en'],
                ['code' => 'admin.layout_page_position.shop_profile','text' => 'Customer profile','position' => 'admin.layout_page_position','location' => 'en'],
                ['code' => 'admin.layout_page_position.item_list','text' => 'List: category, brand, supplier','position' => 'admin.layout_page_position','location' => 'en'],
                ['code' => 'admin.layout_page_position.item_detail','text' => 'Detail: item','position' => 'admin.layout_page_position','location' => 'en'],
                ['code' => 'admin.layout_page_position.news_list','text' => 'List:  Blog/news','position' => 'admin.layout_page_position','location' => 'en'],
                ['code' => 'admin.layout_page_position.news_detail','text' => 'Detail: entry Blog','position' => 'admin.layout_page_position','location' => 'en'],
                ['code' => 'admin.layout_page_position.content_list','text' => 'List: content CMS','position' => 'admin.layout_page_position','location' => 'en'],
                ['code' => 'admin.layout_page_position.content_detail','text' => 'Detail: entry CMS','position' => 'admin.layout_page_position','location' => 'en'],
                ['code' => 'admin.layout_page_position.shop_contact','text' => 'Page contact','position' => 'admin.layout_page_position','location' => 'en'],
                ['code' => 'admin.layout_page_position.shop_page','text' => 'Other page: about...','position' => 'admin.layout_page_position','location' => 'en'],
                ['code' => 'admin.layout_page_position.all','text' => 'Tất cả trang','position' => 'admin.layout_page_position','location' => 'vi'],
                ['code' => 'admin.layout_page_position.home','text' => 'Trang chủ','position' => 'admin.layout_page_position','location' => 'vi'],
                ['code' => 'admin.layout_page_position.shop_home','text' => 'Trang chủ Shop','position' => 'admin.layout_page_position','location' => 'vi'],
                ['code' => 'admin.layout_page_position.shop_search','text' => 'Trang tìm kiếm','position' => 'admin.layout_page_position','location' => 'vi'],
                ['code' => 'admin.layout_page_position.vendor_home','text' => 'Cửa hàng: trang chủ','position' => 'admin.layout_page_position','location' => 'vi'],
                ['code' => 'admin.layout_page_position.store_product_list','text' => 'Cửa hàng: danh sách sản phẩm','position' => 'admin.layout_page_position','location' => 'vi'],
                ['code' => 'admin.layout_page_position.product_list','text' => 'Danh sách: sản phẩm','position' => 'admin.layout_page_position','location' => 'vi'],
                ['code' => 'admin.layout_page_position.product_detail','text' => 'Chi tiết: sản phẩm','position' => 'admin.layout_page_position','location' => 'vi'],
                ['code' => 'admin.layout_page_position.shop_cart','text' => 'Giỏ hàng: giỏ hàng, wishlist, compare, checkout','position' => 'admin.layout_page_position','location' => 'vi'],
                ['code' => 'admin.layout_page_position.shop_auth','text' => 'Chứng thực: đăng nhập, đăng ký, quên mật khẩu','position' => 'admin.layout_page_position','location' => 'vi'],
                ['code' => 'admin.layout_page_position.shop_profile','text' => 'Tài khoản khách hàng','position' => 'admin.layout_page_position','location' => 'vi'],
                ['code' => 'admin.layout_page_position.item_list','text' => 'Danh sách item: danh mục, thương hiệu, nhà cung cấp','position' => 'admin.layout_page_position','location' => 'vi'],
                ['code' => 'admin.layout_page_position.item_detail','text' => 'Chi tiết: item','position' => 'admin.layout_page_position','location' => 'vi'],
                ['code' => 'admin.layout_page_position.news_list','text' => 'Danh sách: bài viết Blog','position' => 'admin.layout_page_position','location' => 'vi'],
                ['code' => 'admin.layout_page_position.news_detail','text' => 'Chi tiết: bài viết Blog','position' => 'admin.layout_page_position','location' => 'vi'],
                ['code' => 'admin.layout_page_position.content_list','text' => 'Danh sách: bài viết CMS','position' => 'admin.layout_page_position','location' => 'vi'],
                ['code' => 'admin.layout_page_position.content_detail','text' => 'Chi tiết: bài viết CMS','position' => 'admin.layout_page_position','location' => 'vi'],
                ['code' => 'admin.layout_page_position.shop_contact','text' => 'Trang liên hệ','position' => 'admin.layout_page_position','location' => 'vi'],
                ['code' => 'admin.layout_page_position.shop_page','text' => 'Các trang viết: giới thiệu...','position' => 'admin.layout_page_position','location' => 'vi'],
                ['code' => 'admin.layout_page_block.header','text' => 'Head code :meta, css, javascript,...','position' => 'admin.layout_page_block','location' => 'vi'],
                ['code' => 'admin.layout_page_block.top','text' => 'Block Top','position' => 'admin.layout_page_block','location' => 'vi'],
                ['code' => 'admin.layout_page_block.bottom','text' => 'Block Bottom','position' => 'admin.layout_page_block','location' => 'vi'],
                ['code' => 'admin.layout_page_block.left','text' => 'BlockLeft - Cột trái','position' => 'admin.layout_page_block','location' => 'vi'],
                ['code' => 'admin.layout_page_block.right','text' => 'Block Right - Cột phải','position' => 'admin.layout_page_block','location' => 'vi'],
                ['code' => 'admin.layout_page_block.banner_top','text' => 'Block banner top','position' => 'admin.layout_page_block','location' => 'vi'],
                ['code' => 'admin.layout_page_block.header','text' => 'Head code: meta, css, javascript, ...','position' => 'admin.layout_page_block','location' => 'en'],
                ['code' => 'admin.layout_page_block.top','text' => 'Block Top','position' => 'admin.layout_page_block','location' => 'en'],
                ['code' => 'admin.layout_page_block.bottom','text' => 'Block Bottom','position' => 'admin.layout_page_block','location' => 'en'],
                ['code' => 'admin.layout_page_block.left','text' => 'Block Left','position' => 'admin.layout_page_block','location' => 'en'],
                ['code' => 'admin.layout_page_block.right','text' => 'Block Right','position' => 'admin.layout_page_block','location' => 'en'],
                ['code' => 'admin.layout_page_block.banner_top','text' => 'Block banner top','position' => 'admin.layout_page_block','location' => 'en'],
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
                ['code' => 'front.notfound','text' => 'Không tìm thấy dữ liệu','position' => 'front','location' => 'vi'], 
                ['code' => 'front.notfound_detail','text' => 'Dữ liệu bạn đang truy cập không tìm thấy','position' => 'front','location' => 'vi'], 
                ['code' => 'front.notfound','text' => 'Data not found','position' => 'front','location' => 'en'], 
                ['code' => 'front.notfound_detail','text' => 'The data you are accessing is not found','position' => 'front','location' => 'en'], 
                ['code' => 'front.backhome','text' => 'Trở về trang chủ','position' => 'front','location' => 'vi'], 
                ['code' => 'front.backhome','text' => 'Back home','position' => 'front','location' => 'en'], 
                ['code' => 'front.view_not_exist','text' => 'View ":view" không tồn tại','position' => 'front','location' => 'vi'],
                ['code' => 'front.view_not_exist','text' => 'View ":view" not exist','position' => 'front','location' => 'en'],
            ]
        );

    }

    public function updateDataVersion() {

    }

}
