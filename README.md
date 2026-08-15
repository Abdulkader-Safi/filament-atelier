# Filament Atelier

A visual page builder for Laravel, built as a Filament plugin.

A developer defines the sections in code. The client builds pages from them in a three-pane editor, and sees the real page render as they type. The public site stays server-rendered Blade, bilingual, and fast.

## Status

Specification stage. There is no code in this repository yet.

The PRD is written and awaiting sign-off. Everything here is under `Docs/`. When the build starts, it starts with the live preview loop, because that's the only part with real unknowns.

## The idea

A page is stored as a JSON tree of typed blocks and rendered by Blade at request time. That's Gutenberg's data model with Elementor's rendering approach, and it avoids the parts of both that hurt: no HTML stored in the database, no block validation, no deprecation chains.

A block type is one PHP class and one Blade view. The class declares its key, label, icon and category, plus a Filament schema that becomes its settings form. Register it and it shows up in the section picker. Adding a block never means editing a file inside the plugin.

The editor puts the section list on the left, a live iframe in the middle, and the selected section's settings on the right. The iframe loads the public layout and the public stylesheet, so what the client sees is what ships. There's a width switcher for desktop, tablet and mobile, because the point of a preview is catching a headline that wraps onto three lines before anyone else does.

## What v1 covers

- Three-pane editor: add, reorder, duplicate, hide and delete sections, with live preview
- A marketing block set: header, hero, features, logo wall, testimonials, CTA, FAQ, rich text, image, gallery, contact form, footer, plus raw HTML as an escape hatch
- English and Arabic on every page, with `dir="rtl"` and hreflang
- Draft and published as separate columns, so editing never touches the live page, plus revision snapshots and shareable signed preview links
- Per-locale SEO fields, JSON-LD, and a sitemap covering both locales
- Scroll animations picked from a dropdown, backed by GSAP
- Design tokens shared by the editor and the front end
- Per-block asset loading and a Core Web Vitals budget

Block types are defined in code in v1. Creating new block types from the panel is planned for v2.

## Built on

Laravel 12/13, Filament v4, Livewire 4, Alpine 3, Tailwind 4, GSAP 3.15.

No page-builder dependency. Atelier owns the page model, slug resolution, routing and rendering, so there's no third-party page abstraction to work around or wait on. The reasoning is in `Docs/prd.md` under "Why not Fabricator".

## Documentation

Everything lives in `Docs/`, and later documents override earlier ones.

- `Docs/prd.md` is the current spec and the authority
- `Docs/tasks/` is the work breakdown, one file per feature in build order
- `Docs/architecture.md` is the technical design, partly superseded, with a banner saying which parts
- `Docs/research/` holds background briefs on Gutenberg, Elementor, Filament plugin development, and preview/drafts/animation/SEO

Start with `Docs/overview.md` if you want the short version.

## A note on GSAP

GSAP has been free for commercial use since April 2025, including the plugins that used to be behind Club GreenSock. It is not MIT and it is not open source. It ships fine in client projects, but don't describe it as open source.

## Licence

Not settled yet. Ownership and distribution are open questions at the bottom of `Docs/prd.md` and will be decided before the first line of code.
