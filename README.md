# Filament Atelier

A visual page builder for Laravel, built as a Filament plugin.

[![Watch the demo: Laravel + FilamentPHP, my new page builder project](https://img.youtube.com/vi/vBIgCQhKWfc/maxresdefault.jpg)](https://www.youtube.com/watch?v=vBIgCQhKWfc)

A developer defines the sections in code. The client builds pages from them in a full-screen editor, and sees the real page render as they type. The public site stays server-rendered Blade, bilingual, and fast.

## Status

MVP, and installable. Blocks, the builder, page settings, SEO and public pages all work. See `Docs/installation.md` to put it on a project, and the limits at the bottom of that file before you promise anything to a client.

The package is the repository root. `example/` is a Laravel 13 app that installs it for testing, and `Docs/` holds the spec. Both are export-ignored, so `composer require` pulls the package and nothing else.

## The idea

A page is stored as a JSON tree of typed blocks and rendered by Blade at request time. That's Gutenberg's data model with Elementor's rendering approach, and it avoids the parts of both that hurt: no HTML stored in the database, no block validation, no deprecation chains.

A block type is one PHP class and one Blade view. The class declares its key, label, icon and category, plus a Filament schema that becomes its settings form. Register it and it shows up in the section picker. Adding a block never means editing a file inside the plugin.

The editor puts the section list on the left and a live iframe beside it. Selecting a section swaps the list for that section's settings. The iframe loads the public layout and the public stylesheet, so what the client sees is what ships. There's a width switcher for desktop, tablet and mobile, because the point of a preview is catching a headline that wraps onto three lines before anyone else does.

## Install

[![Packagist](https://img.shields.io/packagist/v/safi/filament-atelier.svg)](https://packagist.org/packages/safi/filament-atelier)
[![License](https://img.shields.io/packagist/l/safi/filament-atelier.svg)](LICENSE.md)

```bash
composer require safi/filament-atelier
php artisan vendor:publish --tag=filament-atelier-migrations
php artisan migrate && php artisan storage:link
```

Register `AtelierPlugin::make()->blocks(DefaultBlocks::all())` in your panel, and point Tailwind at the package's views. Full steps, including the one that fails silently if you skip it, are in `Docs/installation.md`.

### Upgrading

New tables ship as new migration files rather than as edits to one that already ran on your database, so re-publish after every update:

```bash
composer update safi/filament-atelier
php artisan vendor:publish --tag=filament-atelier-migrations
php artisan migrate
```

`vendor:publish` skips files you already have, so this only ever copies what's new and never overwrites a migration you've edited. Skipping it after an update that adds a table shows up as a "no such table" error the first time that feature runs. `CHANGELOG.md` says when a release needs it.

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

MIT. See `LICENSE.md`.

The MIT license covers Atelier's own code. It does not extend to dependencies, and GSAP in particular carries its own terms (see the note above).
