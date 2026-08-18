# 02. Foundation

> **Status, 18 Aug 2026.** Mostly shipped. Open: the install command, per-page layout
> resolution, registerable layouts, and `schema_version`. Design tokens and
> `page_revisions` landed on 18 Aug.

## What it is

The plumbing everything else sits on: the composer package, the panel plugin registration, the database tables, the `Page` model, slug resolution, front-end routing, layout resolution, the block registry, and the design tokens shared by the editor and the front end.

No page-builder dependency. This feature grew on 15 Aug when Fabricator was dropped, and the page model, routing and layout resolution became ours. The reasoning is in `prd.md` under "Why not Fabricator".

## Why we're building it

Every feature after this one assumes a page exists, has a draft tree and a published tree, resolves at a URL, and can look up a block type by key. Getting the tables wrong here means a migration across every client site later, which is why the locale decision was forced early.

Design tokens are here rather than later for one reason: if the editor and the front end read different sources for colour and spacing, the preview lies, and feature 01 was pointless.

## How it should feel

Invisible to the client. For a dsrpt developer, installing this on a fresh Laravel app should be four commands and a panel registration, with no manual wiring of routes or views. If installation needs a page of instructions, it's wrong.

## In the dashboard

A "Pages" resource appears in the panel nav after install: list, create, delete, and a plain edit form. Nothing else. The three-pane editor replaces the edit screen in feature 04, so at this stage a stock Filament resource is enough.

## Tasks

### Package

- [x] Composer package `safi/filament-atelier`, PSR-4 autoload, MIT or private per open question 1. Public and MIT.
- [!] Constraint `^4.0|^5.0` on Filament unless open question 2 settles on v5 only. Settled on `^5.0` only. Open question 2 is closed by the composer.json.
- [x] `AtelierPlugin` implementing Filament's `Plugin` contract, registered in the panel.
- [x] Service provider using Filament v4's provider conventions (see `research/filament-plugin-development.md`, the provider changed in v4). Built on `spatie/laravel-package-tools`.
- [x] Publishable config, migrations and views.
- [~] `php artisan filament:assets` wiring for the editor's JS and CSS via `FilamentAsset`. CSS only. The editor's JS is an inline Alpine component in the Blade view, which is fine while it is one `Alpine.data` block and wrong the moment it is two.
- [ ] Install command that does the whole setup in one step. `Docs/installation.md` is five manual steps, one of which (the Tailwind `@source` line) fails silently when skipped. This is the step that most deserves the command.

### Tables

- [~] `pages`: title, status, `draft_content` json, `published_content` json, layout, `preview_token`, `published_at`, timestamps. All present except `preview_token`, plus a `seo` json column. `layout` is stored and never read.
- [x] `page_slugs`: `page_id`, locale, slug, unique on (locale, slug).
- [x] `page_revisions`: `page_id`, content json, `created_by`, label, `created_at`. Shipped 18 Aug 2026 as its own migration, so an existing install adds it by publishing migrations again rather than by editing one that already ran.
- [x] `Page` model with the slug relationship and casts.

### Routing and page resolution

Previously Fabricator's job. Ours now.

- [x] Catch-all front-end route for `/{slug}` and `/{locale}/{slug}`, registered last so it never shadows app routes.
- [x] Slug resolution against `page_slugs`, returning 404 on a miss.
- [x] Nested slugs (`/services/web-design`) decided one way or the other now, not retrofitted. **Decided: supported.** A slug is the whole path after the locale, stored as one string in `page_slugs`, so `services/web-design` is a row like any other and needs no parent relationship. Until 18 Aug 2026 the controller read the first segment as a locale and discarded the rest, which served `/services` with a 200 rather than a 404. Fixed, with two tests.
- [x] Public controller reads `published_content` only, never the draft.
- [ ] Layout resolution: the page's `layout` value picks the Blade layout wrapping the blocks. The column exists, nothing reads it. Both controllers use the single `config('atelier.layout')`.
- [ ] Layouts are registerable by the host app, the same way blocks are, so a client site defines its own shell. There is no layout registry. Swapping `atelier.layout` is the only lever, and doing so loses the whole SEO head, which is why 11 pulls the head into a partial.
- [x] A stock `PageResource` for list, create and delete, plus a settings screen for title, slugs and SEO.
- [x] Route caching works. Test with `route:cache`, since a catch-all plus a database lookup is where this usually breaks. Verified 18 Aug 2026.

### Registry

- [x] `BlockRegistry` as a container binding, mapping type key to class.
- [x] Registration API a developer calls from a service provider. `AtelierPlugin::make()->blocks([...])` in the panel provider.
- [x] Registry lookup used by the renderer, the section picker and the settings pane.
- [ ] `schema_version` per block type, stored on each block, so a future attribute change is a data migration and not a runtime chain. Nothing stamps a version. Every tree written so far is unversioned, so the first attribute rename has no way to tell old data from new.

### Design tokens

Built 18 Aug 2026 as `Safi\Atelier\Tokens`. Defaults live in PHP and `atelier.tokens`
overrides them group key by group key, so an install that predates the config key still
gets a full palette and overriding one colour does not mean restating the rest.

- [x] Token config: palette, type scale, spacing scale, layout widths. Colour, font, space
      and width groups. Deliberately small: a token nothing renders is a token nobody
      maintains, so the set grows when a block needs one.
- [x] Emit as CSS custom properties, loaded by both the public layout and the preview.
      Inline in the head, after the stylesheet so they win, and under a kilobyte. The
      preview and the public page share the layout, so they cannot drift.
- [x] Blocks reference tokens as `{ "token": "color.primary" }`, never literals. The
      renderer resolves them into `var(--atelier-color-primary)` before the view runs, so
      a block author never learns tokens exist.

The RTL font swap rides on `[dir="rtl"]` rather than a locale code, which closes 05's
Arabic font stack item and covers a third RTL language for free.

## Done when

- `composer require` plus one artisan command on the `example/` app gives a working Pages resource and a page rendering at its slug in both locales.
- The catch-all route resolves published pages, 404s on unknown slugs, and doesn't shadow the app's own routes or the Filament panel.
- A block type registered in a test service provider is retrievable from the registry.
- Changing a token value changes both the public page and the preview.

## Notes

Everything here is ours, so Filament version tracking is ours too. Keep the surface that touches Filament internals small and in as few files as possible, because that surface is what breaks on a major version bump.

Read `Docs/research/filament-plugin-development.md` before starting. The service provider conventions changed in Filament v4 and the plugin contract has a specific shape.
