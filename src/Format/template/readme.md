# Tạo Template mới

Để tạo một template mới, sử dụng lệnh artisan sau:

```bash
php artisan gp247:make-template --name=YourTemplateName --download=0
```

Trong đó:
- `YourTemplateName`: Tên template của bạn
- `--download=0`: Tạo template trực tiếp trong thư mục app/GP247/Templates
- `--download=1`: Tạo file zip template trong thư mục storage/tmp

# Cấu trúc Template GP247

Đây là template chuẩn cho việc phát triển template trong hệ thống GP247. Template được thiết kế theo mô hình MVC (Model-View-Controller) và tuân thủ các quy tắc của Laravel framework.

## Cấu trúc thư mục

```
template/
├── Admin/           # Chứa các file liên quan đến quản trị
├── Controllers/     # Chứa các controller xử lý logic
├── Lang/           # Chứa các file ngôn ngữ
├── Models/         # Chứa các model
├── public/         # Chứa các file public (css, js, images). Khi cài đặt, sẽ được copy tới public/GP247/Templates/Your-template
├── Views/          # Chứa các file view
├── AppConfig.php   # File cấu hình chính của template
├── config.php      # File cấu hình
├── function.php    # Chứa các hàm helper
├── gp247.json      # File khai báo thông tin template
├── Provider.php    # Service provider của template
├── Route.php       # Định nghĩa routes
└── route_front.stub # Template cho route frontend
```

## Các file chính

### 1. gp247.json
File khai báo thông tin cơ bản của template:
- name: Tên template
- image: Logo template
- auth: Tác giả
- configGroup: Nhóm cấu hình
- configCode: Mã cấu hình
- configKey: Khóa cấu hình, là giá trị duy nhất, trùng vói tên folder Template
- version: Phiên bản
- requireCore: Là phiên bản Gp247/Core phù hợp với template
- requirePackages: Các package (từ packagist.org) được yêu cầu cài đặt. Mặc định yêu cầu `gp247/front`
- requireExtensions: Tên các extension của GP247 (plugin, template) được yêu cầu cài đặt

### 2. AppConfig.php
File cấu hình chính của template, chứa các phương thức:
- install(): Cài đặt template
- uninstall(): Gỡ cài đặt template
- enable(): Kích hoạt template
- disable(): Vô hiệu hóa template
- setupStore(): Thiết lập cho store
- removeStore(): Xóa thiết lập store
- clickApp(): Xử lý khi click vào template trong admin
- getInfo(): Lấy thông tin template

### 3. Provider.php
Service provider của template, đăng ký các service và middleware.

### 4. Route.php
Định nghĩa các route cho template.

## Cách sử dụng

1. Tạo template mới:
   - Đổi tên thư mục theo tên template (trùng giá trị configKey)
   - Cập nhật thông tin trong gp247.json

2. Phát triển:
   - Thêm logic vào Controllers
   - Tạo model trong Models
   - Tạo view trong Views
   - Thêm ngôn ngữ trong Lang
   - Thêm assets trong public

3. Cài đặt:
   - Vui lòng tham khảo hướng dẫn cài đặt chi tiết tại: https://gp247.net/en/user-guide-extension/guide-to-installing-the-extension.html

## Lưu ý

- Tuân thủ cấu trúc MVC
- Sử dụng namespace đúng chuẩn
- Đảm bảo đa ngôn ngữ
- Kiểm tra các dependency trước khi cài đặt
- Xử lý lỗi và rollback khi cần thiết
- Đảm bảo responsive design cho template
- Tối ưu hóa hiệu suất và tốc độ tải trang

---

# Create New Template

To create a new template, use the following artisan command:

```bash
php artisan gp247:make-template --name=YourTemplateName --download=0
```

Where:
- `YourTemplateName`: Your template name
- `--download=0`: Create template directly in app/GP247/Templates directory
- `--download=1`: Create template zip file in storage/tmp directory

# GP247 Template Structure

This is the standard template for developing templates in the GP247 system. The template is designed following the MVC (Model-View-Controller) pattern and adheres to Laravel framework rules.

## Directory Structure

```
template/
├── Admin/           # Contains admin-related files
├── Controllers/     # Contains logic handling controllers
├── Lang/           # Contains language files
├── Models/         # Contains models
├── public/         # Contains public files (css, js, images). When installed, will be copied to public/GP247/Templates/Your-template
├── Views/          # Contains view files
├── AppConfig.php   # Main template configuration file
├── config.php      # Configuration file
├── function.php    # Contains helper functions
├── gp247.json      # Template information declaration file
├── Provider.php    # Template service provider
├── Route.php       # Route definitions
└── route_front.stub # Frontend route template
```

## Key Files

### 1. gp247.json
File declaring basic template information:
- name: Template name
- image: Template logo
- auth: Author
- configGroup: Configuration group
- configCode: Configuration code
- configKey: Configuration key, must be unique and match the template folder name
- version: Version
- requireCore: Compatible Gp247/Core version
- requirePackages: Required packages from packagist.org. Default requires `gp247/front`
- requireExtensions: Required GP247 extensions (plugins, templates)

### 2. AppConfig.php
Main template configuration file, contains methods:
- install(): Install template
- uninstall(): Uninstall template
- enable(): Enable template
- disable(): Disable template
- setupStore(): Setup for store
- removeStore(): Remove store setup
- clickApp(): Handle when clicking template in admin
- getInfo(): Get template information

### 3. Provider.php
Template service provider, registers services and middleware.

### 4. Route.php
Defines template routes.

## Usage

1. Create new template:
   - Rename directory to match template name (must match configKey value)
   - Update information in gp247.json

2. Development:
   - Add logic to Controllers
   - Create models in Models
   - Create views in Views
   - Add languages in Lang
   - Add assets in public

3. Installation:
   - Please refer to detailed installation guide at: https://gp247.net/en/user-guide-extension/guide-to-installing-the-extension.html

## Notes

- Follow MVC structure
- Use correct namespace
- Ensure multilingual support
- Check dependencies before installation
- Handle errors and rollback when necessary
- Ensure responsive design
- Optimize performance and page load speed 