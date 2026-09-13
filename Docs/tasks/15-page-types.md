# 15. Page types

> **Written 13 Sep 2026, against the code at 0.3.6.** Designed in conversation the same
> day and approved before any code. Ships in 1.0.0, so [14](14-v1-release.md)'s gates
> cover it too: whatever this adds to the public API is named in Gate B alongside `Block`
> and `AtelierPlugin`.

## What it is

A page type is a kind of page a developer defines in code: Service, Product, Case study,
whatever the site needs. Each one gets its own entry in the panel sidebar, its own custom
properties, its own starter sections, and its own set of blocks that may be used on it.

```php
class ServiceType extends PageTypeResource
{
    public static function type(): string { return 'service'; }
    public static function label(): string { return 'Service'; }
    public static function icon(): string { return 'heroicon-o-wrench-screwdriver'; }

    /** Any Filament components. The plugin never sees the field names. */
    public static function fields(): array
    {
        return [
            FileUpload::make('card_image')->image(),
            Textarea::make('excerpt'),
            TextInput::make('starting_price')->numeric(),
        ];
    }

    public static function translatable(): array { return ['excerpt']; }

    public static function template(): array { return [['type' => 'hero'], ['type' => 'cta']]; }
    public static function blocks(): ?array { return ['hero', 'features', 'gallery', 'cta']; }

    public static function cardView(): string { return 'services.card'; }
    public static function prefix(): array|string|null { return ['en' => 'services', 'ar' => 'خدمات']; }
    public static function schemaType(): ?string { return 'Service'; }
    public static function indexView(): ?string { return 'services.index'; }
}
```

Registered the way everything else is:

```php
AtelierPlugin::make()->pageTypes([ServiceType::class]);
```

Underneath it is still one `atelier_pages` row, one editor, one render path. A type adds a
`type` string, a `data` JSON column for its own properties, and a Filament resource scoped
to it.

Alongside it, one new block: **Collection**, which lists pages of a type and renders each
one through that type's card view. This is the reason page types are worth building. A
custom property nobody can list is a form field with no consumer.

## Why we're building it

A marketing site is not a flat pile of pages. It has services, products, case studies, team
members, each with the same handful of extra facts and each listed somewhere with a link
back. Today Atelier can only say "page", so those extra facts have nowhere to live except
inside a block's attributes, where they are trapped: a hero's heading belongs to that hero
and nothing can query it. A listing has to be typed by hand, and it goes stale the day a
service is added.

The schema.org `type` select on the settings screen already hints at the idea and stops
short: it tells a search engine the page is a `Service` and tells the panel nothing. This
feature is that idea taken seriously, with the panel, the fields and the listings that a
type implies.

Doing it as one table and one editor rather than a model per type is what keeps it cheap.
All of it already works on `Page`: slugs, redirects, revisions, drafts, preview, SEO, the
sitemap. A service inherits every one of them for free.

## How it should feel

For the client: Services is just there in the sidebar, next to Pages, with its own icon.
Opening one shows the fields that service needs and nothing else. Creating one opens the
builder with the sections a service usually has, already placed. Adding a listing to the
homepage is picking Collection, choosing Services, and being done, with new services
appearing in it forever after.

For the developer: one class per type, six lines if the type has no custom fields. Any
Filament component works in `fields()`, because it is a plain schema, the same reason
`Block::schema()` gets the whole control system free. Nothing inside the plugin is edited
to add a type, the same rule blocks already follow.

## In the dashboard

- **Sidebar.** One entry per registered type, above or below Pages by
  `navigationSort()`. Pages itself now lists only untyped pages.
- **The list.** The existing page table, scoped to the type. Same status badge, same
  "Open builder" action.
- **The settings screen.** A section named after the type, holding whatever `fields()`
  returned, sitting between the locale tabs and the SEO section. Translated fields get the
  locale tabs the meta fields already use.
- **The builder.** Unchanged, except the section picker only offers the blocks the type
  allows, and a new page opens with the type's starter sections in place.
- **Collection block.** Type, source (all published, hand-picked, or children of this
  page), order, limit, optional heading.

## Decisions taken up front

Recorded here because they were argued and settled, not assumed.

**Custom properties are not drafted.** Everything on the settings screen outside the block
tree saves live today, outside the draft and publish cycle: title, slugs, SEO, structured
data. `data` joins them. Editing a service's price changes the live page at once, while
its blocks still need publishing. Splitting `data` into draft and published copies would
make one section of the settings screen behave differently from every other section on
it.

**One class per type, not two.** The type class is the Filament resource. It puts
`cardView()`, a front-end concern, on a resource class, and that is the price of a
consumer writing one file instead of two. The class is plain PHP and costs nothing to
autoload on a public request, which is where the Collection block reads it from.

**A real page always beats a generated index.** A type's index route is registered before
the catch-all, so it would otherwise shadow a page whose slug is `services`. The index
controller looks for a page at that slug first and hands off to `PageController` when it
finds one.

**Listings show published pages only, in the editor as well as the public page.** A
preview that lists half-finished drafts is a preview of a site that does not exist.

**No forced layout.** A type does not pin its Blade shell. Any registered layout stays a
per-page choice.

## Tasks

### Data

- [ ] Migration `add_type_to_atelier_pages_table`: `type` string default `page`, indexed,
      and `data` json nullable. Existing rows become `page`, so nothing in a live install
      moves.
- [ ] Register the migration in `AtelierServiceProvider::configurePackage()`.
- [ ] `Page::data(string $key, ?string $locale = null, mixed $default = null)`, collapsing
      a per-locale map the way `Renderer::localise()` does, with a fallback to the first
      configured locale.
- [ ] `Page::scopeOfType()` and `Page::pageType()` returning the registered class or null.
- [ ] `data` added to the model's array casts.

### The type contract

- [ ] `PageType` interface: `type`, `label`, `pluralLabel`, `icon`, `navigationSort`,
      `fields`, `translatable`, `template`, `blocks`, `cardView`, `prefix`, `schemaType`,
      `indexView`.
- [ ] `PageTypeRegistry` singleton: `register()`, `has()`, `resolve()`, `all()`,
      `options()`. Same shape as `BlockRegistry`.
- [ ] `AtelierPlugin::pageTypes()` feeding both the registry and `$panel->resources()`.
- [ ] Defaults on `PageTypeResource` for every method except `type()` and `label()`, so a
      type with no custom fields is six lines.

### Panel

- [ ] Split `PageResource::form()` into `titleSection()`, `localeSection()`,
      `typeSection()` and `structuredDataSection()`. A subclass cannot inject a section
      into the single 250-line method as it stands.
- [ ] `PageTypeResource` extends `PageResource`: scope the query, set label, plural label,
      icon and navigation sort from the type, build `typeSection()` from `fields()`.
- [ ] `PageResource` scopes itself to `type = 'page'` so typed pages leave the Pages list.
- [ ] Create: write the type, seed `draft_content` from `template()`, prefill the slug
      with the type's prefix for each locale.
- [ ] Translated custom fields get locale tabs, reusing `PageResource::localeTabs()`.
- [ ] `schema.type` defaults to the type's `schemaType()` where it declares one.
- [ ] Picker filtered by `blocks()` in `PageEditor::getPickerProperty()`. A block already
      on a page whose type no longer allows it keeps rendering; removing it would delete a
      client's content because a developer edited an array.

### Collection block

- [ ] `CollectionBlock` plus `blocks/collection.blade.php`, registered in `DefaultBlocks`.
- [ ] Sources: all published of a type, hand-picked ids, or children of the current page
      by slug path (`Page::children()` already does the query).
- [ ] Order (title, newest, hand-picked order) and limit.
- [ ] Renders each item through the type's `cardView()`, with `$page` and `$locale` in
      scope. A shipped fallback card (title and link) for a type that declares none, so
      the block never renders blank.
- [ ] `Renderer` passes `page` into every block view. Four call sites: `PageController`,
      `PreviewController`, `PageEditor`, `StructuredData`.

### Public routes

- [ ] One index route per type per locale at its prefix, registered before the catch-all.
- [ ] The index controller yields to a real page at that slug.
- [ ] Index routes in the sitemap.

### Quality bar

- [ ] `npm run build` in the same commit as any panel Blade view change, per `CLAUDE.md`.
- [ ] `Docs/features.md` and `Docs/installation.md` updated: registering a type, writing a
      card view, the Collection block.
- [ ] `prd.md` gains the page-type rule next to "block types are code-defined in v1".

## Done when

- [ ] A type registered in a panel provider appears in the sidebar with its own icon and
      lists only its own pages, and Pages no longer lists them.
- [ ] Creating one prefills the prefixed slug, seeds the starter sections, and opens a
      builder whose picker offers only the allowed blocks.
- [ ] A translated custom property round-trips as a per-locale map; an untranslated one
      stores a single value.
- [ ] A Collection block on a page renders one card per published page of the type, each
      linking to the right per-locale URL, in the editor preview and on the public page
      identically. Criterion 2 in `prd.md`, the preview matching the page, holds for it.
- [ ] The index route serves at `/services` and `/ar/خدمات`, and yields to a real page at
      the same slug.
- [ ] The existing suite still passes with no page carrying a type, which is every page in
      every install that upgrades.
