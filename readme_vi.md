```
  _____  _____     ___  _  _   _____ 
 / ____|  __ \   |__ \| || | |___  |
| |  __| |__) |     ) | || |_   / / 
| | |_ |  ___/     / /|__   _| / /  
| |__| | |        / /_   | |  / /   
 \_____|_|       |____|  |_| /_/    
```

> 🌐 **Ngôn ngữ:** 🇻🇳 Tiếng Việt (hiện tại) · [🇬🇧 English](readme.md)

Gói xử lý giao diện cho gp247

`composer require gp247/front`

[![Tổng lượt tải](https://poser.pugx.org/gp247/front/d/total.svg)](https://packagist.org/packages/gp247/front)
[![Phiên bản ổn định mới nhất](https://poser.pugx.org/gp247/front/v/stable.svg)](https://packagist.org/packages/gp247/front)
[![Giấy phép](https://poser.pugx.org/gp247/front/license.svg)](https://packagist.org/packages/gp247/front)
[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/gp247net/front)

## Giới thiệu

GP247/Front là một gói CMS (Hệ thống Quản lý Nội dung) toàn diện cho doanh nghiệp, cung cấp các tính năng:

- Quản lý Nội dung Trang
- Hệ thống Mẫu Linh hoạt
- Hệ thống Plugin Mở rộng
- Quản lý Điều hướng & Liên kết
- Tích hợp Biểu mẫu Liên hệ & Đăng ký

## Cài đặt

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

## Tài liệu
Để xem tài liệu chi tiết, truy cập [tài liệu](https://gp247.net/vi/docs)

## Giấy phép
GP247/Front là phần mềm mã nguồn mở được cấp phép theo [giấy phép MIT](https://opensource.org/licenses/MIT). 