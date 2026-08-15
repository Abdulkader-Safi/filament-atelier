# Filament Atelier

> Packagist: `safi/filament-atelier` (vendor `safi` is already Safi's, it publishes `safi/laralcn-ui`).
> GitHub: `Abdulkader-Safi/filament-atelier`.
> Name checked 2026-08-14. `safi/filament-atelier` is free. One near-collision: `blackpig-creatif/atelier`, "artisanal content blocks for FilamentPHP v5", 40 downloads total, 0 stars, last release April 2026. Small enough to ignore, worth knowing if this goes public.

A Laravel + Filament plugin that turns a client's Laravel app into a visual CMS. A dsrpt developer defines the block types in code, one PHP class and one Blade view each. The client then builds pages from those blocks in a three-pane editor, adding, reordering and editing sections while watching the real page render update as they type. The public site is server-rendered, bilingual (English + Arabic), and SEO-ready.

Built to be dsrpt's own tool for client websites. One install per site.

## Status

PRD rewritten 2026-08-14 (v2) after the editor decision changed, then re-scoped the same day: block types are code-defined in v1, authoring block types from the panel moves to v2. Fabricator dropped 2026-08-15, so Atelier is built from scratch.

The build is starting. `example/` is a fresh Laravel 13 app for testing, the plugin goes in `packages/filament-atelier`.

## Files

- `tasks/` — one markdown file per feature, in build order, each covering what it is, why it exists, how it should feel, how it works in the panel, and its checklist. `tasks/README.md` is the index. This is where the work gets tracked.
- `installation.md` — putting Atelier on a real project, writing a block, and the MVP's known limits.
- `prd.md` — the current PRD (v2). Start here. Covers the five decisions taken on 14 Aug, the data model changes, the block interface, the two preview problems, the 10-step plan, and 4 open questions.
- `architecture.md` — the technical design from 22 June. Still correct on the block tree, registry, CSS strategy and SEO. Superseded on the editor, locales and slugs, see the note at its top.
- `research/filament-plugin-development.md` — building a Filament plugin, the Builder field, custom fields and pages, assets, and a maturity read on existing packages.
- `research/gutenberg-block-model.md` — how Gutenberg blocks work, what to copy and what to avoid.
- `research/elementor-architecture.md` — how Elementor is built and what to borrow.
- `research/preview-drafts-gsap-seo.md` — preview patterns, draft/publish/revisions, GSAP in Livewire, SEO and Core Web Vitals.
- `quick-win-options.md` — from 28 June, which off-the-shelf option to ship a client on while this is being built. Stale for this build: it recommends Fabricator, which we dropped on 15 Aug. Still useful if a client needs shipping on something off-the-shelf.

## The core idea in one line

Store the page as a JSON tree of typed blocks, render each block with a Blade view at request time, and show that same render live in an iframe while the client edits.

## Decisions taken (14 to 15 Aug 2026)

- Three-pane editor in v1: section list left, live iframe centre, settings right. Not the Builder field, and not deferred to v3.
- Block types are code-defined in v1: a PHP class plus a Blade view, registered at boot. Authoring block types from the panel, Gutenberg-style, is v2.
- The preview must be the real page: public layout, public stylesheet, and a desktop/tablet/mobile width switcher, so the client can see wrapping and overflow before publishing.
- Single site per install. No multi-tenancy.
- Bilingual from day one, English + Arabic with RTL.
- First target is the next new dsrpt client site, so the block library is a generic marketing set.
- Built from scratch, no Fabricator. Not because Fabricator lacks Filament 5 support (it has it) but because our spec replaces four of the five things it provides. 15 Aug.

## What v1 is and isn't

**Is:** code-defined sections with a visual editor on top. Developer writes the blocks, client arranges and fills them, and sees the result as they work.

**Isn't:** a tool for creating new block types from the browser. That's the phase after, and the research for it sits at the bottom of `prd.md`.

## Prior art worth knowing

`FilamentCraft` (filamentcraft.dev) already does everything above, including designing new section types in the browser, and sells one-time from about $74. Safi's reason for building anyway is dsrpt owning and extending the tool. If that stops being true, the build stops being worth it.

## Build order

The full breakdown is in `tasks/`, ten files in order. The short version:

Prototype the live preview loop first: one page, two hardcoded blocks, three-pane layout, debounced iframe refresh against the real front-end stylesheet. It's the reason for the rewrite and the only genuinely hard part. Everything else is known work.
