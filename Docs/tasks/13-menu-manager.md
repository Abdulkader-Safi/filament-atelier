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

## Rebuilt, 30 Aug 2026: dropped the Repeater entirely

The Repeater fix above was correct but the UI it fixed was the actual problem. Every item's
full form sat open at once, a twelve-item menu was a page of cards, and a client had no way
to tell what was nested under what beyond a "Sub-items" label. Checked Shopify's own menu
editor on Mobbin for a second opinion rather than guessing: a flat, minimal list with a drag
handle, a label, "Edit" and "Delete", nothing else in the row, editing in a separate surface
rather than inline.

Rebuilt on that shape, and dropped the `Repeater` at the same time:

- **The tree is a plain array this page owns**, `addItem()` / `deleteItem()` / `move()`,
  each calling `persist()` itself, the same shape `PageEditor` already uses for blocks. No
  more Repeater re-keying, no more `Hidden::make('id')` workaround, no more `->after()`
  hooks bolted onto three separate built-in actions: owning the array sidesteps the whole
  class of bug the previous section fixed, rather than patching around it.
- **Editing is a Filament `Action` modal** (`editItemAction()`), not an inline expand. A row
  that turns into a form reflows everything under it; a modal is what "Add a custom link"
  already used, is the panel's own theme for free, and is the shape Shopify's edit panel
  uses too. `deleteItemAction()` the same way, with Filament's own `requiresConfirmation()`
  rather than a hand-rolled `wire:confirm`.
- **The row shows a label only**, no URL, matching Shopify's restraint. Reordering is still
  the up/down buttons from the earlier version, not drag, on purpose: the panel's own
  Repeater-based drag already needed a bug fix once this session, hand-rolling SortableJS
  ourselves would risk the same class of bug a second time, and arrow buttons are the more,
  not less, accessible of the two.

One real bug surfaced building this, not a design choice: new Tailwind classes in a
package Blade view need `npm run build` in the package **and** `php artisan filament:assets`
in the host app before they render, because the panel loads a pre-compiled
`resources/dist/atelier.css`, not a live Tailwind scan of the app's own views. Skipping
either step is silent: the row renders, just unstyled or identically-styled regardless of
nesting, which is exactly the "children don't look nested" bug this was caught by.

Also switched off Filament's own `primary`/`danger` color utilities (`text-primary-600`,
`text-danger-600`) for the Edit/Delete links: those are Filament's dynamic CSS-variable
colors, generated by Filament's own Tailwind preset, which this package's minimal
`atelier.css` doesn't import. `PageEditor` already knew this and uses plain
`text-gray-400 hover:text-red-600`; matched that instead of introducing a second convention.

## Changed, 30 Aug 2026: URL is per-locale now, not shared

A menu item's `url` was one string, shared across every locale. Wrong the same way a shared
page slug would be wrong: English is `/home`, Arabic is `/ar/home`, and one URL for both
means one language always links to the wrong path. `url` is now a per-locale map, the same
shape `label` already was, edited as a field inside each locale's own tab in the edit modal
rather than a single field outside the tabs.

`Menu::url($item, $locale)` is the read-side companion to `Menu::label()`, but deliberately
does **not** fall back to another locale the way `label()` does: a label without a
translation is readable-but-untranslated, a URL borrowed from the wrong locale is a broken
link. Returns `null` on a miss, matching `Page::url()`'s own contract, and the partials fall
back to `href="#"` the same way they already did for a missing item.

`MenuSource::getMenuUrl()` is unchanged, still one string: a picked source only prefills the
default locale's URL, same as it only ever prefilled the default locale's label, and the
other locale is the editor's to fill in.

## Added, 30 Aug 2026: drag-and-drop, including reparenting

The arrow buttons only ever reorder within a level; there was no way to promote a sub-item to
top-level or demote a top-level item into another one's children short of deleting and
retyping it. Added real drag-and-drop for that, built by hand rather than through the
Repeater's `->reorderable()`, since that path already needed a bug fix once this session
(the "Fixed" section above) and hand-rolling it here means owning the one mechanism instead
of two.

`Sortable.create()` runs directly against `window.Sortable`, the copy Filament's own
`support` package already loads globally, on every `[data-sortable-list]` element: the
top-level list, and each nestable top-level item's own children list, all sharing one
`group` so SortableJS allows dragging between them. A `MutationObserver` on the whole
component re-runs setup after every Livewire-driven DOM change, rather than betting on a
specific Livewire lifecycle hook name; a WeakMap-style flag on each list element (technically
`_atelierSortable`) makes that idempotent instead of double-attaching.

On drop, `sync()` reads the current order of every list straight from the DOM, keyed by
parent id, and sends the whole shape in one call to a new `reorderTree(array $top, array
$children = [])` method: rebuilds `$this->tree` from that shape, looking every id up against
the tree as it stood before the drag, silently dropping any id it doesn't recognise rather
than trusting the browser's payload, and discarding a dragged item's own children if it has
any, since nothing in the UI offers a place to drag into a child's row and a request that
claims otherwise is enforcing depth from the wrong side. Six new tests cover the PHP side:
same-level reorder, promoting a child, demoting a top-level item into another one's
children, the grandchild-dropping enforcement, and an unrecognised id being ignored.

**What isn't verified by automation: the drag gesture itself.** Every attempt this session to
simulate a real drag through browser automation, both a native drag synthesis and
hand-dispatched `pointerdown`/`pointermove`/`pointerup` sequences, produced no DOM change at
all, against this component and against the Filament Repeater's own drag earlier in this
file. Real mouse input works fine through the same tooling elsewhere on this page (the move
buttons, the edit modal), so this reads as a synthetic-event limitation specific to how
SortableJS detects a drag start, not evidence of a bug. It means the actual gesture needs a
human to try it, which happened the same day, see below.

## Fixed, 30 Aug 2026: a same-list drag could delete a sibling

Hand-tested the drag once it was built. Dragging "Website services" above "mobile services",
both children of the same "Services" item, made "mobile services" disappear, not just from
the screen, the delete persisted.

The likely cause: `MutationObserver({ subtree: true })` watching the whole component reacted
to every DOM mutation, including SortableJS's own internal shuffling while a drag was still
in progress, and re-ran setup mid-drag. A `sync()` firing off that half-settled DOM read the
wrong snapshot and sent it to the server, which trusted it completely, `reorderTree()`
rebuilds the tree from exactly the shape it's given.

Checked `notebrainslab/filament-menu-manager`'s own source for how a plugin solving the same
problem handles it, since Docs/research/menu-manager-prior-art.md had already looked at it
once for the data model but not the client-side reorder code. Its `menu-builder.js` doesn't
use a MutationObserver at all: one small Alpine component per sortable `<ul>`, initialised
once in that component's own `init()`, torn down and recreated only when Livewire actually
replaces that specific element. Rebuilt to match: `x-data="atelierMenuSortable()"` on each
list rather than one wrapping component with a global observer, each list creates its own
`Sortable` instance the first time Alpine mounts it and never again for that same DOM node.
`sync()` still walks the whole tree from one fixed root (`#atelier-menu-root`) on any list's
`onEnd`, a drop in a nested list can still move an item to a different level, but it's no
longer triggered by anything other than an actual drag ending.

Also added a second, independent safety net server-side, since a client-side fix alone still
leaves the same failure mode possible if some other race turns up later:
`reorderTree()` now refuses the whole call outright if the incoming shape doesn't account
for every id the tree already had, rather than silently applying a partial one. Two tests
cover it directly: one confirms a payload missing an id is rejected wholesale (the tree comes
back untouched, not touched-and-pruned), the other confirms depth enforcement still holds
when every id **is** accounted for but a payload claims two levels of nesting, which the UI
itself can never produce, only a tampered request can.

Moved the up/down buttons next to Edit/Delete on the row's end while making both fixes, on
request: the drag handle now sits alone at the row's start.
