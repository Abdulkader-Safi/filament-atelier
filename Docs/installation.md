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

`CHANGELOG.md` says which releases need it. v0.1.2 does, for `atelier_page_revisions`.

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

## Using your own layout

`atelier.layout` points at the Blade view wrapping the rendered blocks, and pointing it at
your own is the normal way to give a client site its own shell. Your layout receives
`$blocks` (the rendered HTML), `$locale`, `$page`, `$title` and `$preview`. Include the two
partials, or you lose what they carry:

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

⚠️ **Both failures are silent.** Without `partials.meta` the page renders perfectly and has
no title, description, canonical, hreflang or Open Graph tags, and previews stop being
`noindex`. Without `partials.tokens` every `var(--atelier-*)` resolves to nothing, so the
background and spacing controls do nothing and Arabic loses its font stack.

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

The Blade view receives `$attributes` (already collapsed to the current locale), `$id`, `$locale`, `$editing`, `$children` and `$page`, the page being rendered.

## Adding a page type

A page type is a kind of page with its own sidebar entry: services, products, case studies. One class, and nothing inside the plugin changes.

```php
namespace App\PageTypes;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Safi\Atelier\PageTypes\BasePageType;

class ServiceType extends BasePageType
{
    public static function type(): string
    {
        return 'service';
    }

    public static function label(): string
    {
        return 'Service';
    }

    public static function icon(): string
    {
        return 'heroicon-o-wrench-screwdriver';
    }

    /** Whatever this kind of page needs. Any Filament component works. */
    public static function fields(): array
    {
        return [
            Textarea::make('excerpt')->rows(2),
            FileUpload::make('card_image')->image()->disk(config('atelier.media.disk')),
            TextInput::make('starting_price')->numeric(),
        ];
    }

    /** Stored per locale. Everything else is stored once. */
    public static function translatable(): array
    {
        return ['excerpt'];
    }

    /** The sections a new one opens with. */
    public static function template(): array
    {
        return [['type' => 'hero'], ['type' => 'features'], ['type' => 'cta']];
    }

    /** What the section picker offers. Leave it out for everything. */
    public static function blocks(): ?array
    {
        return ['hero', 'features', 'gallery', 'faq', 'cta'];
    }

    /** The partial the Collection block renders per item. */
    public static function cardView(): string
    {
        return 'services.card';
    }

    /** Prefilled on a new page, per locale. The client can still change it. */
    public static function prefix(): array|string|null
    {
        return ['en' => 'services', 'ar' => 'خدمات'];
    }

    public static function schemaType(): ?string
    {
        return 'Service';
    }

    /** Served at /services and /ar/خدمات. Leave it out for no index route. */
    public static function indexView(): ?string
    {
        return 'services.index';
    }
}
```

Register it next to the blocks:

```php
AtelierPlugin::make()
    ->blocks(DefaultBlocks::all())
    ->pageTypes([ServiceType::class]),
```

`type()` and `label()` are the only two required. Everything else has a default, so a type with no custom properties is six lines.

Reading the properties back in a view:

```blade
{{-- resources/views/services/card.blade.php, given $page and $locale --}}
<a href="{{ $page->url($locale) }}">
    <h3>{{ $page->title }}</h3>
    <p>{{ $page->data('excerpt', $locale) }}</p>
    <p>From AED {{ number_format((float) $page->data('starting_price')) }}</p>
</a>
```

Four things worth knowing:

- **`data()` unwraps a translated value and falls back to the first configured locale**, so a card with no Arabic excerpt shows the English one rather than a gap.
- **The prefix is a default, not a rule.** It is applied when the page is created and the client owns the slug afterwards. Changing a slug later writes a 301 the way it always did.
- **A real page at the prefix root beats the generated index.** Build a page at `services` and it serves instead, which is what you want as soon as the client asks for copy above the list.
- **A block already on a page keeps working after it leaves `blocks()`.** Only the picker narrows; nothing deletes a client's content because a developer edited an array.

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
