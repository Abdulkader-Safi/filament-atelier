# Changelog

Notable changes to Atelier. Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
versioning follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

While the version is below 1.0.0, a minor bump can carry a breaking change. Anything that
breaks is called out under **Breaking** with what to do about it.

## [Unreleased]

### Fixed

- **`Page::children()` was written in SQL that only some databases accept.** The nesting
  query used `replace(slug, "/", "")` inside a `whereRaw`. Double quotes are string
  literals in SQLite, and in MySQL only while `ANSI_QUOTES` is off; everywhere else, and on
  Postgres always, `"/"` is a column name. So a `CollectionPage` and the `ItemList` schema
  it builds would have thrown a syntax error rather than rendering. Single-quoted now,
  which all three accept. It went unnoticed because the test suite runs on SQLite.

### Removed

- **`preview.debounce` in `config/atelier.php`.** Nothing has ever read it. The real
  debounce is `->live(debounce: 400)` on each field in a block's schema, which is where a
  block author changes it. Setting the key did nothing, so removing it changes nothing;
  your published config keeps the key until you delete it.

### Changed

- Internal tidying with no behaviour change: the menu manager's five mutations share one
  sibling lookup instead of repeating the top-level-versus-child branch, and
  `StructuredData`'s `areaServed()` and `places()` delegate to `list()` rather than
  restating it.

## [0.3.6] - 2026-09-03

Two editor fixes. No migration, no config change. `composer update
safi/filament-atelier` and `php artisan filament:assets`, which re-publishes the panel
stylesheet.

### Fixed

- **Deleting a row from a repeater field did not stick.** The row disappeared, the preview
  kept it, and it was back on the next load. The editor wrote form changes into the block
  tree from `updatedData()`, a Livewire hook that fires on a field update from the browser and
  nothing else. A Filament action writes component state on the server instead, so deleting a
  repeater row, adding one or reordering them changed the form and never reached the tree.

  The sync now also runs on `dehydrate()`, at the end of every request, after any action has
  run. That covers every action a block schema brings with it rather than one hook per field,
  and it only writes when the tree actually moved, so a click or a locale switch does not fire
  a pointless save. If a row you deleted before this release is still on the page, delete it
  again and it will stay gone.

- **The Add section picker still ran off the bottom of the screen.** 0.3.4 capped its height
  with a Tailwind class and 0.3.5 shipped the stylesheet that class needed, but a panel that
  had not re-published its assets was still uncapped, and the cap alone did not stop a tall
  list from stretching the whole page. The cap is now an inline style, so it cannot be lost to
  a stale stylesheet, and the panel clips rather than growing past the viewport.

### Changed

- **The section picker is capped at two thirds of the screen** rather than 45%, and the
  section list behind it stays visible.

## [0.3.5] - 2026-09-01

Ships the stylesheet 0.3.4 should have shipped. `composer update safi/filament-atelier` is the
whole upgrade.

### Fixed

- **The Add section picker still would not scroll on 0.3.4.** The fix was real but half of it
  never left this repository. Atelier compiles its panel CSS ahead of time into the package so
  a client installs with no build step, Tailwind only emits the classes it finds in the views
  at build time, and 0.3.4 changed a view without rebuilding. So `max-h-[45vh]` was in the
  markup and nowhere in the stylesheet, leaving the list uncapped and unscrollable exactly as
  before. Searching worked throughout because that part is Alpine, not CSS.

  Same rebuild also brings in the search input's focus ring and placeholder colour.

## [0.3.4] - 2026-09-01

The section picker, on a site with a lot of block types. No migration, no config change.
`composer update safi/filament-atelier` is the whole upgrade.

### Fixed

- **The Add section picker ran off the bottom of the panel with no way to scroll it.** The
  list had no height of its own, so past roughly twenty block types everything below the fold
  was unreachable: the editor is a fixed-height layout that clips rather than scrolls, and
  the panel's own scroll belongs to the section list above. The picker now caps at 45vh and
  scrolls inside itself.

### Added

- **A search box on the picker.** It opens focused, so adding a section is now type a word,
  click the block. Typing filters by label and hides a category once nothing in it matches,
  with a line saying so when nothing matches at all. Escape clears the box, then closes the
  picker.

## [0.3.3] - 2026-08-31

A menu manager, behind an experimental flag that is off by default, and a fix for a crash
that only showed up next to Shield.

**This release adds a table**, used only if you turn the menu manager on. Publishing and
migrating is still the safe order:

```bash
composer update safi/filament-atelier
php artisan vendor:publish --tag=filament-atelier-migrations
php artisan migrate
```

Leaving the flag off changes nothing about an existing site. No new page, no sidebar entry,
no route.

### Added

- **A menu manager, experimental and off by default.** Named navigation menus per location,
  edited from the panel and rendered on the public site with `Menu::treeFor()`. Drag and drop
  reorders, nests a top-level item under another and promotes one back out. Labels and URLs
  are per locale, an item can point at an Atelier page or anything else you register as a
  `MenuSource`, and an item can be hidden without deleting it.

  Locations come from config, so a theme decides that it has a header and a footer rather
  than a client inventing menu names. One row per location, holding the whole tree as JSON.
  `src/Models/Menu.php` says why that beats a nested-set table here.

  Turn it on in `config/atelier.php`:

  ```php
  'experimental' => [
      'menus' => true,
  ],
  ```

  Or per panel, which overrides config rather than adding to it:

  ```php
  AtelierPlugin::make()->experimental(['menus' => true])
  ```

  It is experimental because it is a real page in a client's sidebar the moment it is on, and
  it has not been through a client project yet. Off means the page and its route are never
  registered, not just hidden. The [Menus guide](https://github.com/Abdulkader-Safi/filament-atelier/wiki/Menus)
  covers the rest.

### Fixed

- **Opening a Shield role wrecked the whole screen if Atelier was installed.** Shield builds
  its role form by instantiating every registered page class and asking each one for its
  title. `PageEditor` only receives its record in `mount()`, so an instance Shield made by
  hand hit an uninitialised typed property and threw, taking the role edit screen with it.
  `getTitle()` now answers without a record.

## [0.3.2] - 2026-08-25

A bug fix. No migration, no config change, no new table. `composer update
safi/filament-atelier` is the whole upgrade.

### Fixed

- **The preview iframe kept showing the old locale after switching languages.** Its refresh
  fetch read a preview URL cached in the Alpine component at page load rather than the
  iframe's current `src`. Livewire moves the iframe's `src` to the new locale on every
  render; the cached copy never followed, so each refresh re-fetched the old locale and
  painted it back over the iframe. The fetch now reads the iframe's own `src`.

- **The same locale switch could also fail the preview request outright, independent of the
  fix above.** `setLocale()` called `persist()`, which writes the block tree, unchanged,
  there is nothing to save, and dispatches `atelier-refresh`, firing a second fetch at the
  exact preview URL the iframe's `src` change was already navigating to. Both requests carry
  the same session cookie and race each other, and the loser can fail. `setLocale()` no
  longer calls `persist()`; the iframe's own navigation is enough.

## [0.3.1] - 2026-08-20

A bug fix. No migration, no config change, no new table. `composer update
safi/filament-atelier` is the whole upgrade.

### Fixed

- **Removing an image in the page editor did not save the removal.** The field cleared, but
  the block tree kept the old path, so the preview still showed the image and publishing put
  it back on the live page. Filament removes a file through a renderless method that writes
  component state directly, so the editor's `updatedData()` hook never ran and the cleared
  value never reached the tree. The upload field now hooks the removal itself.

  If a page already has an image you thought you deleted, open the section, remove it again
  and it will stick this time.

- **Removed and replaced images were never deleted from the disk.** Filament only deletes a
  stored file when `deleteUploadedFileUsing` is set, and it was not, so every image you
  removed or replaced stayed on the disk with nothing referencing it. New removals now delete
  the file. Files orphaned before this release are still there and need clearing by hand.

## [0.3.0] - 2026-08-18

Structured data. Every page now emits a JSON-LD graph describing the site, the page and what
the page is about, from three sources: a settings screen, a page type select, and blocks that
describe themselves.

It also fixes a routing bug that has been there since the first release and is worth reading
even if none of the rest interests you.

**This release adds a table and a column.** After updating:

```bash
composer update safi/filament-atelier
php artisan vendor:publish --tag=filament-atelier-migrations
php artisan migrate
```

Nothing else is required. A site that fills in none of the new screens still emits a valid
graph, because everything derivable is derived.

### Fixed

- **Atelier's catch-all was shadowing the host app's own routes.** The package registered its
  routes as its provider booted, which happens before Laravel loads `routes/web.php`, and
  Laravel matches in registration order. So an app's own `/blog/{slug}` lost to Atelier's
  `/{locale}/{slug?}` and 404'd, which is the opposite of what this package documents and
  promises. Routes now register from a `booted()` callback, after every provider, so the
  catch-all really is last. Route caching still works.

  If you worked around this by moving routes into a service provider, renaming a path, or
  putting your blog on a third segment, you can undo it after upgrading.

### Added

- **A JSON-LD graph on every public page.** One `<script type="application/ld+json">` holding
  the `Organization` (or the LocalBusiness subtype you pick), the `WebSite`, this `WebPage`
  with its language and dates, a `BreadcrumbList` derived from nested slugs, `ImageObject`
  nodes for the logo and share image, and whatever the page is about.

  Nodes reference each other by `@id` rather than repeating the organisation on each one.
  Nothing is stored: the graph is built at render from data you already filled in, so there
  is no second copy to keep in step and no cache to clear. Empty values never appear, so a
  page with nothing filled in is still valid rather than a node full of nulls. The Arabic
  page describes the same organisation in Arabic, pointing at the same `@id`. Previews emit
  nothing, being `noindex` anyway.

  The encoding is a security boundary rather than formatting: a client typing `</script>`
  into a meta title cannot close the block, and Arabic stays readable rather than becoming
  `\uXXXX` escapes.

- **A Site details screen**, under a Settings group in the panel. The organisation behind the
  site: name and description (translatable), legal name, logo, type, social profiles, and for
  a business with premises its address, geo, price range, areas served, **opening hours** and
  **contact points**, plus founding date, employee count, VAT and tax numbers.

  It is a screen rather than config on purpose. Tokens, locales and layouts are a developer's
  decisions and belong in a file. An address is client-owned data that changes without a
  deploy, and the person who knows it does not have a text editor open.

  Hours are one row per set, listing the days that share them, which is both what schema.org
  wants and how a person thinks about them. A contact point says who answers and in which
  language, which a bare telephone number does not.

- **A page type select**, on the page settings screen under Structured data: standard page,
  about, contact, listing, article, service, product, event, person or job vacancy. Choosing
  one reveals the few fields it needs and nothing else, and none of them repeat a field the
  page already has, since the name and description come from the meta fields, the image from
  the share image and the dates from publishing.

  Page-shaped types refine the `WebPage` node itself, because an About page *is* a web page.
  Thing-shaped types become their own node linked through `mainEntity`, because a page about
  a product is not a product. The type is page-level, not per locale: a page that is a
  Service in English is a Service in Arabic.

  Defaults do the work where they can. An Article with no author credits the organisation. An
  Event defaults to going ahead and in person, so cancelling is one select and a cancelled
  event stops advertising itself. A vacancy dates itself from the publish date and locates
  itself from the site address, and marking it remote sets `jobLocationType`, without which a
  remote role is filtered out of remote searches. A listing page emits an `ItemList` of the
  pages directly under it, derived from the slug path, so a services index stays right when a
  service is added.

- **FAQ schema, two ways.** The FAQ block turns its questions into `FAQPage` automatically,
  with nothing typed twice: two FAQ blocks on one page merge into a single node, a question
  with no answer is dropped, and a hidden section contributes nothing.

  Questions can also be **typed** under Structured data, per locale, on a page built from
  anything at all. That is not a fallback: on a site whose blocks you wrote yourself, a custom
  FAQ section has no schema unless somebody remembered to add it, and nobody should edit a PHP
  class to get an FAQ into the head. Typed entries win over derived ones. Note that Google
  expects FAQ data to correspond to something a visitor can see, so this is for content on the
  page in another form, not for questions that appear nowhere.

  Breadcrumbs take a mode in the same place: from the slug path (the default), typed by hand
  for a page whose slug is not its hierarchy, or off.

- **Blocks can describe themselves.** `structuredData($attributes, $locale, $url)` on a block
  returns nodes for the page's graph, and a node whose `@id` is already there merges into it.
  The attributes arrive collapsed to the locale with tokens resolved, exactly as the view
  receives them, so the schema cannot describe something different from what rendered. Adding
  a block that contributes schema needs no change inside the plugin.

- **Your own routes can share the graph.** A blog post or a services record lives on your
  route, in your view, and Atelier never sees it, but it should not have to reinvent the
  organisation:

  ```blade
  @include('atelier::partials.schema', ['nodes' => [[
      '@type' => 'Article',
      '@id' => url()->current().'#article',
      'headline' => $post->title,
      'publisher' => ['@id' => \Safi\Atelier\Schema\StructuredData::siteId('organization')],
  ]]])
  ```

  It emits the `Organization` and `WebSite` nodes plus whatever you pass, with the same
  pruning, merging and escaping. The publisher is then the same node the rest of the site
  points at rather than a copy that drifts the first time a phone number changes. Pair it with
  `->sitemap([...])` and a blog is in the sitemap and in the graph without Atelier knowing it
  exists.

### Deliberately not included

- **`Review` and `AggregateRating` from testimonials.** Google ignores reviews a business
  publishes about itself, and the block has no rating field to aggregate.
- **`HowTo`.** Its rich results were dropped in September 2023, so it is markup for nobody.
- **`WebSite` `SearchAction`.** The sitelinks search box was deprecated in November 2024.

## [0.2.0] - 2026-08-18

Multiple layouts, picked per page. A minor rather than a patch because it adds a real
capability and a new registration method, not because anything breaks: no migration, and
every existing page keeps rendering through `config('atelier.layout')` untouched.

### Added

- **Multiple layouts, chosen per page.** A site is rarely one shell: marketing pages want a
  navbar and footer, documentation wants a sidebar, a landing page often wants neither. The
  blocks are the same either way, so the shell is now a per-page choice. Register them where
  you register blocks:

  ```php
  AtelierPlugin::make()
      ->blocks(DefaultBlocks::all())
      ->layouts([
          'site' => ['label' => 'Navbar and footer', 'view' => 'layouts.site'],
          'docs' => ['label' => 'Sidebar', 'view' => 'layouts.docs'],
      ]);
  ```

  A **Layout** select appears on the page settings screen, hidden entirely when no layouts
  are registered. A layout is a key, a label and a view rather than a class, because a class
  holding three strings is ceremony. The choice is page-level, not per locale: a layout is
  structure, and both locales share one structure by design. The `layout` column already
  existed, so there is no migration.

  An unset layout falls back to `config('atelier.layout')`, and so does a key nobody
  registered, because a page keeps its key after a developer removes that layout and every
  public page 500ing is a bad way to find out.

### Fixed

- The preview never passed `$page` to the layout, so a custom layout reading it worked on
  the live site and 500'd in the preview. Found by the test asserting a preview renders
  through the same layout as the public page.

## [0.1.6] - 2026-08-18

Makes the sitemap readable in a browser. No migration, no API change.

### Added

- A stylesheet on the sitemap. Opening `/sitemap.xml` in a browser used to show a wall of
  unlabelled URLs and timestamps, which reads as broken. It now renders as a table of URLs,
  dates and locale alternates. Crawlers ignore the instruction that points at it, so
  nothing about the machine-readable side changes.

## [0.1.5] - 2026-08-18

The SEO release: a sitemap, `robots.txt`, per-page noindex, and 301s when a slug changes.

**This release adds a table.** Run
`php artisan vendor:publish --tag=filament-atelier-migrations` and `php artisan migrate`
after updating, or renaming a slug fails on a missing `atelier_page_redirects`.

### Added

- **A sitemap at `/sitemap.xml`**, both locales, with `xhtml:link` alternates per entry and
  `lastmod` from `published_at`, which the table already stored and nothing read. Drafts and
  noindexed pages are excluded. Written rather than pulled in: `spatie/laravel-sitemap`
  exists to crawl a site and discover URLs, and Atelier already knows every URL it has.
- **Sitemap sources for pages Atelier does not own.** A client site usually has a blog or a
  services resource with its own model, panel tab and routes, and those URLs belong in the
  same sitemap. `AtelierPlugin::make()->sitemap([...])` takes closures or invokable class
  names, the same shape as `->blocks()`. Each returns URL strings, or arrays with `lastmod`
  and per-locale `alternates`. Sources run at request time, entries dedupe on `loc`, and a
  source that throws fails the sitemap rather than silently shrinking it.
- **A `robots.txt`** pointing at the sitemap and disallowing the preview route and the panel.
  Note that Laravel ships a real `public/robots.txt`, and the web server answers that before
  any route runs, so delete it or copy the `Sitemap:` line into it.
- **Per-page, per-locale `noindex` and `nofollow` toggles.** One switch keeps a page out of
  search results and out of the sitemap. The tag is emitted only when it says something,
  since `index, follow` is what every crawler assumes anyway.
- **Redirects when a slug changes.** Renaming a published page used to 404 every inbound
  link to it, silently and permanently. Changing a slug now writes a 301 from the old one,
  and the public route consults those before it gives up. The redirect stores the page
  rather than a target slug, so a page renamed twice sends both old URLs to wherever it
  lives now, with no chain to follow. An unpublished target still 404s, because redirecting
  someone to a 404 is worse than the 404. **Needs `vendor:publish
  --tag=filament-atelier-migrations` and `migrate`.**

## [0.1.4] - 2026-08-18

The head no longer disappears when an app supplies its own layout, and the README stops
promising features that do not exist. No migration.

**If you point `atelier.layout` at your own Blade view, this release needs two lines from
you.** Nothing breaks without them, which is the problem: add them or your pages keep
rendering with no title, description, canonical, hreflang or Open Graph tags.

```blade
<head>
    @include('atelier::partials.meta')

    @vite(['resources/css/app.css'])

    {{-- After your stylesheet, so the tokens win. --}}
    @include('atelier::partials.tokens')
</head>
```

Apps on the stock layout need no change.

### Added

- `atelier::partials.meta` and `atelier::partials.tokens`. A host app that points
  `atelier.layout` at its own Blade view keeps the full head with two `@include` lines,
  documented in the README, `Docs/installation.md` and the
  [wiki](https://github.com/Abdulkader-Safi/filament-atelier/wiki/Installation). Tokens are
  a second include rather than part of the first, because they have to sit after the host
  app's stylesheet while the meta does not care where it goes.

### Fixed

- **Replacing the layout silently lost every meta tag.** Title, description, canonical,
  hreflang and Open Graph lived inside `atelier::layouts.site`, so any app with its own
  shell rendered pages with none of them, and previews stopped being `noindex`. Three
  tests, one of which asserts the stock layout and a host app's layout emit the same head.
- The social share image upload was missing `->visibility('public')`. It worked on a local
  disk and 403'd on S3, so the tag pointed at an image no crawler could read.

### Changed

- The README described features that do not exist: GSAP animations, a sitemap, JSON-LD,
  per-block asset loading, and header, footer, contact form and raw HTML blocks. It also
  said Filament v4 while the package requires `^5.0`. It now lists what ships and,
  separately, what does not.
- The README points at the [wiki](https://github.com/Abdulkader-Safi/filament-atelier/wiki)
  for documentation. `Docs/` is export-ignored, so nobody installing the package could read
  it, and it was never written for using Atelier: it is the PRD, the task breakdown and the
  research behind the decisions.
- **Animation is no longer a plugin feature.** It belongs to whoever writes the block: a
  block is already your PHP class and your Blade view, so it animates however you like and
  Atelier ships no GSAP dependency and no preset contract. The cost, stated plainly, is
  that there is no animation dropdown for the client. `Docs/tasks/08-animation.md` is
  marked dropped and keeps its Livewire guidance, which is still right for a block author.

## [0.1.3] - 2026-08-18

Repository hygiene, not package code. Nothing in `src/` changed, so there is no migration
and no upgrade step beyond `composer update`.

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

[unreleased]: https://github.com/Abdulkader-Safi/filament-atelier/compare/v0.3.6...HEAD
[0.3.6]: https://github.com/Abdulkader-Safi/filament-atelier/compare/v0.3.5...v0.3.6
[0.3.5]: https://github.com/Abdulkader-Safi/filament-atelier/compare/v0.3.4...v0.3.5
[0.3.4]: https://github.com/Abdulkader-Safi/filament-atelier/compare/v0.3.3...v0.3.4
[0.3.3]: https://github.com/Abdulkader-Safi/filament-atelier/compare/v0.3.2...v0.3.3
[0.3.2]: https://github.com/Abdulkader-Safi/filament-atelier/compare/v0.3.1...v0.3.2
[0.3.1]: https://github.com/Abdulkader-Safi/filament-atelier/compare/v0.3.0...v0.3.1
[0.3.0]: https://github.com/Abdulkader-Safi/filament-atelier/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/Abdulkader-Safi/filament-atelier/compare/v0.1.6...v0.2.0
[0.1.6]: https://github.com/Abdulkader-Safi/filament-atelier/compare/v0.1.5...v0.1.6
[0.1.5]: https://github.com/Abdulkader-Safi/filament-atelier/compare/v0.1.4...v0.1.5
[0.1.4]: https://github.com/Abdulkader-Safi/filament-atelier/compare/v0.1.3...v0.1.4
[0.1.3]: https://github.com/Abdulkader-Safi/filament-atelier/compare/v0.1.2...v0.1.3
[0.1.2]: https://github.com/Abdulkader-Safi/filament-atelier/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/Abdulkader-Safi/filament-atelier/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/Abdulkader-Safi/filament-atelier/releases/tag/v0.1.0
