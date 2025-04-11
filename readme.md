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


## Introduction

GP247/Front is a comprehensive CMS (Content Management System) package for businesses, providing features:

- Page Content Management
- Flexible Template System
- Extensible Plugin System  
- Navigation & Link Management
- Integrated Contact & Subscription Forms

## Installation

1. Install via Composer:
```bash
composer require gp247/front
```

2. Ensure the content in `routes/web.php` is removed or commented out:
```php
// Route::get('/', function () {
//     return view('welcome');
// });
```

3. Register the service provider in `bootstrap/providers.php`:
```php
return [
    // ... existing providers
    GP247\Front\FrontServiceProvider::class,
];
```

4. Run the installation command:
```bash
php artisan gp247:front-install
```

## Key Features

### Page Management
- Create and manage static pages
- SEO support for each page
- Access control

### Interface
- Flexible Template System
- Customizable layouts for each section
- Responsive design
- Admin interface customization:
  ```bash
  php artisan vendor:publish --tag=gp247:view-front-admin
  ```
  Views will be stored at: `resources/views/vendor/gp247-front`
- Update Default template views:
  ```bash
  php artisan vendor:publish --tag=gp247:view-front-template
  ```
  Views will be stored at: `app/GP247/Templates/Default`

### Extensions
- Plugin support
- Custom module integration
- API for feature development

## Documentation
For detailed documentation, visit [documentation](https://gp247.net/en/docs)

## License
The GP247/Front is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
