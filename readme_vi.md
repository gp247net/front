<p align="center">
    <img src="https://static.gp247.net/logo/logo.png" width="150">
</p>
<p align="center">Process front for gp247<br>
    <code><b>composer require gp247/front</b></code></p>

<p align="center">
<a href="https://packagist.org/packages/gp247/front"><img src="https://poser.pugx.org/gp247/front/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/gp247/front"><img src="https://poser.pugx.org/gp247/front/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/gp247/front"><img src="https://poser.pugx.org/gp247/front/license.svg" alt="License"></a>
</p>

[<img src="https://gp247.net/GP247/Core/language/flag_uk.png" width="24"> English](readme.md)

## Giới thiệu

GP247/Front là một gói CMS (Content Management System) toàn diện cho doanh nghiệp, cung cấp các tính năng:

- Quản lý nội dung trang (Pages)
- Quản lý giao diện linh hoạt với hệ thống Template
- Hệ thống Plugin mở rộng
- Quản lý liên kết và điều hướng
- Tích hợp form liên hệ và đăng ký

## Cài đặt

1. Cài đặt qua Composer:
```bash
composer require gp247/front
```

2. Đảm bảo nội dung trong file `routes/web.php` đã được xóa hoặc comment:
```php
// Route::get('/', function () {
//     return view('welcome');
// });
```

## Tính năng chính

### Quản lý trang
- Tạo và quản lý các trang tĩnh
- Hỗ trợ SEO cho từng trang
- Phân quyền truy cập

### Giao diện
- Hệ thống Template linh hoạt
- Tùy chỉnh layout theo từng mục
- Responsive design

### Mở rộng
- Hỗ trợ cài đặt Plugin
- Tích hợp các module tùy chỉnh
- API để phát triển thêm tính năng

## Tài liệu
Xem thêm tài liệu chi tiết tại [documentation](https://gp247.net/vi/docs)

## License
The GP247/Front is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT). 