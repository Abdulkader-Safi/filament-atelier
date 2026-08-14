# Filament Atelier

> Packagist: `safi/filament-atelier` (vendor `safi` is already Safi's, it publishes `safi/laralcn-ui`).
> GitHub: `Abdulkader-Safi/filament-atelier`.
> Name checked 2026-08-14. `safi/filament-atelier` is free. One near-collision: `blackpig-creatif/atelier`, "artisanal content blocks for FilamentPHP v5", 40 downloads total, 0 stars, last release April 2026. Small enough to ignore, worth knowing if this goes public.

A Laravel + Filament plugin that turns a client's Laravel app into a visual CMS. The client builds pages from sections in a three-pane editor and watches the real page update as they type. A dsrpt developer adds block types in code. Safi can add extra blocks after handover by pasting HTML and CSS. The public site is server-rendered, bilingual (English + Arabic), and SEO-ready.

Built to be dsrpt's own tool for client websites. One install per site.

## Status

PRD rewritten 2026-08-14 (v2) after the editor decision changed. Awaiting sign-off. Nothing built.

Safi is building this in a separate session and directory. These files are the handoff.

## Files

- `prd.md` — the current PRD (v2). Start here. Covers the four decisions taken on 14 Aug, the data model changes, the admin-created-blocks design, the preview refresh problem, the 11-step plan, and 4 open questions.
- `architecture.md` — the technical design from 22 June. Still correct on the block tree, registry, CSS strategy and SEO. Superseded on the editor, locales and slugs, see the note at its top.
- `research/filament-plugin-development.md` — building a Filament plugin, the Builder field, custom fields and pages, assets, and a maturity read on existing packages.
- `research/gutenberg-block-model.md` — how Gutenberg blocks work, what to copy and what to avoid.
- `research/elementor-architecture.md` — how Elementor is built and what to borrow.
- `research/preview-drafts-gsap-seo.md` — preview patterns, draft/publish/revisions, GSAP in Livewire, SEO and Core Web Vitals.
- `quick-win-options.md` — from 28 June, which off-the-shelf option to ship a client on while this is being built. Partly stale, it predates FilamentCraft.
- `claude-code-fabricator-setup-prompt.md` — paste-ready Claude Code prompt to set up Fabricator on a client app. Still usable for a quick client job, unrelated to this build.

## The core idea in one line

Store the page as a JSON tree of typed blocks, render each block with a Blade view at request time, and show that render live in an iframe while the client edits.

## Decisions taken (14 Aug 2026)

- Three-pane editor in v1: section list left, live iframe centre, settings right. Not the Builder field, and not deferred to v3.
- Single site per install. No multi-tenancy.
- Bilingual from day one, English + Arabic with RTL.
- First target is the next new dsrpt client site, so the block library is a generic marketing set.

## Prior art worth knowing

`FilamentCraft` (filamentcraft.dev) already does everything above, including designing new section types in the browser, and sells one-time from about $74. Safi's reason for building anyway is dsrpt owning and extending the tool. If that stops being true, the build stops being worth it.

## Build order

Prototype the live preview loop first: one page, two hardcoded blocks, three-pane layout, debounced iframe refresh. It's the reason for the rewrite and the only genuinely hard part. Everything else is known work.
