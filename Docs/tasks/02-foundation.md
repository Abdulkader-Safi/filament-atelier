# 02. Foundation

## What it is

The plumbing everything else sits on: the composer package, the panel plugin registration, the database tables, the `Page` model, the block registry, and the design tokens shared by the editor and the front end.

Built on `Z3d0X/filament-fabricator` for the page model, slug resolution and front-end routing. We don't rewrite page routing.

## Why we're building it

Every feature after this one assumes a page exists, has a draft tree and a published tree, resolves at a URL, and can look up a block type by key. Getting the tables wrong here means a migration across every client site later, which is why the locale decision was forced early.

Design tokens are here rather than later for one reason: if the editor and the front end read different sources for colour and spacing, the preview lies, and feature 01 was pointless.

## How it should feel

Invisible to the client. For a dsrpt developer, installing this on a fresh Laravel app should be four commands and a panel registration, with no manual wiring of routes or views. If installation needs a page of instructions, it's wrong.

## In the dashboard

A "Pages" resource appears in the panel nav after install. Nothing else. The three-pane editor replaces its edit screen in feature 04, so at this stage the resource can stay Fabricator's default.

## Tasks

### Package

- [ ] Composer package `safi/filament-atelier`, PSR-4 autoload, MIT or private per open question 1.
- [ ] Constraint `^4.0|^5.0` on Filament unless open question 2 settles on v5 only.
- [ ] `AtelierPlugin` implementing Filament's `Plugin` contract, registered in the panel.
- [ ] Service provider using Filament v4's provider conventions (see `research/filament-plugin-development.md`, the provider changed in v4).
- [ ] Publishable config, migrations and views.
- [ ] `php artisan filament:assets` wiring for the editor's JS and CSS via `FilamentAsset`.
- [ ] Install command that does the whole setup in one step.

### Tables

- [ ] `pages`: title, status, `draft_content` json, `published_content` json, layout, `preview_token`, `published_at`, timestamps.
- [ ] `page_slugs`: `page_id`, locale, slug, unique on (locale, slug).
- [ ] `page_revisions`: `page_id`, content json, `created_by`, label, `created_at`.
- [ ] `Page` model with the slug relationship and casts.
- [ ] Route resolution for `/{slug}` reading `published_content` only.

### Registry

- [ ] `BlockRegistry` as a container binding, mapping type key to class.
- [ ] Registration API a developer calls from a service provider.
- [ ] Registry lookup used by the renderer, the section picker and the settings pane.
- [ ] `schema_version` per block type, stored on each block, so a future attribute change is a data migration and not a runtime chain.

### Design tokens

- [ ] Token config: palette, type scale, spacing scale, layout widths.
- [ ] Emit as CSS custom properties, loaded by both the public layout and the preview.
- [ ] Blocks reference tokens as `{ "token": "color.primary" }`, never literals.

## Done when

- `composer require` plus one artisan command on a clean Laravel app gives a working Pages resource and a page rendering at its slug.
- A block type registered in a test service provider is retrievable from the registry.
- Changing a token value changes both the public page and the preview.

## Notes

Fabricator gives us the page resource, layout resolution and routing. Where our needs diverge (the `page_slugs` table, the two content columns), extend rather than fork if possible, so upstream Filament version bumps stay cheap.
