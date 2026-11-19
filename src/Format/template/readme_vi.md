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

Đây là format chuẩn cho việc phát triển template trong hệ thống GP247.

## Cấu trúc thư mục

```
template/
├── blocks/         # Các khối giao diện (partials) dùng trong template
├── Lang/           # Các file ngôn ngữ
├── Plugins/        # Phần mở rộng đi kèm template (tùy chọn)
├── public/         # Tài nguyên public (css, js, images). Khi cài đặt sẽ được copy tới public/GP247/Templates/{template}
├── AppConfig.php   # Cấu hình chính của template
├── config.php      # Cấu hình hiển thị/tuỳ chọn của template
├── function.php    # Các hàm helper dùng trong template
├── gp247.json      # Khai báo thông tin template
├── Provider.php    # Service provider của template
└── Route.php       # Khai báo routes của template
```

## Mô tả các thành phần

### 1. gp247.json
Khai báo thông tin cơ bản của template:
- name: Tên template
- image: Logo template
- auth: Tác giả
- configGroup: Nhóm cấu hình (đối với Template: "Templates")
- configCode: Mã cấu hình
- configKey: Khóa cấu hình (duy nhất, trùng tên thư mục template)
- version: Phiên bản
- requireCore: Các phiên bản gp247/core tương thích, dạng `MAJOR.MINOR` (ví dụ `["3.15"]`)
- minUpdateFrom: Bản đã cài cũ nhất được cập nhật thẳng lên bản này
- requireComposerPackages: Các gói Composer yêu cầu (mặc định `gp247/front`). Đổi tên từ `requirePackages` ở gp247/core 2.1 (khóa cũ core vẫn đọc nhưng đã deprecated).
- requireGp247Extensions: Tên các extension GP247 (plugin, template) yêu cầu. Đổi tên từ `requireExtensions` ở gp247/core 2.1 (khóa cũ core vẫn đọc nhưng đã deprecated).

### 2. AppConfig.php
Chứa các phương thức vòng đời của template:
- install(), uninstall(), enable(), disable()
- setupStore(), removeStore()
- clickApp(), getInfo()

### 3. Provider.php
Đăng ký service, event, middleware cần thiết cho template.

### 4. Route.php
Khai báo các route frontend của template.

### 5. blocks/
Chứa các thành phần giao diện dùng chung (ví dụ header, footer, breadcrumb, danh sách sản phẩm...).

### 6. Plugins/
Nơi đặt các phần mở rộng đi kèm template (không bắt buộc). Mỗi plugin có cấu trúc riêng theo chuẩn extension của GP247.

### 7. public/
Chứa tài nguyên tĩnh của template (css, js, images). Khi cài đặt sẽ được publish sang `public/GP247/Templates/{template}`.

## View template bắt buộc phải có (scaffold không tạo sẵn)

`gp247/front` và `gp247/shop` lấy các view sau từ template **đang dùng**. Template tự viết không bao giờ dùng lại file
của `GP247Front`, nên thiếu view nào thì trang đó dừng với lỗi "view not found".

| View | Vì sao |
|---|---|
| `layout.blade.php` (+ các phần trong `layout/` mà nó include) | Mọi trang, kể cả mọi trang shop, đều kế thừa nó |
| `screen/home`, `screen/page_detail`, `screen/front_search`, `screen/notfound` | `gp247/front` hiển thị (`notfound` trả HTTP 404) |
| `common/item_single`, `common/pagination`, `common/pagination_result`, `common/jsonld_product`, `common/jsonld_breadcrumb`, `common/render_form_custom_field` | Trang tìm kiếm và các trang shop include |

Cách bắt đầu nhanh và đúng: chép các file này từ `vendor/gp247/front/src/Views/templates/GP247Front/` rồi sửa giao diện.
Skill `gp247-template-create` (https://github.com/gp247net/gp247-skills) dựng sẵn chúng cho bạn.

## Hợp đồng layout: SEO, mã đo lường và banner cookie

- **`<head>` gọi đúng một** `<x-gp247-front::meta-head :title :description :keyword :og_image :canonical :hreflang="$seoHreflang ?? []" />`.
  Component này in title, description, canonical, robots, Open Graph, Twitter card, hreflang, JSON-LD Organization, mã
  xác minh site, **và** mã đo lường của chủ site (GA4, GTM, Meta Pixel, mã tự do) cùng banner cookie. Không tự viết các
  thẻ này: layout không có `meta-head` thì không có thẻ SEO, không có mã đo lường, không có banner.
- **`@stack('jsonld')` nằm trong `<head>`**, bọc bởi `@if(\GP247\Front\Library\SeoMeta::jsonldEnabled())`. Các trang đẩy dữ liệu
  có cấu trúc vào đây qua các partial `common/jsonld_*`; không tự `json_encode` JSON-LD. Mỗi file `common/jsonld_*` chỉ
  một dòng chuyển sang partial của package, ví dụ `@include('GP247TemplatePath::GP247Front.common.jsonld_product')`.
- **`{!! gp247_render_block('header', $layout_page ?? null) !!}` nằm trong `<head>`**, cùng mọi vị trí khối layout khác
  (`top`, `left`, `center`, `right`, `bottom`, `footer`) trong body.
- **`<html lang="{{ app()->getLocale() }}" dir="...">`** (`rtl` cho ngôn ngữ viết từ phải sang trái).
- **Link "Cài đặt cookie" (nên có)**: một phần tử có `data-gp247-consent-open`, thuộc tính `hidden` và chữ
  `gp247_language_render('front.consent.settings')` ở chân trang, bọc bởi
  `@if (class_exists(\GP247\Front\TemplateComponents\ConsentBanner::class))`. Bộ nạp banner cho hiện link khi banner bật;
  không có link thì bộ nạp tự thêm một nút nhỏ nổi ở góc.
- **In chữ người dùng nhập bằng `{{ }}`** (đã escape), không dùng `{!! !!}` trừ HTML do hệ thống dựng.
- **Không đóng gói ảnh chia sẻ mặc định**: ảnh của trang, nếu không có thì `og_image` của cửa hàng, nếu không nữa thì bỏ thẻ `og:image`.

## Cách sử dụng

1. Khởi tạo:
   - Đổi tên thư mục theo tên template (trùng `configKey`)
   - Cập nhật thông tin trong `gp247.json`

2. Phát triển:
   - Khai báo route trong `Route.php`
   - Xây dựng giao diện trong `blocks/` và các thư mục con theo nhu cầu
   - Viết helper trong `function.php`
   - Thêm ngôn ngữ trong `Lang/`
   - Thêm tài nguyên vào `public/`

3. Cài đặt:
   - Tham khảo hướng dẫn chi tiết: [English guide](https://gp247.net/en/docs/user-guide-extension/guide-to-installing-the-extension.html) | [Tiếng Việt](https://gp247.net/vi/docs/user-guide-extension/guide-to-installing-the-extension.html)

## Lưu ý

- Sử dụng namespace đúng chuẩn
- Đảm bảo đa ngôn ngữ
- Kiểm tra các dependency trước khi cài đặt
- Xử lý lỗi và rollback khi cần thiết
- Đảm bảo responsive design và hiệu năng tải trang

---

