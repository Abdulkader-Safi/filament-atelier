# 04. Three-pane editor

## What it is

The product. A custom Filament page that replaces the default page edit screen with three panes:

- **Left:** the section list. Drag to reorder, add, duplicate, hide, delete.
- **Middle:** the live preview from feature 01.
- **Right:** the settings for whichever section is selected, built from that block's `schema()`.

Plus a toolbar carrying the width switcher, the language switcher, and Save and Publish.

## Why we're building it

Fabricator's editing screen is a vertical stack of collapsible forms. On a twelve-section page you're scrolling through accordions trying to remember which "Hero" is the one near the pricing table. The three-pane layout separates structure (left) from detail (right) from result (middle), which is how every editor that non-technical people actually enjoy is built.

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

- [ ] Custom Filament page registered as the page edit screen, replacing Fabricator's default.
- [ ] Three-pane responsive layout. Decide and document what happens under about 1024px: the honest answer is that this editor is a desktop tool.
- [ ] Toolbar: width switcher, language switcher, Save, Publish, a link to view the live page.

### Section list

- [ ] Render the section list from the draft block tree.
- [ ] Drag to reorder, persisting to `draft_content` and refreshing the preview.
- [ ] Add section: picker grouped by `category()`, with icons and labels.
- [ ] Insert at a chosen position, not only at the end.
- [ ] Duplicate a section, with a fresh block `id`.
- [ ] Hide a section: excluded from the public render, still visible and marked in the editor.
- [ ] Delete with a confirm, since this one isn't reversible without revisions.
- [ ] Smart row labels from the block's heading where one exists, falling back to the block label.

### Settings pane

- [ ] Build the form from the selected block's `schema()`.
- [ ] Inject the shared controls the block declares in `supports()`.
- [ ] Mark which fields are reactive, so only those trigger a preview refresh.
- [ ] Keep unsaved state when switching between sections.

### State

- [ ] All edits write to `draft_content` only.
- [ ] Warn on navigating away with unsaved changes.
- [ ] Handle two people editing the same page, or decide explicitly not to and say so here.

## Done when

- A non-technical user builds a multi-section page and publishes it without touching code (PRD criterion 1).
- Reordering by drag updates the preview and persists (PRD criterion 3).
- Every action above works without a full editor reload.

## Deliberately not in v1

Clicking a section in the preview to select it. It's the natural next step and it's listed in the PRD as v2. The left list covers the same need for now.
