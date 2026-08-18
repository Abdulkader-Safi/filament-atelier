# Changelog

Notable changes to Atelier. Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
versioning follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

While the version is below 1.0.0, a minor bump can carry a breaking change. Anything that
breaks is called out under **Breaking** with what to do about it.

## [Unreleased]

### Added

- `SECURITY.md`, and GitHub private vulnerability reporting enabled on the repository, so
  a researcher has somewhere to send a report that is not a public issue.
- CI. The suite runs on PHP 8.4 against the example app on every push and pull request, and
  Pint checks formatting. A separate job holds the `^8.3` floor honest: the suite cannot run
  there because Pest requires 8.4, so it checks the two halves the constraint actually
  promises, that the package parses on 8.3 and that a consumer on 8.3 can install it.
  Third-party actions are pinned to full commit SHAs rather than tags, because a tag can be
  re-pointed at malicious code by whoever controls the action.
- `.github/dependabot.yml`, covering the workflows with a seven-day cooldown, so an update
  is never merged the moment it is published.

### Changed

- The test suite no longer needs a built front end. Tests assert on rendered HTML, never on
  the asset bundle, so `withoutVite()` lets the suite run on a clean checkout instead of
  failing on a missing Vite manifest.

## [0.1.2] - 2026-08-18

Design tokens, shared section controls and page revisions. Three of the four gaps that
were holding up other features, plus one routing bug that served the wrong page.

**This release adds a table.** Run
`php artisan vendor:publish --tag=filament-atelier-migrations` and `php artisan migrate`
after updating, or `atelier_page_revisions` is missing and publishing a page fails.

### Added

- Design tokens. Colour, font, spacing and width are emitted as CSS custom properties into
  the head of both the public page and the editor preview, so the two cannot drift.
  Defaults ship in `Safi\Atelier\Tokens` and `atelier.tokens` overrides them key by key,
  which means an existing install picks them up without republishing its config. A block
  attribute stored as `{"token": "color.primary"}` is resolved to
  `var(--atelier-color-primary)` before the view runs.
- An Arabic font stack, swapped in by a `[dir="rtl"]` rule rather than by locale code.
- Shared section controls. A block declares `supports()` and gets those controls in its
  settings pane, built once rather than reimplemented per block. Background and vertical
  space ship now. Every control emits an inline style built from tokens, never a utility
  class, because a class written in PHP is a class the host app's Tailwind never scans.
  Leaving a control unset keeps the block's own styling.
- Page revisions. Every publish snapshots the tree that went live into
  `atelier_page_revisions` with who published it, pruned to `atelier.revisions.keep`
  (20 by default). `Page::restoreRevision()` copies one back into the draft, deliberately
  not into the live page, so restoring is an undo the editor reviews and publishes.
  No panel UI yet. The table ships as its own migration rather than as a change to one
  that already ran, so it needs the publish and migrate above.
- An Unpublish action on the page settings screen. `Page::unpublish()` existed and nothing
  called it, so taking a page down meant editing the database.
- `Docs/tasks/11-seo-v0.2.md`, the SEO work planned for v0.2.0: sitemap, `robots.txt`,
  JSON-LD, per-page indexing control, slug redirects, and the head markup fixes.

### Changed

- Block views take a `$shared` attribute bag from the renderer and merge their own classes
  into it. A custom block's root element should now be
  `<section {{ $shared->class([...]) }}>` rather than writing `data-atelier-block` itself.
  Existing custom blocks keep working; they just do not get the shared controls.
- Every file in `Docs/tasks/` audited against the code and marked with what is actually
  built. `Docs/tasks/README.md` carries the status of each feature and the gaps that block
  more than their own feature.
- The contact form block is settled as presentational: it posts to a route the developer
  wires per site, and the app's own code already handles storing the submission. Atelier
  owns no submissions table, so it never duplicates what the site already has.
- Upgrading is documented in the README, `Docs/installation.md` and the wiki, because a
  missed `vendor:publish` fails late rather than loudly.

### Fixed

- A nested slug such as `services/web-design` was unreachable. The public route read the
  first path segment as a locale and discarded the rest, so the URL returned 200 with the
  `services` page rendered instead of the one asked for. A slug is now the whole path
  after the locale.

### Known issues

- Every meta tag lives inside `atelier::layouts.site`. An app that points
  `config('atelier.layout')` at its own view gets no title, description, canonical,
  hreflang or Open Graph tags.
- The social share image field is missing `visibility('public')`, so it works on a local
  disk and breaks on S3.

## [0.1.1] - 2026-08-15

### Fixed

- Creating a page from the panel failed with `table atelier_pages has no column named
  slugs`. The settings form edits slugs as `slugs.{locale}`, which arrives as a top-level
  `slugs` key; the edit screen stripped it before saving and the create action did not.
  Both paths now share one trait, and creating a page with no slug typed generates one
  from the title rather than leaving the page unreachable.

### Changed

- Docs point at Packagist now that the package is published.

## [0.1.0] - 2026-08-15

First release. Installable from Packagist as `safi/filament-atelier`.

A page is a JSON tree of typed blocks rendered by Blade at request time. The editor
preview and the public page run through the same views and the same stylesheet, which is
the reason the plugin exists.

### Added

**The editor**

- Three-pane builder: section list, live preview, settings pane, on a full-screen shell.
- Live preview in an iframe rendering the draft through the public layout. On change the
  canvas contents are swapped rather than the iframe reloaded, so scroll position survives
  and there is no flash. A twelve-section render measured 16ms mean, 31ms worst of ten.
- Add, duplicate, hide, delete and move sections. Rows are labelled by the block's own
  heading where it has one.
- Width switcher for desktop, tablet and mobile, at fixed widths rather than the pane's.
- Language switcher. The settings pane edits the selected locale and leaves the other
  locale's values alone.
- Every change writes the draft immediately. There is no Save button.

**Blocks**

- A block type is one PHP class plus one Blade view, declaring `type()`, `label()`,
  `icon()`, `category()`, `schema()`, `supports()`, `translatable()` and `view()`.
  Registering one takes a line in the panel provider and no change inside the plugin.
- Nine blocks: hero, features, logo wall, testimonials, CTA, FAQ, rich text, image,
  gallery.
- `BlockRegistry` resolves types for the renderer, the picker and the settings pane.
- An unknown block type renders nothing publicly and a visible placeholder in the editor,
  rather than throwing.

**Pages and SEO**

- Page settings: title, and a tab per locale holding slug, meta title, meta description,
  social share image and canonical.
- Public routes at `/{slug}` and `/{locale}/{slug}`, registered last so an app's own
  routes still win. Route caching works.
- Canonical, hreflang, Open Graph and Twitter tags in the head. Meta title falls back to
  the page title.

**Bilingual**

- Translatable attributes hold a per-locale map inside one tree, so both languages share
  one section order.
- A missing translation falls back to the default locale rather than rendering a hole.
- Arabic renders with `dir="rtl"` and `lang="ar"`, and the blocks use no physical
  direction utilities.

**Draft and publish**

- `draft_content` and `published_content` as separate columns. Editing writes the draft,
  publishing copies it across, and the public route reads published only. A draft 404s.
- Status badge covering draft, published, and published with unpublished changes.
- Shareable preview link: signed, expiring, `noindex` by meta tag and header, and never
  cached.

**Packaging**

- Composer package installing into a Laravel 12 or 13 app on Filament 5.
- Publishable config, migrations and views, and a compiled stylesheet registered through
  `FilamentAsset`, so a consumer needs no build step for the panel.
- A demo seeder creating Home, About and a draft Contact in both languages.

### Fixed

- Uploaded images never reached the disk. The editor read the raw Livewire state instead
  of the form's dehydrated state, so `FileUpload` never moved the temporary file: the
  field said the upload was complete, the tree stored `[]`, and the page showed no image.
- Rich text was corrupted on save. The same raw-state read wrote the editor's TipTap
  document into the block tree, and the Blade view then tried to echo an array.

### Breaking

- The package moved from `packages/filament-atelier/` to the repository root. Composer and
  Packagist read `composer.json` from the root, and nothing could install it from a
  subdirectory.

[unreleased]: https://github.com/Abdulkader-Safi/filament-atelier/compare/v0.1.2...HEAD
[0.1.2]: https://github.com/Abdulkader-Safi/filament-atelier/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/Abdulkader-Safi/filament-atelier/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/Abdulkader-Safi/filament-atelier/releases/tag/v0.1.0
