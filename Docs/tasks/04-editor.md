# 04. Three-pane editor

> **Status, 18 Aug 2026.** Built and working. Two deliberate divergences from this file:
> the editor autosaves, so there is no Save button and no unsaved-changes warning, and
> reorder is up and down buttons rather than drag. Drag is the one worth coming back to.

## What it is

The product. A custom Filament page that replaces the default page edit screen with three panes:

- **Left:** the section list. Drag to reorder, add, duplicate, hide, delete.
- **Middle:** the live preview from feature 01.
- **Right:** the settings for whichever section is selected, built from that block's `schema()`.

Plus a toolbar carrying the width switcher, the language switcher, and Save and Publish.

## Why we're building it

The default Filament approach, and Fabricator's, is a vertical stack of collapsible forms. On a twelve-section page you're scrolling through accordions trying to remember which "Hero" is the one near the pricing table. The three-pane layout separates structure (left) from detail (right) from result (middle), which is how every editor that non-technical people actually enjoy is built.

## How it should feel

Direct. Click a section on the left, its settings are on the right and it's visible in the middle. Drag a section up, the page reorders in front of you. Nothing takes a page reload, nothing loses your place.

Hiding a section should feel safe, as the reversible alternative to deleting. Duplicating should feel like the fastest way to build a page, because it is.

The left pane should read like the page: section names that say what they are, not "Block 4". Where a block has a heading, use it as the label.

## In the dashboard

Pages → open a page. The three-pane editor is the edit screen.

- **Add section:** button at the bottom of the left list (and between sections on hover), opens the block picker grouped by category.
- **Reorder:** drag the handle on a section row.
- **Duplicate / hide / delete:** per-row menu on the right of each section row.
- **Edit:** click the row, settings load on the right.
- **Width:** desktop / tablet / mobile buttons in the toolbar.
- **Language:** EN / AR toggle in the toolbar (feature 05).
- **Save** keeps the draft. **Publish** puts it live (feature 06). They are separate buttons and that distinction has to be obvious.

## Tasks

### Layout

- [~] Custom Filament page registered as the page edit screen on our own `PageResource`. It is a standalone panel page at `/atelier/{record}`, reached from a button on the page settings screen, not the resource's edit route. The split turned out to be right: settings, slugs and SEO belong on a form, not in the builder chrome. Worth writing into the resource so a row click lands in the builder.
- [ ] Three-pane responsive layout. Decide and document what happens under about 1024px: the honest answer is that this editor is a desktop tool. The layout has no breakpoints and nothing is documented, so on a narrow screen the panes just squeeze.
- [~] Toolbar: width switcher, language switcher, Save, Publish, a link to view the live page. Width, language, status badge, open-preview and Publish are there. No Save, by design, because every change persists to the draft immediately. No view-live link; that one lives on the settings screen and should be here too.

### Section list

- [x] Render the section list from the draft block tree.
- [!] Drag to reorder, persisting to `draft_content` and refreshing the preview. Shipped as up and down buttons (`move($id, $offset)`). It persists and refreshes correctly, but "drag a section up, the page reorders in front of you" is the feel this file asked for and buttons are not it.
- [x] Add section: picker grouped by `category()`, with icons and labels.
- [ ] Insert at a chosen position, not only at the end. `addBlock()` appends. Adding a section to the middle of a twelve-section page means adding it at the bottom and clicking up eleven times.
- [x] Duplicate a section, with a fresh block `id`.
- [x] Hide a section: excluded from the public render, still visible and marked in the editor.
- [x] Delete with a confirm, since this one isn't reversible without revisions. Still not reversible: revisions are not built (06).
- [x] Smart row labels from the block's heading where one exists, falling back to the block label.

### Settings pane

- [x] Build the form from the selected block's `schema()`.
- [ ] Inject the shared controls the block declares in `supports()`. Nothing reads `supports()` (03).
- [x] Mark which fields are reactive, so only those trigger a preview refresh. Each block marks its own fields `->live(debounce: 400)`.
- [x] Keep unsaved state when switching between sections. There is no unsaved state: every change writes the draft.

### State

- [x] All edits write to `draft_content` only.
- [!] Warn on navigating away with unsaved changes. Not applicable as built. The editor autosaves on every change, so there is never unsaved work to lose. Worth stating on the screen, because a client who cannot find a Save button assumes their work is not saved.
- [ ] Handle two people editing the same page, or decide explicitly not to and say so here. Undecided, and autosave makes it sharper than it would otherwise be: two editors on one page silently overwrite each other with no conflict and no warning. Decide before a client has two people in the panel.

## Done when

- A non-technical user builds a multi-section page and publishes it without touching code (PRD criterion 1).
- Reordering by drag updates the preview and persists (PRD criterion 3).
- Every action above works without a full editor reload.

## Deliberately not in v1

Clicking a section in the preview to select it. It's the natural next step and it's listed in the PRD as v2. The left list covers the same need for now.
