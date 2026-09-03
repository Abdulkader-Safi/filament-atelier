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

    /*
    | Named menu locations, the same idea as `locales` above: a fixed set a
    | developer decides, not something a client adds from the panel. What
    | the client edits is a location's items, in the Menus page, not the set
    | of locations itself.
    |
    | `depth` is how many levels of children a location's items may nest.
    | Default 1 (one level) when left out. The editor and the public partial
    | are only built for one level regardless of a higher number here.
    |
    | AtelierPlugin::make()->menuLocations([...]) in a panel provider adds to
    | this rather than replacing it, for a location that only makes sense
    | inside one specific panel.
    */
    'menus' => [
        // 'primary' => ['label' => 'Primary'],
        // 'footer' => ['label' => 'Footer', 'depth' => 2],
    ],

    /*
    | Blade layout wrapping the rendered blocks. The preview and the public
    | page both use it, which is what keeps the preview honest.
    */
    'layout' => 'atelier::layouts.site',

    'preview' => [
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

    /*
    | Feature flags for anything still being proven out. Off by default: a
    | flag stays off until you turn it on, here or with
    | AtelierPlugin::make()->experimental([...]) in a panel provider, which
    | overrides this rather than only adding to it.
    |
    | 'menus' is the menu manager: a real page in the panel, in a client's
    | sidebar the moment it's on, so it's worth trying in your own project
    | before you turn it on somewhere a client will see it.
    */
    'experimental' => [
        'menus' => false,
    ],

];
