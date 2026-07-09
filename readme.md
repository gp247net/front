<p align="center">
    <img src="https://static.gp247.net/logo/logo.png" width="150">
</p>
<p align="center">Front-end package for GP247<br>
    <code><b>composer require gp247/front</b></code></p>

<p align="center">
<a href="https://packagist.org/packages/gp247/front"><img src="https://poser.pugx.org/gp247/front/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/gp247/front"><img src="https://poser.pugx.org/gp247/front/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/gp247/front"><img src="https://poser.pugx.org/gp247/front/license.svg" alt="License"></a>
<a href="https://deepwiki.com/gp247net/front"><img src="https://deepwiki.com/badge.svg" alt="Ask DeepWiki"></a>
</p>


## Introduction

GP247/Front is a comprehensive CMS (Content Management System) package for businesses, providing features:

- Page Content Management
- Flexible Template System
- Extensible Plugin System  
- Navigation & Link Management
- Integrated Contact & Subscription Forms

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
