<p align="center">
    <img src="https://static.gp247.net/logo/logo.png" width="150">
</p>
<p align="center">Gói xử lý giao diện cho gp247<br>
    <code><b>composer require gp247/front</b></code></p>

<p align="center">
<a href="https://packagist.org/packages/gp247/front"><img src="https://poser.pugx.org/gp247/front/d/total.svg" alt="Tổng lượt tải"></a>
<a href="https://packagist.org/packages/gp247/front"><img src="https://poser.pugx.org/gp247/front/v/stable.svg" alt="Phiên bản ổn định mới nhất"></a>
<a href="https://packagist.org/packages/gp247/front"><img src="https://poser.pugx.org/gp247/front/license.svg" alt="Giấy phép"></a>
</p>

## Giới thiệu

GP247/Front là một gói CMS (Hệ thống Quản lý Nội dung) toàn diện cho doanh nghiệp, cung cấp các tính năng:

- Quản lý Nội dung Trang
- Hệ thống Mẫu Linh hoạt
- Hệ thống Plugin Mở rộng
- Quản lý Điều hướng & Liên kết
- Tích hợp Biểu mẫu Liên hệ & Đăng ký

## Cài đặt

1. Cài đặt thông qua Composer:
```bash
composer require gp247/front
```

2. Đảm bảo nội dung trong `routes/web.php` được xóa hoặc comment:
```php
// Route::get('/', function () {
//     return view('welcome');
// });
```

3. Đăng ký service provider trong `bootstrap/providers.php`:
```php
return [
    // ... các providers hiện có
    GP247\Front\FrontServiceProvider::class,
];
```

4. Chạy lệnh cài đặt:
```bash
php artisan gp247:front-install
```

## Tính năng chính

### Quản lý Trang
- Tạo và quản lý trang tĩnh
- Hỗ trợ SEO cho từng trang
- Kiểm soát truy cập

### Giao diện
- Hệ thống Mẫu Linh hoạt
- Bố cục tùy chỉnh cho từng phần
- Thiết kế tương thích
- Tùy chỉnh giao diện quản trị:
  ```bash
  php artisan vendor:publish --tag=gp247:view-front-admin
  ```
  Các view sẽ được lưu trữ tại: `resources/views/vendor/gp247-front`
- Cập nhật view của template Default:
  ```bash
  php artisan vendor:publish --tag=gp247:view-front-template
  ```
  Các view sẽ được lưu trữ tại: `app/GP247/Templates/Default`

### Mở rộng
- Hỗ trợ Plugin
- Tích hợp module tùy chỉnh
- API để phát triển tính năng

## Tài liệu
Để xem tài liệu chi tiết, truy cập [tài liệu](https://gp247.net/vi/docs)

## Giấy phép
GP247/Front là phần mềm mã nguồn mở được cấp phép theo [giấy phép MIT](https://opensource.org/licenses/MIT). 