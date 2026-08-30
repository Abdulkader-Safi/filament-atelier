<?php

declare(strict_types=1);

return [

    /*
    | Locales the builder edits. The FIRST ONE IS THE DEFAULT: it lives at
    | /{slug} with no prefix, the editor opens on it, and a missing translation
    | falls back to it. The rest live at /{locale}/{slug}.
    |
    | Put Arabic first and Arabic becomes the default, with English at
    | /en/{slug}. Nothing else needs changing.
    |
    | Decide early, both the set and the order. Adding or removing a locale
    | means migrating the per-locale maps inside every block tree. Reordering
    | needs no data migration, but it changes every URL on the site and writes
    | no redirects, because the slugs themselves never changed.
    */
    'locales' => [
        'en' => ['label' => 'English', 'dir' => 'ltr'],
        'ar' => ['label' => 'العربية', 'dir' => 'rtl'],
    ],

    'menus' => [
        'primary' => ['label' => 'Primary'],
        'footer' => ['label' => 'Footer'],
        'sidebar' => ['label' => 'Sidebar'],
    ],

    /*
    | Blade layout wrapping the rendered blocks. The preview and the public
    | page both use it, which is what keeps the preview honest.
    */
    'layout' => 'atelier::layouts.site',

    'preview' => [
        // Milliseconds after the user stops typing before the iframe refreshes.
        'debounce' => 500,

        // How long a shareable preview link stays valid.
        'link_expiry_minutes' => 60 * 24,

        // Iframe widths for the toolbar switcher.
        'widths' => [
            'desktop' => null,
            'tablet' => 820,
            'mobile' => 390,
        ],
    ],

    /*
    | Design tokens. The shipped palette, type, spacing and widths live in
    | Safi\Atelier\Tokens::defaults(); anything set here overrides that group
    | key by key, so you can change one colour without restating the rest.
    | They are emitted as CSS custom properties into both the public page and
    | the editor preview, which is what keeps the preview honest.
    |
    | 'tokens' => [
    |     'color' => ['primary' => '#0f766e'],
    |     'font'  => ['arabic' => '"IBM Plex Sans Arabic", sans-serif'],
    | ],
    */
    'tokens' => [],

    'media' => [
        // Where uploaded images land. The disk must be public, or the
        // preview and the live page render broken images.
        'disk' => env('ATELIER_MEDIA_DISK', 'public'),
        'directory' => 'atelier',
    ],

    /*
    | robots.txt. Atelier serves one at /robots.txt pointing at the sitemap,
    | but only if the app has no `public/robots.txt`, because a real file is
    | served before any route runs. Laravel ships one, so delete it or copy
    | the Sitemap line across.
    */
    'robots' => [
        // Also disallow the panel. Set to null to leave it crawlable.
        'disallow_panel' => '/admin',
    ],

    'revisions' => [
        // Snapshots kept per page. Oldest are pruned on publish.
        'keep' => 20,
    ],

];
