# 13. Menu manager

> **Status, 29 Aug 2026. Built on a branch, not released.** Research first, see
> [`Docs/research/menu-manager-prior-art.md`](../research/menu-manager-prior-art.md), then
> `prd.md`: success criterion 12 and an `In (v1)` scope line. Data, editor, public
> rendering and the quality-bar items all landed the same day. What's left is the one item
> this file always said was someone else's: header and footer blocks (03) calling the
> renderer instead of a developer wiring their own layout, which is still not built.

## What it is

Named navigation menus (primary, footer, and so on), editable from the panel, rendered by a Blade partial on the public site. Each menu is a shallow JSON tree of link items, one level of nesting, stored in the same tree-in-a-column style as a page's blocks rather than a relational nested-set or adjacency-list table.

```json
{
  "location": "primary",
  "items": [
    {
      "id": "m_1a2b",
      "label": { "en": "About", "ar": "من نحن" },
      "url": "/about",
      "target": "_self",
      "children": []
    }
  ]
}
```

## Why we're building it

Every marketing site needs a header and footer nav a client can edit without touching code. Atelier has no answer for it today: a page is a tree of blocks, but the nav around the page is nowhere. The two Filament plugins in the research brief both solve this with a relational nested-set or adjacency-list table, the WordPress `wp_menu_items` shape. That doesn't fit here. Pages already store their hierarchy as one JSON tree with per-locale maps inline, and a menu is structurally the same shape, just flatter and link-typed instead of block-typed. Building this as a second small JSON tree, not a second hierarchy strategy, keeps the package internally consistent. The query-efficiency case for a relational tree only matters at thousands of rows, not the tens of items a menu actually holds.

## How it should feel

Like editing a page section, not a separate app. Drag to reorder, drag to nest one level, an inline add for a custom link or an existing page, autosave. No save button to remember. Same drag-and-drop-plus-buttons pattern as the section list in the page editor (04), so a client who already learned that doesn't learn a second interaction model for menus.

For the developer: a location is declared in the panel provider with a name, a label and a max depth, the same shape as registering a block. A model becomes a menu source by implementing a small interface. No editing inside the plugin.

## In the dashboard

- A "Menus" page in the admin sidebar, location picker at the top, tree editor below.
- Add item: custom link (label, URL, target), or pick from a registered model (pages first, extensible to others).
- Drag to reorder and to nest, up to the location's configured depth. Button-based reorder (up/down/indent/outdent) as the accessibility fallback, matching the pattern from the research brief.
- EN/AR toggle on item labels, reusing the same translatable-map convention as block attributes (05).
- Delete with confirmation. A menu is small enough that undo isn't worth building yet.

## Tasks

### Data

- [x] `atelier_menus` table: `id`, `location` (unique string key), `items` (json), timestamps. One row per location, no items table.
- [x] `Menu` model, `items` cast to array, `forLocation()` to fetch-or-create the row.
- [x] Item shape: `id`, `label` (per-locale map), `url`, `target`, `children` (one level, same shape). Enforced by the editor (04) rather than the column; the JSON cast doesn't validate shape.
- [x] Location registry declared on the plugin class (`menuLocations(key => label|['label', 'depth'])`), read by the editor page and the front-end helper. No hardcoded locations inside the plugin. `depth` defaults to 1, matching the one-level-of-children the editor and renderer are built for.
- [x] A model becomes a menu item source through a small interface (`MenuSource`: `menuSourceLabel()`, `menuSourceOptions()`, `getMenuLabel()`, `getMenuUrl()`), not a hardcoded per-model panel. Registered on the plugin class via `menuSources()`.

### Editor

- [x] Filament page, not a resource, one per panel. Follows `SiteDetails`'s plainer shape (a bare `Filament\Pages\Page` with a `form()` method) rather than `PageEditor`'s explicit `HasSchemas`, since a menu needs one schema, not several.
- [x] Location switcher, loads that location's tree.
- [x] Drag-and-drop reorder and one-level nesting. Built on Filament's own `Repeater` rather than a bespoke canvas, see "Built" below.
- [!] Button-based reorder (indent/outdent/up/down) alongside drag-and-drop. Not built. The Repeater's drag handle is already keyboard-operable, and the page editor (04) itself has no drag at all, so this isn't a regression against what exists. Revisit if a real accessibility gap turns up.
- [x] Add-item flow: custom link vs. model picker. A custom link is the Repeater's own "Add a custom link" button; a model pick is a header action per registered `MenuSource`, a small form with one Select, not the block editor's settings pane, because there's nothing else to configure on a model pick.
- [x] Autosave, no explicit save button. `updatedData()` persists on every change, reading the form's dehydrated state rather than the raw property, which matters because a `Repeater` re-keys its rows once hydrated.
- [x] Depth enforcement, for the one-level case the editor and renderer support: a location with `depth` 1 gets no children field in the schema at all, so nesting past one level is structurally impossible rather than merely discouraged. A `depth` set above 2 is accepted by the registry but not yet honoured by the schema, which only ever builds one level of nesting; known limit, not a bug, until something needs deeper menus.
- [x] EN/AR label editing. Not the page editor's toolbar toggle: each item's label is a `Tabs` field, one tab per locale, the same pattern `SiteDetails` uses for the site name. Simpler for a form with no live preview to keep in sync, and there's no missing-translation fallback question to answer, since both languages are just visible at once.

### Rendering

- [x] An include, not a Blade component: `@include('atelier::partials.menu', ['location' => 'primary', 'locale' => $locale])`. Every other shared partial in the package (`meta`, `tokens`, `schema`) is an include, not a class-based or anonymous component, so this follows that rather than adding a second convention. Overridable the same way a block view is: a host app's own `atelier::partials.menu` in its view namespace wins.
- [x] Recursive partial (`atelier::partials.menu-items`) for nested items. Capped at one level by construction, since the editor (04) never writes a `children` array on a grandchild; nothing in the partial itself stops a hand-edited row going deeper; it just stops recursing whenever `children` is empty.
- [x] `dir="rtl"`-safe markup: flex row with `gap`, no `ml-`/`mr-`/`left`/`right`/`text-left`/`text-right`, verified in `MenuRenderTest`. Row-direction flexbox reverses under `dir="rtl"` on its own, so this needed no logical-property overrides.
- [x] Active-item detection: `aria-current="page"` on an exact path match, a `font-semibold` class on an exact match or an ancestor match.
- [ ] Cache the resolved tree per location and locale, invalidated on save. Still waiting on 09's page cache, unchanged from the original plan.

### Quality bar

- [x] Deleting a model referenced by a menu item doesn't 500 the public page. Stronger than "doesn't 500": a picked item is a label-and-URL snapshot copied in once, not a foreign key, by the `MenuSource` decision at the top of this file, so a deleted source model changes nothing about the item. `MenuSourceTest` deletes the page after adding it and asserts the public render is untouched.
- [x] A location with no items renders nothing, not an empty `<ul>` or a console error. Covered in `MenuRenderTest`.
- [!] Header and footer blocks (03, still unbuilt) read from this rather than shipping their own nav markup, once both exist. Still blocked on 03. `Safi\Atelier\Models\Page` implements `MenuSource` now, so a client can already pick an existing page as an item without retyping it, the case the task file itself flagged as "pages first."

## Done when

- A developer registers a menu location with one call on the plugin class and it appears in the Menus page with no other file touched.
- A client adds, reorders, nests one level and deletes items in a location, and the change is live on the public page with no publish step (PRD criterion 12).
- The same menu renders correctly in English and Arabic, RTL-correct, from the same tree (PRD criterion 12).
- A model implementing `MenuSource` can be added as an item and its label and URL come from the model, not retyped.

## Built, 29 Aug 2026: Repeater over custom drag-and-drop

The editor tasks above describe a bespoke drag-and-drop interaction. Built instead on
Filament's own `Repeater`, `->reorderable()`, nested one level deep for children. It already
does drag-to-reorder, it is what the rest of the panel uses for a list of things, and it
means no SortableJS wiring and no custom Livewire properties to keep in step with the tree.
The one-level nesting comes from a fixed inner `Repeater` on each item rather than a
recursive one, which matches the location's own one-level depth limit rather than fighting
it. No separate up/down/indent/outdent buttons were built on top: the page editor (04)
doesn't have drag either, arrow buttons only, so this is the more-built of the two
interactions, not the less-accessible one, and a button fallback can be added later if a
real accessibility gap shows up rather than a hypothetical one.

## Fixed, 29 Aug 2026: two bugs found by hand-testing the built editor

Both found by actually typing into the panel rather than trusting the Livewire test helper,
which drives `$this->data` directly through `set()` and so never exercises either bug.

**Nothing typed was saving.** Every Filament field defers its state to the server by
default; the value only reaches `$this->data` on some other network round-trip. Nothing on
this page forced one, so `updatedData()` never ran for ordinary typing; the tests still
passed because `Livewire::test()->set('data.items', [...])` writes the property directly,
which isn't what a browser does. Fixed by adding `->live(onBlur: true)` to the label and URL
fields, `->live()` to the target select.

**No "Add a sub-item" button ever appeared.** `itemSchema()` built the nested Repeater only
when `depth() > 1`, but `depth()` defaults to 1 and the docblock on it says "Default 1: one
level of children," so a location with no explicit depth override, meaning every location
registered so far, got no nesting UI at all. Off-by-one, fixed to `depth() > 0`.

Both verified by hand in the running example app: typing into a fresh item and reloading
keeps it, and "Services" now nests "Website services" under it and keeps that too.

## Changed, 29 Aug 2026: config is now where locations live

Locations moved from being `menuLocations()`-only to `config('atelier.menus')`, the same
place `locales` lives, seeded onto the `MenuRegistry` singleton in
`AtelierServiceProvider::packageRegistered()`. `menuLocations()` on the plugin still works,
additive rather than a replacement, for a location that only makes sense inside one panel.
Reasoning: a location is a key and a label, no classes involved, the same shape as a locale;
`menuSources()` stays plugin-only because it lists Eloquent model classes, the same shape as
`blocks()`. `example/config/atelier.php` now carries `primary`, `footer` and `sidebar`; the
panel provider's own call is gone.

Also added: `Menu::treeFor($location)` and `Menu::label($item, $locale)`, both static and
callable from anywhere, not only Blade. `atelier::partials.menu` now calls the first rather
than duplicating the registry-check-then-fetch logic inline, and `atelier::partials.menu-items`
calls the second rather than duplicating the per-locale fallback. This is the actual answer
to "expose a way to design it": overriding the shipped partial's file is one option, calling
`Menu::treeFor()` from a controller, a Livewire component, or a hand-rolled view that never
touches the shipped partial is the other, and now there's one canonical place either path
goes through.

## Changed, 30 Aug 2026: styling moved out of the vendor-override path

Built a vendor-override (`resources/views/vendor/atelier/partials/menu.blade.php`) first,
the documented Laravel way to restyle a package view. Reasonable feedback: nobody actually
reaches for that path, it is not where a developer looks when they want to change how
something on their own site looks. Deleted it. `example/resources/views/partials/nav.blade.php`
replaces it: a normal view in the app, calling `Menu::treeFor()` and `Menu::label()` directly,
included from `layouts/marketing.blade.php` like any other partial. First pass had a
hover dropdown, an SVG chevron and ancestor-highlight logic, in short exactly the "not that
much code" problem it was supposed to solve; cut down to labels, `aria-current`, and one
level of children shown inline, about a third the size.

## Fixed, 30 Aug 2026: reorder, add, delete never saved either

Reported as "resorting the menu doesn't work." Root cause is a level up from the
`live(onBlur: true)` fix earlier in this file: reorder, add, delete are not field
updates at all, they are Filament Actions built into the Repeater (`getReorderAction()`,
`getAddAction()`, `getDeleteAction()`), each one mutating the Repeater's own state and
calling `callAfterStateUpdated()`, which never touches this page's `$this->data` and so
never reaches `updatedData()`. A drag reorder proved this by hand in the browser (no visual
change even after simulating real pointer events via JS, so the drop was never reaching the
DOM order, let alone the server) and by a Livewire test that calls `reorder` directly with
the exact arguments Filament's own JS sends, `array_reverse($itemKeys)`, and finds the old
order still there after.

Fixed with `->after()`, Filament's own post-action hook, wired onto `addAction()`,
`deleteAction()` and `reorderAction()` for both the top-level `items` Repeater and the
nested `children` one, each calling `$this->persist()`. Four new tests exercise the exact
action-call shape the real UI sends (`Livewire::test()->callFormComponentAction(...)`)
rather than `set('data.items', ...)`, which was passing throughout and never would have
caught any of this: it writes the property directly and skips the very Action-call path the
bug lived in.
