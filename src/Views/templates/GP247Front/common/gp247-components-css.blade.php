<style>
:root {
    --gp247-color-text: #333333;
    --gp247-color-link: #333333;
    --gp247-color-link-hover: #000000;
    --gp247-color-border: #e5e5e5;
    --gp247-color-bg-menu: #ffffff;
    --gp247-color-active: #0d6efd;
    --gp247-font-size: 14px;
    --gp247-spacing: 8px;
}

/* language-switcher */
.gp247-language-switcher {
    position: relative;
    list-style: none;
}
.gp247-language-switcher__toggle {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: var(--gp247-color-link);
    text-decoration: none;
    cursor: pointer;
}
.gp247-language-switcher__menu {
    position: absolute;
    top: 100%;
    right: 0;
    z-index: 100;
    min-width: 140px;
    margin: 8px 0 0;
    padding: 4px 0;
    list-style: none;
    background: var(--gp247-color-bg-menu);
    border: 1px solid var(--gp247-color-border);
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}
.gp247-language-switcher__item--active {
    font-weight: 600;
}
.gp247-language-switcher__link {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    color: var(--gp247-color-link);
    text-decoration: none;
    white-space: nowrap;
}
.gp247-language-switcher__link:hover {
    background: rgba(0, 0, 0, 0.04);
    color: var(--gp247-color-link-hover);
}

/* currency-switcher — same interaction pattern as language-switcher */
.gp247-currency-switcher {
    position: relative;
    list-style: none;
}
.gp247-currency-switcher__toggle {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    /* inherit (not --gp247-color-link, a fixed dark grey) — this toggle sits
       on whatever background its caller uses (e.g. the dark topbar in
       layout/block_menu.blade.php), and a hardcoded dark color is invisible
       there; inherit keeps it readable against any parent text color */
    color: inherit;
    text-decoration: none;
    cursor: pointer;
    opacity: 0.85;
    transition: opacity 0.15s ease;
}
.gp247-currency-switcher__toggle:hover,
.gp247-currency-switcher:focus-within .gp247-currency-switcher__toggle {
    opacity: 1;
}
/* Package view ships a FontAwesome <i class="fas fa-caret-down">, but this
   template doesn't load FontAwesome — draw the dropdown indicator with a
   plain-text glyph instead so it isn't just blank */
.gp247-currency-switcher__toggle i.fa-caret-down {
    font-style: normal;
}
.gp247-currency-switcher__toggle i.fa-caret-down::before {
    content: "\25BE";
}
.gp247-currency-switcher__menu {
    position: absolute;
    top: 100%;
    right: 0;
    z-index: 100;
    min-width: 120px;
    margin: 8px 0 0;
    padding: 4px 0;
    list-style: none;
    color: var(--gp247-color-text);
    background: var(--gp247-color-bg-menu);
    border: 1px solid var(--gp247-color-border);
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}
.gp247-currency-switcher__item--active {
    font-weight: 600;
}
.gp247-currency-switcher__link {
    display: block;
    padding: 6px 12px;
    color: var(--gp247-color-link);
    text-decoration: none;
    white-space: nowrap;
}
.gp247-currency-switcher__link:hover {
    background: rgba(0, 0, 0, 0.04);
    color: var(--gp247-color-link-hover);
}

/* footer-links */
.gp247-footer-links {
    display: flex;
    flex-wrap: wrap;
    gap: var(--gp247-spacing);
    margin: 0;
    padding: 0;
    list-style: none;
}
.gp247-footer-links__link {
    color: var(--gp247-color-link);
    text-decoration: none;
}
.gp247-footer-links__link:hover {
    color: var(--gp247-color-link-hover);
    text-decoration: underline;
}
.gp247-footer-links__item--group {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 4px;
}
.gp247-footer-links__label {
    color: var(--gp247-color-text);
    font-weight: 600;
}
.gp247-footer-links__link--child {
    font-size: 0.92em;
    opacity: 0.85;
}

/* breadcrumb */
.gp247-breadcrumb__path {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: var(--gp247-font-size);
}
.gp247-breadcrumb__item {
    display: flex;
    align-items: center;
    color: var(--gp247-color-text);
}
.gp247-breadcrumb__item:not(:last-child)::after {
    content: "/";
    margin: 0 8px;
    color: var(--gp247-color-border);
}
.gp247-breadcrumb__item--active {
    color: var(--gp247-color-active);
    font-weight: 600;
}
.gp247-breadcrumb__link {
    color: var(--gp247-color-link);
    text-decoration: none;
}
.gp247-breadcrumb__link:hover {
    text-decoration: underline;
}
</style>
