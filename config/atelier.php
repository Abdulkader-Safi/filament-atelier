<?php

declare(strict_types=1);

return [

    /*
    | Locales the builder edits. The first is the default and lives at /{slug};
    | the rest live at /{locale}/{slug}. Changing this after pages exist means
    | migrating the per-locale maps inside every block tree, so decide early.
    */
    'locales' => [
        'en' => ['label' => 'English', 'dir' => 'ltr'],
        'ar' => ['label' => 'العربية', 'dir' => 'rtl'],
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

    'media' => [
        // Where uploaded images land. The disk must be public, or the
        // preview and the live page render broken images.
        'disk' => env('ATELIER_MEDIA_DISK', 'public'),
        'directory' => 'atelier',
    ],

    'revisions' => [
        // Snapshots kept per page. Oldest are pruned on publish.
        'keep' => 20,
    ],

];
