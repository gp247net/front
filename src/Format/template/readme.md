# Create a new Template

To create a new template, use the following artisan command:

```bash
php artisan gp247:make-template --name=YourTemplateName --download=0
```

Where:
- `YourTemplateName`: Your template name
- `--download=0`: Create the template directly under `app/GP247/Templates`
- `--download=1`: Create a template zip file under `storage/tmp`

# GP247 Template Structure

This is the standard format for developing templates in the GP247 system.

## Directory structure

```
template/
├── blocks/         # Reusable UI blocks (partials) used by the template
├── Lang/           # Language files
├── Plugins/        # Optional extensions bundled with the template
├── public/         # Public assets (css, js, images). During install, they are copied to public/GP247/Templates/{template}
├── AppConfig.php   # Main configuration of the template
├── config.php      # Display/options configuration of the template
├── function.php    # Helper functions used in the template
├── gp247.json      # Template metadata declaration
├── Provider.php    # Service provider of the template
└── Route.php       # Template route declarations
```

## Component descriptions

### 1. gp247.json
Declare the basic information of the template:
- name: Template name
- image: Template logo
- auth: Author
- configGroup: Configuration group (for Template: "Templates")
- configCode: Configuration code
- configKey: Configuration key (unique, matches the template folder name)
- version: Version
- requireCore: Compatible gp247/core versions as `MAJOR.MINOR` (for example `["3.15"]`)
- minUpdateFrom: Oldest installed version that can update straight to this one
- requireComposerPackages: Required Composer packages (default `gp247/front`). Renamed from `requirePackages` in gp247/core 2.1 (old key still read by core but deprecated).
- requireGp247Extensions: Names of required GP247 extensions (plugins, templates). Renamed from `requireExtensions` in gp247/core 2.1 (old key still read by core but deprecated).

### 2. AppConfig.php
Contains the template lifecycle methods:
- install(), uninstall(), enable(), disable()
- setupStore(), removeStore()
- clickApp(), getInfo()

### 3. Provider.php
Registers the necessary services, events, and middleware for the template.

### 4. Route.php
Declares the template's frontend routes.

### 5. blocks/
Contains shared UI components (e.g., header, footer, breadcrumb, product lists, ...).

### 6. Plugins/
Place for optional extensions shipped with the template (not required). Each plugin follows the GP247 extension standard.

### 7. public/
Contains the template's static assets (css, js, images). Upon installation, they are published to `public/GP247/Templates/{template}`.

## Views a template must provide (the scaffold does not create them)

`gp247/front` and `gp247/shop` render these views from the **active** template. A custom template never falls back to
the files of `GP247Front`, so a missing view stops the page with "view not found".

| View | Why |
|---|---|
| `layout.blade.php` (+ the `layout/` parts it includes) | Every page, including every shop page, extends it |
| `screen/home`, `screen/page_detail`, `screen/front_search`, `screen/notfound` | Rendered by `gp247/front` (`notfound` is sent with HTTP 404) |
| `common/item_single`, `common/pagination`, `common/pagination_result`, `common/jsonld_product`, `common/jsonld_breadcrumb`, `common/render_form_custom_field` | Included by search and by the shop pages |

The quickest correct start is to copy these files from `vendor/gp247/front/src/Views/templates/GP247Front/` and
restyle them. The `gp247-template-create` skill (https://github.com/gp247-official/gp247-skills) builds them for you.

## Layout contract: SEO, tracking code and cookie banner

- **`<head>` calls exactly one** `<x-gp247-front::meta-head :title :description :keyword :og_image :canonical :hreflang="$seoHreflang ?? []" />`.
  It prints title, description, canonical, robots, Open Graph, Twitter card, hreflang, the Organization JSON-LD, the
  site verification codes, **and** the site owner's tracking code (GA4, GTM, Meta Pixel, custom code) with the cookie
  banner. Do not write these tags yourself: a layout without `meta-head` has no SEO tags, no tracking code and no banner.
- **`@stack('jsonld')` inside `<head>`**, wrapped in `@if(\GP247\Front\Library\SeoMeta::jsonldEnabled())`. Pages push their
  structured data there through the `common/jsonld_*` partials; never `json_encode` JSON-LD by hand. Write each
  `common/jsonld_*` file as one line that hands over to the package partial, e.g.
  `@include('GP247TemplatePath::GP247Front.common.jsonld_product')`.
- **`{!! gp247_render_block('header', $layout_page ?? null) !!}` inside `<head>`**, plus every other layout-block
  position (`top`, `left`, `center`, `right`, `bottom`, `footer`) in the body.
- **`<html lang="{{ app()->getLocale() }}" dir="...">`** (`rtl` for right-to-left languages).
- **"Cookie settings" link (recommended)**: an element with `data-gp247-consent-open`, the `hidden` attribute and the
  text `gp247_language_render('front.consent.settings')` in the footer, wrapped in
  `@if (class_exists(\GP247\Front\TemplateComponents\ConsentBanner::class))`. The consent loader shows it while the
  banner is on; without it the loader adds a small floating button.
- **Print typed text with `{{ }}`** (escaped), never `{!! !!}`, unless it is HTML built by the system.
- **No bundled default share image**: the page image, else the store's `og_image`, else no `og:image` tag.

## How to use

1. Initialize:
   - Rename the folder to your template name (matches `configKey`)
   - Update the information in `gp247.json`

2. Develop:
   - Declare routes in `Route.php`
   - Build UI in `blocks/` and subdirectories as needed
   - Write helpers in `function.php`
   - Add languages in `Lang/`
   - Add assets in `public/`

3. Install:
   - Refer to the detailed guide: [English guide](https://gp247.net/en/docs/user-guide-extension/guide-to-installing-the-extension.html) | [Tiếng Việt](https://gp247.net/vi/docs/user-guide-extension/guide-to-installing-the-extension.html)

## Notes

- Use proper namespaces
- Ensure multi-language support
- Check dependencies before installation
- Handle errors and perform rollback when necessary
- Ensure responsive design and page performance

---