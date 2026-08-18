A visual page builder for Laravel, built as a Filament plugin.

A developer defines the sections in code. The client builds pages from them in a full-screen editor and watches the real page update as they type. The public site stays server-rendered Blade, bilingual, and fast.

A page is stored as a JSON tree of typed blocks and rendered by Blade at request time. A block type is one PHP class plus one Blade view, and the class returns a Filament schema which becomes its settings form. The preview renders the real page through the public layout and the public stylesheet, so what you see is what ships.

## Requirements

- PHP 8.3 or newer
- Laravel 12 or 13
- Filament 5
- Tailwind 4 on the front end

## Installation

```bash
composer require safi/filament-atelier
php artisan vendor:publish --tag=filament-atelier-config
php artisan vendor:publish --tag=filament-atelier-migrations
php artisan migrate
php artisan storage:link
```

`storage:link` is not optional. Uploaded images go to the `public` disk, and without the symlink every image in the builder and on the live site is broken, with no error anywhere to tell you why.

### Register the plugin

In your panel provider:

```php
use Safi\Atelier\AtelierPlugin;
use Safi\Atelier\Blocks\DefaultBlocks;

->plugins([
    AtelierPlugin::make()
        ->blocks(DefaultBlocks::all()),
])
```

`DefaultBlocks::all()` is the set Atelier ships: hero, features, rich text, image, gallery, logo wall, testimonials, FAQ, call to action. Pass your own array to cherry-pick, and add your own classes alongside them.

### Point Tailwind at the block views

**This step fails silently.** Skip it and every block renders unstyled, with nothing in the console or the log to say why. Tailwind scans source files and has no idea your vendor directory exists.

In `resources/css/app.css`:

```css
@source '../../vendor/safi/filament-atelier/resources/views/**/*.blade.php';
```

Then `npm run build`. Blocks you write live in your own app, which Tailwind already scans.

### Decide what owns `/`

Atelier registers a catch-all for `/{slug}` and `/{locale}/{slug}`, registered last so your own routes still win. A fresh Laravel app has a welcome route on `/`; remove it if you want the CMS to own the home page.

### Configure locales

In `config/atelier.php`:

```php
'locales' => [
    'en' => ['label' => 'English', 'dir' => 'ltr'],
    'ar' => ['label' => 'العربية', 'dir' => 'rtl'],
],
```

The first locale is the default and lives at `/{slug}`. Every other locale lives at `/{locale}/{slug}`. Decide this before you create pages: changing it later means migrating the per-locale maps inside every block tree on every page. If the site is English only, delete the Arabic line now.

## Using your own layout

`atelier.layout` points at the Blade view wrapping the rendered blocks, and pointing it at your own is the normal way to give a client site its own shell. Your layout receives `$blocks` (the rendered HTML), `$locale`, `$page`, `$title` and `$preview`.

Include the two partials:

```blade
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Title, description, canonical, hreflang, Open Graph, Twitter, and
         noindex on previews. Emits its own <title>, so don't write one. --}}
    @include('atelier::partials.meta')

    @vite(['resources/css/app.css'])

    {{-- Design tokens. After your stylesheet, so they win. --}}
    @include('atelier::partials.tokens')
</head>
<body>
    <header>Your navigation</header>
    <main>{!! $blocks !!}</main>
    <footer>Your footer</footer>
</body>
</html>
```

**Both failures are silent.** Without `partials.meta` the page renders perfectly and carries no title, description, canonical, hreflang or Open Graph tags, and previews stop being `noindex`. Without `partials.tokens` every `var(--atelier-*)` resolves to nothing, so the background and spacing controls quietly do nothing and Arabic loses its font stack.

## The editor

**Pages** in the panel nav, then a page row, then its settings, then **Edit page content** for the builder.

Settings and content are separate screens on purpose. Slug and SEO are set once; content is what you come back to.

Page settings hold the title and a tab per locale carrying the slug, meta title, meta description, social share image and canonical URL. The header has Edit page content, View live, Publish, Unpublish and Delete.

The builder is full screen, outside the panel chrome:

- The **sidebar** shows the section list, each row labelled by the section's own heading rather than "Block 4", with move, duplicate, hide and delete. Selecting a row swaps the list for that section's fields.
- The **middle** is the live preview, rendering the real page through the public layout and the public stylesheet. Click a section in the preview to select it.
- The **toolbar** carries the status badge, desktop, tablet and mobile widths at fixed sizes, the locale switcher, a signed preview link that expires, and Publish.

Every change writes the draft immediately, so there is no Save button. The live page reads a separate column and cannot change until you press Publish. An unpublished page 404s, so a half-finished page cannot leak. Hiding a section keeps it in the editor and removes it from the public page, which is the reversible alternative to deleting.

Publishing snapshots the tree into `atelier_page_revisions`, pruned to `revisions.keep`. `Page::restoreRevision()` copies one back into the draft. There is no UI for browsing revisions yet.

## Writing a block

One PHP class and one Blade view. Nothing inside the plugin changes.

```php
namespace App\Blocks;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Safi\Atelier\Blocks\BaseBlock;
use Safi\Atelier\Media;

class StatsBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'stats';            // registry key, and the view name
    }

    public static function icon(): string
    {
        return 'heroicon-o-chart-bar';
    }

    public static function category(): string
    {
        return 'Content';          // groups it in the picker
    }

    /** Shared controls this block opts into. */
    public static function supports(): array
    {
        return ['background', 'padding'];
    }

    /** Fields stored per locale. */
    public static function translatable(): array
    {
        return ['heading'];
    }

    /** Starting values when the block is added to a page. */
    public static function defaults(): array
    {
        return ['heading' => ['en' => 'By the numbers']];
    }

    public function schema(): array
    {
        return [
            TextInput::make('heading')->live(debounce: 400),
            Textarea::make('body')->rows(3)->live(debounce: 400),
            Media::upload('image', 'Image')->live(),
        ];
    }
}
```

Then `resources/views/blocks/stats.blade.php` in your app:

```blade
<section {{ $shared->class(['px-6 py-16']) }}>
    <div class="mx-auto max-w-3xl">
        @if ($heading = $attributes['heading'] ?? null)
            <h2 class="text-3xl font-semibold">{{ $heading }}</h2>
        @endif
    </div>
</section>
```

And register it:

```php
AtelierPlugin::make()->blocks([
    ...DefaultBlocks::all(),
    \App\Blocks\StatsBlock::class,
])
```

### Five things that will bite you otherwise

1. **`->live(debounce: 400)` is what makes the preview update as you type.** A field without it only refreshes when focus leaves it. This is the most common "why isn't the preview updating".
2. **Put `{{ $shared }}` on the outer element**, usually as `$shared->class([...])`. It carries the block id that click-to-select needs and the styles from any control the block declared in `supports()`. Write `data-atelier-block="{{ $id }}"` by hand instead and the block still works, it just never gets the shared controls.
3. **`translatable()` fields are stored as `{"en": "...", "ar": "..."}`.** Everything else is shared across locales. Repeaters can be translatable too, and then the whole list is per locale.
4. **Use `Media::upload()` in the schema and `Media::url()` in the view.** Never call `Storage::url()` yourself. `FileUpload` state is not reliably a string: it is an array keyed by uuid while editing and `[]` when empty, and `Media::url()` is where that is handled.
5. **Reference design tokens, not literal colours.** A field storing `{"token": "color.primary"}` is resolved to `var(--atelier-color-primary)` before your view runs, so changing the token changes every page using it.

### What the view receives

| Variable | What it is |
|---|---|
| `$attributes` | The block's fields, collapsed to the current locale and token-resolved |
| `$shared` | Attribute bag for the root element: the block id, plus `supports()` styling |
| `$id` | Stable block id |
| `$locale` | Current locale code |
| `$editing` | True in the preview, false on the public page |
| `$children` | Rendered child blocks, for nesting |

`$editing` is for showing something in the editor that should not ship, like an empty-state placeholder.

## Design tokens

Colour, font, spacing and width are emitted as CSS custom properties into the head of both the public page and the editor preview, from the same layout, so the two cannot drift.

Defaults live in `Safi\Atelier\Tokens` and `atelier.tokens` overrides them key by key, so changing one colour does not mean restating the group:

```php
'tokens' => [
    'color' => ['primary' => '#0f766e'],
    'font' => ['arabic' => '"IBM Plex Sans Arabic", sans-serif'],
],
```

The Arabic font swap rides on a `[dir="rtl"]` rule rather than a locale code, so a third RTL language gets it for free.

## SEO

Each page carries per-locale meta title, meta description, social share image and canonical
URL, rendered into the head with Open Graph and Twitter tags alongside `hreflang` between
locales.

Two toggles per locale control indexing. Marking a locale **noindex** emits the robots tag
and drops that URL from the sitemap, one switch for both. **Nofollow** is independent: a
page can be indexed and still not pass link credit.

A sitemap is served at `/sitemap.xml`, listing every published, indexable page in every
locale with `xhtml:link` alternates and `lastmod` from the publish time. Drafts and
noindexed pages never appear.

`/robots.txt` points at it and disallows the preview route and the panel. **Laravel ships a
real `public/robots.txt`, and a file on disk is served before any route runs**, so delete
that file to use this one, or copy the `Sitemap:` line into yours. Set
`atelier.robots.disallow_panel` to your panel path, or `null` to leave it crawlable.

### Renaming a slug

Changing a published page's slug writes a 301 from the old URL, and the public route
consults those before it 404s. The redirect stores the page rather than a target slug, so a
page renamed twice sends both old URLs to wherever it lives now, with no chain to follow.
An unpublished target 404s instead: sending someone to a 404 is worse than the 404 itself.

## Bilingual pages

Translatable attributes hold a per-locale map inside one tree, so both languages share one section order. A missing translation falls back to the default locale rather than rendering a hole.

Each locale has its own slug row, its own SEO fields and its own URL, with `hreflang` pointing between them and `dir="rtl"` on the Arabic side. The accepted cost of one tree is that Arabic cannot have a different section order from English.

## Configuration

| Key | What it does |
|---|---|
| `locales` | Which languages exist. The first is the default and has no URL prefix. |
| `layout` | The Blade layout wrapping rendered blocks. Both the preview and the public page use it. |
| `tokens` | Design token overrides. Anything omitted falls back to the shipped set. |
| `preview.debounce` | Milliseconds after typing stops before the preview refreshes. |
| `preview.widths` | Pixel widths for the desktop, tablet and mobile switcher. |
| `preview.link_expiry_minutes` | How long a shareable preview link stays valid. |
| `media.disk` | Disk for uploads. Must be public. |
| `media.directory` | Folder within that disk. |
| `revisions.keep` | Snapshots kept per page, pruned on publish. |
| `robots.disallow_panel` | Panel path to disallow in `robots.txt`. `null` leaves it crawlable. |

## Upgrading

New tables ship as new migration files, never as an edit to one that already ran, so after every update:

```bash
composer update safi/filament-atelier
php artisan vendor:publish --tag=filament-atelier-migrations
php artisan migrate
```

`vendor:publish` skips files you already have, so this copies only what is new. Skipping it fails late rather than loudly: the panel loads, and the missing table surfaces the first time someone uses the feature that needs it. The changelog says which releases need it.

## Known limits

Worth knowing before you promise anything to a client:

- **Block types are code only.** Creating them from the panel is not built, and that is deliberate: it is what stops a client breaking the design.
- **Reordering is arrow buttons, not drag,** and new sections are added at the end.
- **No JSON-LD.** Meta, canonical, hreflang, Open Graph and a sitemap are in; structured data is not.
- **No revisions UI.** Snapshots are written and restore works from code.
- **No header, footer, contact form or raw HTML block** in the shipped set yet.
- **Arabic shares the section order with English.** One tree, translated text, by design.
- **Not multi-tenant.** One install, one site.

## Links

- [Wiki](https://github.com/Abdulkader-Safi/filament-atelier/wiki), including an agent quickstart and how the data model is shaped
- [Changelog](https://github.com/Abdulkader-Safi/filament-atelier/blob/main/CHANGELOG.md)
- [Security policy](https://github.com/Abdulkader-Safi/filament-atelier/blob/main/SECURITY.md)
- [Issues](https://github.com/Abdulkader-Safi/filament-atelier/issues)
