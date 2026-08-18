# Installing Atelier on a real project

> MVP, 15 Aug 2026. Everything here is verified against the `example/` app in this repo.
> Read `prd.md` for why it works this way.

Atelier needs Laravel 12 or 13, PHP 8.3+, Filament 5, and Tailwind 4 on the front end.

## 1. Install the package

On Packagist as [safi/filament-atelier](https://packagist.org/packages/safi/filament-atelier).

```bash
composer require safi/filament-atelier
php artisan vendor:publish --tag=filament-atelier-config
php artisan vendor:publish --tag=filament-atelier-migrations
php artisan migrate
php artisan storage:link
```

`storage:link` matters. Uploaded images go to the `public` disk, and without the link every image in the builder and on the site is broken.

## Upgrading an existing install

New tables ship as new migration files, never as an edit to one that already ran, so
re-publish after every update:

```bash
composer update safi/filament-atelier
php artisan vendor:publish --tag=filament-atelier-migrations
php artisan migrate
```

`vendor:publish` skips files you already have, so this copies only what is new. Skipping it
fails late rather than loudly: the panel loads and the missing table surfaces as
`no such table: atelier_...` the first time someone uses the feature that needs it.

`CHANGELOG.md` says which releases need it. The unreleased one does, for
`atelier_page_revisions`.

## 2. Register the plugin

In your panel provider:

```php
use Safi\Atelier\AtelierPlugin;
use Safi\Atelier\Blocks\DefaultBlocks;

->plugins([
    AtelierPlugin::make()
        ->blocks(DefaultBlocks::all()),
])
```

`DefaultBlocks::all()` is the set Atelier ships. Pass your own array instead to cherry-pick, and add your own classes alongside them.

## 3. Point Tailwind at the block views

**This step is required and its failure mode is silent:** blocks render unstyled, with no error anywhere. Tailwind scans source files, and it does not know your vendor directory exists.

In `resources/css/app.css`:

```css
@source '../../vendor/safi/filament-atelier/resources/views/**/*.blade.php';
```

Then `npm run build`. If you write your own blocks, their views are in your app and Tailwind already scans those.

## 4. Decide what owns `/`

Atelier registers a catch-all for `/{slug}` and `/{locale}/{slug}`. Your app's own routes are matched first, so nothing you already have breaks. A fresh Laravel app has a welcome route on `/`; remove it if you want the CMS to own the home page.

## 5. Configure locales

`config/atelier.php`:

```php
'locales' => [
    'en' => ['label' => 'English', 'dir' => 'ltr'],
    'ar' => ['label' => 'العربية', 'dir' => 'rtl'],
],
```

The **first** locale is the default and lives at `/{slug}`. The rest live at `/{locale}/{slug}`.

Decide this before pages exist. Changing it later means migrating the per-locale maps inside every block tree on every page. If the site is English-only, delete the Arabic line now.

## 6. Optional: demo content

```php
(new Safi\Atelier\Database\Seeders\AtelierDemoSeeder)->run();
```

Creates Home, About and a draft Contact so you can see the thing working. Delete them once you have real pages.

## Writing a block

A block is one PHP class and one Blade view. Nothing inside the plugin changes.

```php
namespace App\Blocks;

use Filament\Forms\Components\TextInput;
use Safi\Atelier\Blocks\BaseBlock;
use Safi\Atelier\Media;

class StatsBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'stats';
    }

    public static function icon(): string
    {
        return 'heroicon-o-chart-bar';
    }

    public static function category(): string
    {
        return 'Content';
    }

    /** Fields stored per locale. */
    public static function translatable(): array
    {
        return ['heading'];
    }

    /** Starting values when the block is added. */
    public static function defaults(): array
    {
        return ['heading' => ['en' => 'By the numbers']];
    }

    public function schema(): array
    {
        return [
            TextInput::make('heading')->live(debounce: 400),
            Media::upload('image', 'Image')->live(),
        ];
    }
}
```

Then `resources/views/blocks/stats.blade.php` in your app, and register `StatsBlock::class` in the plugin.

Three things worth knowing:

- **`->live(debounce: 400)` is what makes the preview update as you type.** A field without it only refreshes the preview when focus leaves.
- **`translatable()` fields are stored as `{"en": "...", "ar": "..."}`.** Everything else is shared across locales. Repeaters can be translatable too; the whole list is then per locale.
- **Images: use `Media::upload()` in the schema and `Media::url()` in the view.** Don't call `Storage::url()` yourself. `FileUpload` state isn't reliably a string, and `Media::url()` is where that's handled.

The Blade view receives `$attributes` (already collapsed to the current locale), `$id`, `$locale`, `$editing` and `$children`.

## What the editor does

Pages list → a page → its settings (slug, SEO) → **Edit page content** opens the builder full screen in a new tab.

In the builder: the sidebar lists sections, clicking one swaps it to that section's settings. Add, duplicate, hide, delete and reorder from there. The middle is the real page, at desktop, tablet or mobile width, in either language. **Save** keeps it a draft; **Publish** puts it live.

## Rebuilding after you change a plugin view

Only if you're working on the package itself, not when consuming it:

```bash
npm run build                                 # the editor's own stylesheet, from the repo root
cd example && php artisan filament:assets
```

## Known limits in this MVP

- **Reordering is arrows, not drag.** Drag-and-drop is next.
- **Block types are code only.** Creating them from the panel is v2 and deliberately not started.
- **Arabic shares the section order with English.** One tree, translated text. This is a deliberate trade, see `prd.md`.
- **No revisions UI.** Publishing overwrites the published copy; snapshots aren't stored yet.
- **The contact block is presentational.** It doesn't submit anywhere; wire a route yourself.
- **No sitemap yet**, and no JSON-LD. Meta, canonical, hreflang and Open Graph are in.
