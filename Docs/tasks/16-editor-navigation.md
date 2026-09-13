# 16. Moving around inside the editor

> **Written 13 Sep 2026, against the code at 0.5.0.** Neither half is a bug report. Both
> are the editor asking for more clicks than the job needs. Drag to reorder also closes
> PRD criterion 3, which [14](14-v1-release.md) lists as an unmet v1 gate.

## What it is

Two ways to stop leaving the builder.

**A Pages panel in the icon rail**, listing every page grouped by type, with the one being
edited marked. Clicking one opens it in the builder.

**Links in the preview follow through.** Clicking a nav item inside the preview opens that
page in the builder rather than navigating the iframe to the live site.

And one thing that is only about the hand: **drag to reorder sections**, the same gesture
the menu manager already has, instead of clicking an arrow four times.

## Why we're building it

Today the builder is a dead end with one exit. Editing two pages means: back arrow, the
Pages list, find the row, Open builder, and the same five steps to come back. A client
fixing a typo across a header and a footer does that a dozen times an afternoon.

Worse than the clicks, the preview lies about it. The nav inside the preview is real
markup with real hrefs, so clicking one navigates the iframe to that page's public URL.
The canvas then shows a different page from the one the left panel is editing, with no
indication anything happened. Nothing errors, nothing warns, and the section list is now
describing a page you are not looking at.

Reordering has the same shape of problem. Moving a section from the bottom of a
twelve-section page to the top is eleven clicks, each one a Livewire round trip and a
preview refresh, and the menu manager sitting one screen away does it with one drag.

## How it should feel

Like a browser with an editor attached, rather than a form with a picture beside it. Click
a link in the page, you are editing that page. Click Pages, pick another, you are editing
that. The URL in the address bar follows, so refresh and the back button behave.

A link that leaves Atelier, an external site or one of the host app's own routes, opens in
a new tab. The editor never navigates itself somewhere it cannot edit.

Dragging a section feels like dragging a menu item, because it is the same library and the
same configuration.

## In the dashboard

- **Icon rail.** A third button, Pages, under Sections and Settings.
- **The panel.** Search at the top, then pages grouped by type: ordinary pages first, then
  each registered page type under its own label. The current one is marked and not a link.
- **The canvas.** Clicking a link inside the preview switches the builder to that page. A
  link Atelier does not own opens in a new tab and the builder stays where it was.
- **The section list.** A drag handle on each row. Up and down buttons stay, because a
  keyboard needs them and two items do not deserve a gesture.

## Decisions taken up front

**Switching page is a real navigation, not a Livewire state swap.** The panel links to
`/admin/atelier/{id}` with `wire:navigate`. The URL then names the page being edited, so
refresh, back and pasting the link to someone all work. Swapping the record inside the
component would leave the address bar pointing at the previous page, and every piece of
editor state would need resetting by hand.

**The preview script moves out of the layouts and into the controller.** It is copied into
three layouts today and any host writing a fourth has to copy it too, or the editor
half-works with no error. `PreviewController` injects it before `</body>`, so a host layout
carries nothing but the canvas marker.

**One implementation of "which page is this URL".** `PageController` holds the only copy of
the locale-splitting rule, and a second copy is how `/services/web-design` returned the
wrong page in August. It moves to `PageResolver`, which both the controller and the
editor's link handling call.

**A link that resolves to nothing opens in a new tab.** Not a toast, not an iframe
navigation. The editor keeps the page it had and the target opens beside it.

## Tasks

### The resolver

- [ ] `PageResolver::forPath(?string $path): ?array{page, locale, slug}`, holding the rule
      that the first segment is a locale only when it names one, and returning the page
      only when it is published.
- [ ] `PageController` uses it. Its redirect and 404 handling stay where they are.

### The Pages panel

- [ ] `PageEditor::getPagesProperty()`: every page grouped by type, ordered by title, with
      the current one flagged. Types read from `PageTypeRegistry` so a site with none sees
      one flat list.
- [ ] A third icon-rail button and its panel, with a client-side search over the list.
- [ ] Links use `wire:navigate` and the current page renders as text rather than a link.

### Following links in the preview

- [ ] The preview script moves from `atelier::layouts.site` into `PreviewController`,
      injected before `</body>`. Removed from the two example layouts.
- [ ] The script intercepts every `<a>` click in the preview and posts the href up.
- [ ] `PageEditor::openPath(string $path)`: resolve, then redirect to that page's builder
      with `navigate: true`, or tell the browser to open the URL in a new tab.
- [ ] A page that is a draft resolves for the editor even though the public route would
      404 it, because editing an unpublished page is the normal case here.

### Drag to reorder

- [ ] `PageEditor::reorder(array $ids)`, rejecting a list that is not a permutation of the
      current tree, the same guard `reorderTree()` uses.
- [ ] SortableJS on the section list, borrowed from Filament's bundle the way the menu
      manager borrows it: one Alpine component per list, `forceFallback`,
      `fallbackOnBody`, and a handle so a click still selects.
- [ ] The up and down buttons stay.

### Quality bar

- [ ] `npm run build` in the same commit as the panel view changes, per `CLAUDE.md`.
- [ ] PRD criterion 3 marked met, and 14's Gate A item struck through with the date.
- [ ] `Docs/installation.md`: the layouts guide stops telling people to copy the preview
      script, since it is injected now.

## Done when

- [ ] Clicking a page in the Pages panel opens it in the builder, with the address bar
      following, and the section list belonging to the page on screen.
- [ ] Clicking a link inside the preview opens that page in the builder. A link Atelier
      does not own opens in a new tab and the builder does not move.
- [ ] A section dragged from the bottom of a twelve-section page to the top lands there,
      in one gesture, with the preview refreshed once.
- [ ] A host layout with no preview script still selects sections on click and follows
      links, which is criterion 2 (the preview matching the page) holding for a layout the
      package has never seen.
