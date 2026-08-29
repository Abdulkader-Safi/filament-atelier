# 13. Menu manager

> **Status, 29 Aug 2026. Not started.** Research only so far, see
> [`Docs/research/menu-manager-prior-art.md`](../research/menu-manager-prior-art.md). Not
> yet in `prd.md`'s success-criteria list, added here ahead of the PRD catching up.

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

- [ ] `menus` table: `id`, `location` (unique string key), `tree` (json), timestamps. One row per location, no items table.
- [ ] `Menu` model, `tree` cast to array.
- [ ] Item shape: `id`, `label` (per-locale map), `url`, `target`, `children` (recursive, same shape).
- [ ] Location registry declared on the plugin class (`addMenuLocation(key, label, maxDepth)`), read by the editor page and the front-end helper. No hardcoded locations inside the plugin.
- [ ] A model becomes a menu item source through a small interface (`MenuSource`: `getMenuLabel()`, `getMenuUrl()`), not a hardcoded per-model panel. Registered on the plugin class the same way `modelSources()` works in the research brief.

### Editor

- [ ] Filament page, not a resource, one per panel, same `Page` plus `InteractsWithSchemas` pattern as the visual builder (02, 04).
- [ ] Location switcher, loads that location's tree.
- [ ] Drag-and-drop reorder and one-level nesting, matching the section-list interaction in 04.
- [ ] Button-based reorder (indent/outdent/up/down) alongside drag-and-drop.
- [ ] Add-item flow: custom link vs. model picker, opens the same kind of settings pane the block editor uses.
- [ ] Autosave, no explicit save button, matching the rest of the editor (04, 06).
- [ ] Depth enforcement: nesting beyond a location's configured max is refused in the UI, not silently allowed and broken on render.
- [ ] EN/AR label editing behind the same toolbar toggle as the page editor, sharing the fallback-to-default-locale rule from 05 rather than inventing a second one.

### Rendering

- [ ] A `menu()` helper or Blade component that resolves a location's tree for the current locale. `atelier::partials.menu` as the default view, overridable by the host app the same way block views are.
- [ ] Recursive partial for nested items, capped at the location's configured depth so a bad tree can't render infinitely.
- [ ] `dir="rtl"`-safe markup: logical properties, no `ml-`/`mr-`/`left`/`right`, matching the rule already enforced on every block view (03).
- [ ] Active-item detection: exact match and descendant match, so a parent item highlights when a child page is current.
- [ ] Cache the resolved tree per location and locale, invalidated on save. Can wait for 09's page cache to land first and copy the pattern.

### Quality bar

- [ ] Deleting a model referenced by a menu item doesn't 500 the public page. Decide whether the item drops silently or falls back to its stored label with no link, and be consistent about it.
- [ ] A location with no items renders nothing, not an empty `<ul>` or a console error.
- [ ] Header and footer blocks (03, still unbuilt) read from this rather than shipping their own nav markup, once both exist.

## Done when

- A developer registers a menu location with one call on the plugin class and it appears in the Menus page with no other file touched.
- A client adds, reorders, nests one level and deletes items in a location, drag-and-drop and buttons both work, and the change is live on the public page with no publish step.
- The same menu renders correctly in English and Arabic, RTL-correct, from the same tree.
- A model implementing `MenuSource` can be added as an item and its label and URL come from the model, not retyped.

This isn't tied to a PRD criterion yet, since menus aren't in `prd.md`'s success-criteria list. If this gets built, that list needs a line added, and this file's "Done when" should cite it by number the way every other task file does.
