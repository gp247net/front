```
  _____  _____     ___  _  _   _____ 
 / ____|  __ \   |__ \| || | |___  |
| |  __| |__) |     ) | || |_   / / 
| | |_ |  ___/     / /|__   _| / /  
| |__| | |        / /_   | |  / /   
 \_____|_|       |____|  |_| /_/    
```

> 🌐 **Ngôn ngữ:** 🇻🇳 Tiếng Việt (hiện tại) · [🇬🇧 English](./README.md) · [🇯🇵 日本語](./README_ja.md)

[![Tests](https://github.com/gp247net/front/actions/workflows/tests.yml/badge.svg?branch=master)](https://github.com/gp247net/front/actions/workflows/tests.yml)

Gói nền tảng frontend & CMS cho GP247

`composer require gp247/front`

[![Tổng lượt tải](https://poser.pugx.org/gp247/front/d/total.svg)](https://packagist.org/packages/gp247/front)
[![Phiên bản ổn định mới nhất](https://poser.pugx.org/gp247/front/v/stable.svg)](https://packagist.org/packages/gp247/front)
[![Giấy phép](https://poser.pugx.org/gp247/front/license.svg)](https://packagist.org/packages/gp247/front)
[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/gp247net/front)

## Giới thiệu

GP247/Front là **gói nền tảng cho toàn bộ tầng frontend** của GP247 — không phải lớp giao diện/theme thuần túy. Gói này vừa cung cấp **website công khai (storefront)** và **hệ quản lý nội dung (CMS)**, vừa là **hạ tầng dùng chung** để các gói frontend khác (như `gp247/shop`) kế thừa. Đây là thành phần **bắt buộc** cho một site GP247 đầy đủ, đóng vai trò cho tầng frontend tương tự như `gp247/core` cho tầng quản trị.

Các tính năng chính:

- Quản lý nội dung trang (Page / CMS)
- Hệ thống template linh hoạt + cơ chế resolve view theo template đang dùng
- Hệ thống plugin mở rộng
- Quản lý điều hướng, menu, liên kết & banner
- Tích hợp biểu mẫu liên hệ & đăng ký
- Hạ tầng dùng chung cho frontend (base Livewire component, layout, route) để các gói khác cắm vào

## Cài đặt

> 📘 **Hướng dẫn cài đặt đầy đủ** — chọn bộ cài (S-Cart, GP247 CMS, chỉ core), hosting không có dòng lệnh, thêm front/shop vào site đang có, xử lý sự cố: [Cài đặt GP247 và S-Cart](https://github.com/gp247net/gp247-docs/blob/main/install-upgrade/install_vi.md).

1. Cài đặt gói

    >`composer require gp247/front`


2. Cấu hình routing: mở file `bootstrap/app.php`:

  - Comment dòng cấu hình web routes:

    ```php
    //GP247 comment
    //web: __DIR__.'/../routes/web.php',
    ```


3. Đăng ký service provider trong `bootstrap/providers.php`:
    ```php
    return [
        // ... các providers hiện có
        GP247\Front\FrontServiceProvider::class,
    ];
    ```

4. Chạy lệnh cài đặt:
>`php artisan gp247:front-install`

## Tính năng chính

### Quản lý Trang
- Tạo và quản lý trang tĩnh
- Hỗ trợ SEO cho từng trang
- Kiểm soát truy cập

### Giao diện
- Hệ thống Mẫu Linh hoạt
- Bố cục tùy chỉnh cho từng phần
- Thiết kế tương thích

Tùy chỉnh giao diện quản trị:

>`php artisan vendor:publish --tag=gp247:front-admin`

Các view sẽ được lưu trữ tại: `resources/views/vendor/gp247-front-admin`

Cập nhật view của template mặc định (GP247Front):

>`php artisan vendor:publish --tag=gp247:front-view`

Các view sẽ được lưu trữ tại: `app/GP247/Templates/GP247Front`

Publish tài nguyên public (css/js/ảnh) của template mặc định:

>`php artisan vendor:publish --tag=gp247:front-public`

Các tài nguyên sẽ được lưu trữ tại: `public/GP247/Templates/GP247Front`

### Mở rộng
- Hỗ trợ Plugin
- Tích hợp module tùy chỉnh
- API để phát triển tính năng

### Ghi đè (override) controller

>Bước 1: Copy các file controller muốn ghi đè trong `vendor/gp247/front/src/Controllers` (hoặc `src/Api/Controllers`) -> `app/GP247/Front/Controllers` (hoặc `app/GP247/Front/Api/Controllers`)

>Bước 2: Đổi namespace từ `GP247\Front\Controllers` (hoặc `GP247\Front\Api\Controllers`) thành `App\GP247\Front\Controllers` (hoặc `App\GP247\Front\Api\Controllers`) — chỉ thêm `App` vào phía trước, giữ nguyên phần còn lại.

## Chạy test

Bộ test của `gp247/front` nằm ở thư mục `tests/` của repo này và chạy tự động trên GitHub Actions cho mỗi thay đổi
(PHP 8.3 / 8.4 × MySQL / MariaDB). Thư mục test không có trong gói Composer, nên muốn tự chạy thì clone repo:

1. Clone repo này.
2. Tạo một ứng dụng Laravel trắng, cài `gp247/front` **từ bản clone** theo mục Cài đặt ở trên, cùng `gp247/core` từ 3.18.0
   trở lên (lớp test gốc nằm trong `GP247\Core\Testing`). Test kiểm bản được cài trong `vendor/` của ứng dụng, nên trỏ
   Composer vào bản clone trước:
   `composer config repositories.front '{"type": "path", "url": "/đường/dẫn/tới/front", "options": {"symlink": false}}'`,
   rồi `composer require "gp247/core:^3.18" "gp247/front:*@dev"`. Dùng một cơ sở dữ liệu **chỉ dùng cho test** — tên
   kết thúc bằng `_test` hoặc `_testing`. Bộ test từ chối chạy trên cơ sở dữ liệu khác để không đụng tới dữ liệu thật;
   muốn dùng tên khác thì khai rõ `GP247_TEST_DATABASE=<tên cơ sở dữ liệu>`.
3. Từ thư mục gốc của ứng dụng chạy:

   ```bash
   GP247_HOST_APP="$PWD" vendor/bin/phpunit -c /đường/dẫn/tới/front/phpunit.xml.dist
   ```

Test của package khác và của plugin cần mở cửa hàng có thể dùng lại các helper trong `GP247\Front\Testing`:
`InteractsWithFrontStore` (mở cửa hàng cho test), `ConfiguresSeoStore` (đặt các công tắc SEO của cửa hàng) và
`ConfiguresTracking` (thiết lập theo dõi và đồng ý cookie).

## Tài liệu
Để xem tài liệu chi tiết, truy cập [tài liệu](https://gp247.net/vi/docs)

## Giấy phép
GP247/Front là phần mềm mã nguồn mở được cấp phép theo [giấy phép MIT](https://opensource.org/licenses/MIT). 