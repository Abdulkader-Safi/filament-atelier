# Menu manager: prior art

> Research brief on two Filament menu-manager plugins, pulled for a future Atelier navigation feature. Not a decision, not a task file. Verified against filamentphp.com, GitHub and npm/packagist metadata in August 2026.

## Why look at these

A menu is a page's block tree with the categories swapped for a flatter, link-typed shape: named locations instead of a single tree, items instead of blocks, one level of nesting instead of arbitrary. Two Filament plugins already solve "manage navigation from the admin panel," so it's worth knowing what they got right and where their data model diverges from Atelier's before speccing a task file.

## ysfkaya/menu-manager (paid, €59)

Closed source, no public repo. Everything below is from the plugin's filamentphp.com listing.

**Storage.** A relational table with hierarchy via `kalnoy/nestedset` (nested set model). Columns: title, URL, target, location, locale, plus a free-form `data` column for extra fields. Nested set means fast subtree reads but write-heavy reordering (every sibling shift touches `lft`/`rgt` bounds on the whole subtree).

**Locations.** Multiple named menu positions, each with a configurable max depth via `addLocation(name, label, depth)`.

**Item sources.** Three built-in panel types, extensible: `CustomMenuPanel` (arbitrary link), `FixedMenuPanel` (pinned/system link), `ModelMenuPanel` (pulls from an Eloquent model).

**Editing.** Drag-and-drop plus button-based reorder (accessibility fallback), autosave, per-location form fields, dark mode.

**i18n.** Locale-specific rendering, translatable nodes, but as separate rows per locale, not a per-locale map inside one node (the WordPress-menu approach, not Atelier's page-tree approach).

**Frontend.** A `@menu(location, view, dataKey, viewData)` Blade directive, with prebuilt Tailwind/Alpine views (simple navbar, dropdown, multi-level). Docs recommend a tag-aware cache driver.

**Requires.** PHP 8.2+, Filament 4.0+, Laravel 11+. Install is licensed: email + license key against a private Composer repo, which rules it out as a dependency for a plugin you intend to distribute (public or private to dsrpt) without passing that licensing cost through.

**Author.** Yusuf Kaya, Istanbul-based Laravel engineer.

## notebrainslab/filament-menu-manager (free, MIT)

Open source: [github.com/notebrainslab/filament-menu-manager](https://github.com/notebrainslab/filament-menu-manager). Beta, package health 82/100, 20 combined stars across the author's two plugins: young, low-adoption, read the code before leaning on it for anything load-bearing.

**Storage.** Also relational, adjacency list (parent-child foreign key columns), not nested set, not JSON.

**Config.** Fluent API:

```php
FilamentMenuManagerPlugin::make()
    ->locations(['primary' => 'Primary', 'footer' => 'Footer'])
    ->modelSources([App\Models\Post::class, App\Models\Page::class])
    ->navigationGroup('Content')
    ->navigationIcon('heroicon-o-bars-3')
    ->navigationSort(10)
    ->navigationLabel('Menus')
    ->authentication(fn () => auth()->user()->can('View:MenuManagerPage'));
```

**Model sources: the `HasMenuItems` trait.** Any Eloquent model opts in with four method stubs, no editing inside the plugin required:

```php
use NoteBrainsLab\FilamentMenuManager\Concerns\HasMenuItems;

class Post extends Model
{
    use HasMenuItems;

    public function getMenuLabel(): string { return $this->title; }
    public function getMenuUrl(): string { return route('posts.show', $this); }
    public function getMenuTarget(): string { return '_self'; }
    public function getMenuIcon(): ?string { return 'heroicon-o-document'; }
}
```

This is the same shape as Atelier's block registry rule ("adding a block must never require editing a file inside the plugin"), just applied to menu item sources instead of block types.

**Editing.** SortableJS drag-and-drop with nested hierarchy, up/down/indent/outdent buttons, debounced autosave, dark mode via CSS custom properties.

**Frontend.** A `MenuManager` service class, not a directive:

```blade
@php
    $tree = app(\NoteBrainsLab\FilamentMenuManager\MenuManager::class)
        ->menusForLocation('primary')
        ->first()
        ?->getTree() ?? [];
@endphp

@foreach ($tree as $item)
    <a href="{{ $item['url'] }}" target="{{ $item['target'] }}">{{ $item['title'] }}</a>
    @if (!empty($item['children']))
        {{-- recurse --}}
    @endif
@endforeach
```

`getTree()` already returns a nested array, which is the one place this plugin's runtime shape (if not its storage shape) resembles Atelier's JSON tree.

**Requires.** PHP 8.2+, Filament 4.x/5.x, Laravel 11-13, Livewire 3/4: same floor as the rest of the stack.

**Author.** Notebrains, a small dev shop.

## A third data point: datlechin/filament-menu-builder

Pulled for comparison, not a candidate: [github.com/datlechin/filament-menu-builder](https://github.com/datlechin/filament-menu-builder). Same relational adjacency-list model as notebrainslab's. Two things worth noting anyway: a `Menu::location('header')` lookup that caches and auto-invalidates on save (rather than re-querying per request), and active-state detection with both "exact URL match" and "matches self or any descendant" modes for highlighting the current nav item.

## What doesn't transfer to Atelier

Both real plugins store hierarchy relationally: nested set (ysfkaya) or adjacency list (notebrainslab, datlechin). That's the WordPress `wp_menu_items` mental model, one row per item, parent_id pointers, a query to rebuild the tree. It doesn't match how Atelier already stores pages: one JSON tree in a column (`draft_content`/`published_content`), translatable text as a per-locale map inside the same node rather than one row per locale. Importing either plugin's table design would mean maintaining two different hierarchy strategies in the same package for no real gain, menus are small (tens of items, not thousands), so the query-efficiency argument for a relational tree structure doesn't apply the way it might for a deep taxonomy.

Also skip: ysfkaya's license-gated Composer repo (adds a distribution dependency you don't control), and the directive-vs-service split. Atelier's render path is already "one Blade view reads one data source, same for editor preview and public," a menu tree is just another data source into that same call, it doesn't need its own directive or service class to stay separate from block rendering.

## What's worth carrying forward, conceptually

- **Named locations with a per-location depth cap** (ysfkaya): a cheap guard rail against a footer menu growing five levels deep by accident.
- **The trait-based model-source pattern** (notebrainslab's `HasMenuItems`) over hardcoded per-source-type panels: matches Atelier's "no editing inside the plugin to add a source" rule already applied to blocks.
- **Button-based reorder as an accessibility fallback** next to drag-and-drop: both plugins ship this, worth keeping.
- **Cached lookup by location** (datlechin) with invalidation on save, rather than re-querying the tree per request.
- **Active-state detection modes** (datlechin): exact vs descendant-match, as a small but real UX detail for a nav Blade partial.

The shape a menu takes in Atelier, if built, would be closer to "a second small JSON tree next to the page tree, sharing the existing translatable-map convention, read by the same kind of Blade partial a block already is" than to either plugin's schema. That's a design direction, not a decision, next step is a task file if this gets prioritized.

## Sources

- ysfkaya Menu Manager listing: https://filamentphp.com/plugins/ysfkaya-menu-manager
- ysfkaya GitHub profile: https://github.com/ysfkaya
- notebrainslab Menu Manager listing: https://filamentphp.com/plugins/notebrainslab-menu-manager
- notebrainslab/filament-menu-manager: https://github.com/notebrainslab/filament-menu-manager
- datlechin/filament-menu-builder: https://github.com/datlechin/filament-menu-builder
