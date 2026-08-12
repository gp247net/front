```
  _____  _____     ___  _  _   _____ 
 / ____|  __ \   |__ \| || | |___  |
| |  __| |__) |     ) | || |_   / / 
| | |_ |  ___/     / /|__   _| / /  
| |__| | |        / /_   | |  / /   
 \_____|_|       |____|  |_| /_/    
```

> 🌐 **Language:** 🇬🇧 English (current) · [🇻🇳 Tiếng Việt](readme_vi.md)

Frontend foundation & CMS package for GP247

`composer require gp247/front`

[![Total Downloads](https://poser.pugx.org/gp247/front/d/total.svg)](https://packagist.org/packages/gp247/front)
[![Latest Stable Version](https://poser.pugx.org/gp247/front/v/stable.svg)](https://packagist.org/packages/gp247/front)
[![License](https://poser.pugx.org/gp247/front/license.svg)](https://packagist.org/packages/gp247/front)
[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/gp247net/front)


## Introduction

GP247/Front is the **foundation package for GP247's entire frontend tier** — not a mere presentation/theme layer. It provides both the **public-facing storefront** and a **content management system (CMS)**, and serves as the **shared infrastructure** that other frontend packages (such as `gp247/shop`) build on. It is a **required** component of a full GP247 site, playing the role for the frontend tier that `gp247/core` plays for the admin tier.

Key features:

- Page content management (Page / CMS)
- Flexible template system + active-template view resolution
- Extensible plugin system
- Navigation, menu, link & banner management
- Integrated contact & subscription forms
- Shared frontend infrastructure (base Livewire components, layout, routes) for other packages to plug into

## Installation

1. Install package

    >`composer require gp247/front`


2. Configure routing and exceptions for GP247, open `bootstrap/app.php`:

  - Comment out the web routes line:

    ```php
    //GP247 comment
    //web: __DIR__.'/../routes/web.php',
    ```


3. Register the service provider in `bootstrap/providers.php`:
    ```php
    return [
        // ... existing providers
        GP247\Front\FrontServiceProvider::class,
    ];
    ```

4. Run the installation command:
>`php artisan gp247:front-install`

## Key Features

### Page Management
- Create and manage static pages
- SEO support for each page
- Access control

### Interface
- Flexible Template System
- Customizable layouts for each section
- Responsive design

Admin interface customization:
>`php artisan vendor:publish --tag=gp247:front-admin`

Views will be stored at: `resources/views/vendor/gp247-front-admin`

Update the default template (GP247Front) views:

>`php artisan vendor:publish --tag=gp247:front-view`

Views will be stored at: `app/GP247/Templates/GP247Front`

Publish the default template's public assets (css/js/images):

>`php artisan vendor:publish --tag=gp247:front-public`

Assets will be stored at: `public/GP247/Templates/GP247Front`

### Extensions
- Plugin support
- Custom module integration
- API for feature development

### Overriding controllers

>Step 1: Copy the controller files you want to override from `vendor/gp247/front/src/Controllers` (or `src/Api/Controllers`) to `app/GP247/Front/Controllers` (or `app/GP247/Front/Api/Controllers`)

>Step 2: Change the namespace from `GP247\Front\Controllers` (or `GP247\Front\Api\Controllers`) to `App\GP247\Front\Controllers` (or `App\GP247\Front\Api\Controllers`) — just prepend `App`, keep the rest as-is.

## Documentation
For detailed documentation, visit [documentation](https://gp247.net/en/docs)

## License
The GP247/Front is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
