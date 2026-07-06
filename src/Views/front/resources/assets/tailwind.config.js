/*
 * Tailwind config for the GP247Front template — SELF-CONTAINED to this
 * template's own Blade/JS (same isolation pattern as the admin-shell
 * precedent, see vendor/gp247/core/src/AdminShell/resources/assets/README.md
 * and ADR-014 decision 1). Content globs are relative to the CWD: the
 * documented build command is run from the host Laravel project root.
 *
 * Design tokens below are ported from ecommerce-template/index.html's
 * `@theme` block (Tailwind v4 browser build) into v3 `theme.extend` form —
 * this project's installed tailwindcss is v3 (see root package.json).
 */

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        'app/GP247/Templates/GP247Front/**/*.blade.php',
        'app/GP247/Templates/GP247Front/resources/assets/js/**/*.js',
        // Pagination is rendered through these vendor views (Livewire's `WithPagination`
        // trait for the reactive product grid, Laravel's own default for the plain
        // `->links()` calls elsewhere e.g. common/pagination.blade.php) — neither lives
        // under this template's own directory, so without scanning them directly their
        // `sm:` responsive classes (mobile vs desktop pagination layout) never get
        // generated, and both layouts render at once (broken pagination bug, 2026-07-04).
        'vendor/livewire/livewire/src/Features/SupportPagination/views/*.blade.php',
        'vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    50: '#f0f9ff',
                    100: '#e0f2fe',
                    200: '#bae6fd',
                    300: '#7dd3fc',
                    400: '#38bdf8',
                    500: '#0ea5e9',
                    600: '#0284c7',
                    700: '#0369a1',
                    800: '#075985',
                    900: '#0c4a6e',
                },
                accent: {
                    50: '#ecfeff',
                    500: '#06b6d4',
                    600: '#0891b2',
                    700: '#0e7490',
                },
                ink: {
                    50: '#f8fafc',
                    100: '#f1f5f9',
                    200: '#e2e8f0',
                    300: '#cbd5e1',
                    400: '#94a3b8',
                    500: '#64748b',
                    600: '#475569',
                    700: '#334155',
                    800: '#1e293b',
                    900: '#0f172a',
                },
            },
            fontFamily: {
                sans: ['Inter', 'Segoe UI', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                arabic: ['Cairo', 'Segoe UI', 'Tahoma', 'sans-serif'],
            },
            boxShadow: {
                soft: '0 4px 24px -8px rgb(0 0 0 / 0.08)',
            },
            borderRadius: {
                xl: '1rem',
            },
        },
    },
    plugins: [],
};
