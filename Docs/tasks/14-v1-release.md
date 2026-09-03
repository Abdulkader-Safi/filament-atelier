# 14. Release 1.0.0

> **Written 3 Sep 2026, against the code at 0.3.6.** Audited against the repository, not
> against memory. Nothing here is built. This file is the gate list between 0.3.6 and a
> `v1.0.0` tag, and several of its items are decisions rather than code.

## What it is

The work between the current 0.3.6 and a 1.0.0 tag. It splits into two bars that get
confused with each other:

**The product bar.** The eleven live success criteria in `prd.md`. Three of them are not
met and one is met only behind a flag that ships off.

**The package bar.** What `1.0.0` promises to a stranger who runs `composer require`. A
named public API, a support matrix that CI actually tests, a suite that runs from a clone
of the package, and documentation that is true. None of that is about features.

A 1.0.0 can be tagged with a small feature set. It cannot be tagged with an unnamed API.

## Why we're building it

Below 1.0.0 a minor release may break anything, and the changelog says so. That freedom is
worth having while the shape moves, and it is worth losing once client projects depend on
the package. Right now the package is public on Packagist under MIT with a public
repository and an install guide that tells people to use it on client work. It is being
treated as stable by everyone except its version number.

The cost of tagging 1.0.0 too early is one specific thing: a rename inside `Block`,
`AtelierPlugin` or the config keys becomes a 2.0.0 instead of a footnote. So the API gets
named and reviewed once, before the tag, rather than discovered by a consumer after it.

## How it should feel

Someone who has never spoken to Safi installs the package from the README, registers it,
writes a block, and ships a client site. They never open an issue asking which classes
they are allowed to call, and nothing in a 1.x update breaks the block they wrote.

The 1.0.0 test is not that everything in the PRD is built. It is that what is built is
named, tested, documented truthfully, and will not move under them.

## In the dashboard

Two client-visible decisions, and nothing else:

- **The menu manager's experimental flag.** Criterion 12 is a v1 criterion satisfied by a
  feature that ships off by default. Either the flag goes and Menus appears in every
  panel, or the criterion moves to 1.1.
- **Whether a raw-HTML block exists in the picker.** It is named in `CLAUDE.md` as the v1
  escape hatch for one-off markup, and it is not built.

## The gates

### Gate A: decide what 1.0.0 contains

Cheapest work here is subtraction. Every item is a decision first and code second.

- [ ] **Criterion 3, drag to reorder.** Not met. Reordering is up and down buttons, and a
      new section always lands at the end. Build it, or amend the criterion and move drag
      to 1.1 with the limitation stated in the README, which already says it.
- [ ] **The v1 block set.** `prd.md` scopes thirteen: header, hero, features, logo wall,
      testimonials, CTA, FAQ, rich text, image, gallery, contact form, footer, raw HTML.
      Nine ship. Missing: header, footer, contact form, raw HTML.
      - Header and footer are arguably answered by layouts plus the menu manager. If so,
        strike them from the PRD with a dated banner rather than leaving them owed.
      - Contact form is blocked by open question 4 and has been since August.
      - Raw HTML is the escape hatch named in `CLAUDE.md`'s non-negotiables. It is the one
        of the four that is genuinely missing rather than arguably covered, and it is an
        afternoon.
- [ ] **Criterion 11, Core Web Vitals.** Never measured. Run 9 in [10](10-verification.md)
      can run today. Do that before deciding anything about feature 09: the numbers may
      show the whole performance feature is unnecessary for 1.0.
- [ ] **Conditional per-block assets** (09). A `CLAUDE.md` non-negotiable that does not
      exist, and the cost of the delay is already paid: nine blocks to retrofit instead of
      zero. If run 9's numbers are green, the honest move is to write down that the
      non-negotiable was not met, why it did not bite (no block ships its own CSS today),
      and what the trigger is for building it. Leaving it silently unbuilt is the bad
      option.
- [ ] **Criterion 12, menus.** Decide the flag, per "In the dashboard" above.
- [ ] **Close the four open questions in `prd.md`.** Two are already answered by facts and
      only need writing down: the Filament floor is `^5.0` and has been since the composer
      constraint was set, and distribution is public, MIT, on Packagist. Ownership
      (question 1) is a business decision that a 1.0.0 tag makes look permanent. The
      contact form (question 4) blocks a block.

### Gate A2: the bugs the audit turned up

Found on 3 Sep 2026 during a simplification pass over `src/`. Each one is a 1.0.0
consideration in its own right.

- [ ] **The home page has two URLs and the wrong one is canonical.** `PageController`
      serves the page whose slug is `home` at `/`, but `Page::url()` has no case for it, so
      the canonical tag, the hreflang alternates, the sitemap entry and every menu item
      built from that page point at `/home`. `/home` resolves to the same content through
      the catch-all. Duplicate content with the canonical pointing at the copy, on a
      package whose pitch is SEO. Fix `url()` to return `url('/')` for the default slug,
      and decide whether `/home` should 301 to `/`. The `'home'` slug is also hardcoded in
      the controller rather than configurable, which is worth settling in the same change.
- [x] **`Page::children()` used double-quoted string literals in a `whereRaw`.** Fixed
      3 Sep 2026. `replace(slug, "/", "")` parses as a column name on Postgres and on MySQL
      with `ANSI_QUOTES`, so every `CollectionPage` render and its `ItemList` schema would
      have 500'd there. It passed only because the test suite runs on SQLite. Worth a
      second look for the same pattern elsewhere, and worth asking whether CI should run
      the suite against Postgres before a 1.0 that claims to be a normal Laravel package.
- [ ] **Link fields have no scheme check.** `cta_url` on the hero and CTA blocks, and a
      menu item's per-locale URL, go straight into an `href`. Blade escaping does not stop
      `javascript:`. It sits on the same trust boundary as `RichTextBlock`'s `{!! $body !!}`,
      which is inherent to a rich editor, but an allowlist on a plain URL field is cheap and
      matches the care already taken in `Graph::toJson()`.
- [ ] **Revisions are an API with no UI.** Written and pruned on every publish,
      `restoreRevision()` exists and is tested, and nothing in the panel lists or restores
      one. Wire it into `EditPageSettings`, or say so in the docs. The current
      documentation says both things in different places.
- [ ] **`SitemapController::stylesheet()` does not check `file_get_contents`.** Fine today
      because the file ships, but a missing file returns 200 with an empty body and a PHP
      warning instead of anything diagnosable.
- [ ] **Six public methods have no caller anywhere**: `LayoutRegistry::has()`, `all()` and
      `descriptions()`, `MenuRegistry::label()`, `SitemapRegistry::all()`,
      `SiteSettings::forget()`. `descriptions()` looks written to feed option descriptions
      on the layout select in `PageResource`, which never picks it up. 1.0 is the moment to
      keep them deliberately or delete them, because after the tag they are frozen.
- [ ] **`FaqBlock::structuredData()` and `StructuredData::faq()` build the same Question and
      Answer list twice.** Sharing it needs a new public static, which is exactly the kind
      of decision that belongs before the API freeze rather than after.

### Gate B: name the public API

The whole meaning of the version number. Nothing here is hard, and none of it is done.

- [ ] Write the public surface down, in the wiki and in `docs.md`: `AtelierPlugin` and its
      builder methods, the `Block` contract, `BaseBlock`, `BlockRegistry`,
      `LayoutRegistry`, `SitemapRegistry`, `MenuRegistry` and `MenuSource`, `Media`,
      `Tokens`, `Menu::treeFor()`, `StructuredData::siteId()`, the models, every key in
      `config/atelier.php`, the migration table names, and the view names a consumer is
      told to include (`atelier::partials.schema`, `atelier::partials.meta`,
      `atelier::partials.tokens`, `atelier::layouts.site`).
- [ ] Say what is internal, and therefore free to change in a 1.x: everything under
      `src/Filament/`, `src/Http/`, `Renderer`, `SharedControls`, the block classes'
      internals, and the compiled stylesheet.
- [ ] Review the named surface once, deliberately, for anything that should be renamed
      before it is frozen.
- [ ] State the support matrix and make it match the composer constraints. `composer.json`
      promises `illuminate/contracts ^12.0|^13.0`; nothing tests Laravel 12. Either test it
      or narrow the constraint to `^13.0`.

### Gate C: tests and CI

- [ ] **The package has no test suite of its own.** `tests/` at the root is empty, while
      `composer.json` declares the `Safi\Atelier\Tests\` namespace and requires
      `orchestra/testbench` and `pest`. `composer test` and `composer analyse` both fail
      from a clone. The 168 tests live in `example/`, which is export-ignored. Either move
      the suite to testbench at the root, or delete the unused dev dependencies and point
      the root scripts at `example/` so the documented commands work.
- [ ] **`phpstan` never runs.** No `phpstan.neon` exists, larastan is required and unused,
      and CI has no analysis job. Add the config and the job, or drop both.
- [ ] **CI tests one point in the matrix**: PHP 8.4, and whatever Laravel 13 and Filament 5
      resolve to that morning, because no lockfile is committed. The PHP 8.3 floor job
      checks parsing and installability, which is the right shape. Add the same treatment
      for the framework constraint, or narrow the constraint per Gate B.
- [ ] **CI does not catch the stylesheet trap.** Touching a panel Blade view without
      running `npm run build` ships a class that exists in the markup and in no stylesheet,
      silently, on every client site. It happened in 0.3.4 and again in 0.3.5. A job that
      runs the build and fails if `resources/dist/atelier.css` differs from the committed
      file removes the whole class of bug for about ten lines of YAML. Do this one first.
      The committed stylesheet matches a fresh build today (checked 3 Sep 2026), so the
      job is purely preventative and lands green.
- [ ] **The suite only ever runs on SQLite.** That is how a `whereRaw` with double-quoted
      string literals survived in `Page::children()` until 3 Sep 2026: it is valid SQLite
      and valid MySQL by default, and a syntax error on Postgres. A package that installs
      into anyone's Laravel app should run its suite against at least Postgres before
      promising 1.0.
- [ ] Cover what has no test and would be expensive to get wrong: slug resolution for
      nested paths, draft pages on the public route, the signed preview link's expiry and
      signature, and `Media` upload handling.

### Gate D: make the documentation true

Three surfaces describe this plugin: the wiki, `docs.md` in the package, and the README.
[02](02-foundation.md) already flags that they drift. They have.

- [ ] **`Docs/installation.md`'s "Known limits" is wrong on four of six lines.** It says
      there is no sitemap and no JSON-LD (both shipped), that snapshots are not stored
      (they are), and describes a contact block that does not exist. The README links it as
      the thing to read before promising a client anything.
- [ ] **The test count is stale everywhere.** `CLAUDE.md` says 26, [10](10-verification.md)
      says 29. It is 168.
- [ ] **The wiki's Home page still says v0.1.0** and its Usage page still teaches
      `data-atelier-block` by hand. Carried over from 02.
- [ ] An upgrade page, or `UPGRADE.md`, holding the whole dance in one place:
      `composer update`, `vendor:publish --tag=filament-atelier-migrations`, `migrate`,
      `filament:assets`. Two of the last three releases needed the asset step and it lived
      only in a changelog entry.
- [ ] **`SECURITY.md`'s supported-versions table still lists only `0.1.x`.** It needs the
      current line now and a `1.x` line at the tag, plus a decision on whether 1.x gets
      backported fixes or whether upgrading stays the fix path.
- [ ] `CONTRIBUTING.md` and issue templates, if the package is genuinely public. Skip both
      if question 1 lands on internal.

### Gate E: the verification runs

[10](10-verification.md) is the gate, and none of its nine runs has been done. Two of them
need a person who is not Safi, which is the point of them. Runs 7 and 9 were blocked when
that file was written and are not blocked now: the sitemap and the JSON-LD graph shipped in
0.2.0 and 0.3.0.

- [ ] Runs 1 and 2, the two that need someone else. Book the people before anything else in
      this gate, because they are the long-lead item.
- [ ] Runs 3, 4, 5, 6 by Safi.
- [ ] Run 7, now unblocked. Rich Results Test on a page with an FAQ block.
- [ ] Run 9, now unblocked. Lighthouse on both locales, numbers written into
      [09](09-performance.md).
- [ ] Write down every criterion consciously accepted as unmet, with the reason, in
      [10](10-verification.md). A criterion quietly dropped is the failure mode here.

### Gate F: security and simplification

- [x] A security pass over the rendering surface, done 3 Sep 2026: block views, JSON-LD
      encoding, the signed preview route, slug resolution, sitemap leakage, panel
      authorization, uploads, and dynamic resolution from stored data. Verdict: nothing
      lets an anonymous visitor read a draft, escape a route, or reach code execution. Two
      findings, both needing a panel account, both below.

  What came back clean, so it does not need re-litigating: `Graph::toJson()` encodes with
  `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`, so a `</script>` typed
  into a meta title or an FAQ answer cannot break out of the JSON-LD block. `PageController`
  reads `published()` only and 404s an unpublished page. `Sitemap::pageUrls()` filters on
  published status and drops noindexed locales. `PreviewController` is behind
  `signed:relative` and sets both `X-Robots-Tag` and the robots meta. No user-controlled
  string reaches `view()`, `app()` or a callable: block views come from the registry keyed
  by a developer-registered class, `LayoutRegistry::view()` returns null for an unknown key,
  and menu sources are checked with `is_subclass_of`. The one `Blade::render()` in
  `Renderer.php` uses a literal template with the user string passed as escaped data, so the
  standing ban holds.

- [ ] **Guard the URI scheme on link fields.** Medium severity, the one to fix first.
      `cta_url` on the hero and CTA blocks and a menu item's URL go straight into an `href`
      in `blocks/hero.blade.php`, `blocks/cta.blade.php` and `partials/menu-items.blade.php`.
      Blade escaping stops attribute breakout and does nothing about the scheme, so
      `javascript:` survives into the published page and runs for any visitor who clicks
      the button. Through a menu item it lands on every page at once. This is an
      inconsistency rather than a decision: `LogoWallBlock`'s link field and every URL on
      Site details already validate. Fix it once where the value is rendered, not per view,
      so a block author cannot miss it: a helper next to `Media::url()` that returns the
      value only for a null, `http`, `https`, `mailto` or `tel` scheme. Do not use
      Laravel's `url` validation rule on the field, because it rejects `/about`, which is
      the common case.

- [ ] **Make `{!! $body !!}` in `blocks/rich-text.blade.php` safe in Atelier rather than in
      a dependency.** It is safe today only because Filament's `RichEditorStateCast`
      round-trips the value through TipTap, which rebuilds it from registered nodes with
      the text escaped. That protection covers the form path and nothing else.
      `PageEditor::$tree` is a plain public Livewire property with no `#[Locked]`, so a
      panel user can `$wire.set('tree', ...)` with a raw payload and then call any public
      mutator, which persists it verbatim. The same hole opens with no tampering at all
      whenever `draft_content` is written by something that is not the editor: a content
      import, a seeder, or a revision restored from before the state cast existed. Sanitise
      at render, in `Renderer::normalise()` or in the view, and the claim in the docs that
      block views escape by default becomes true of Atelier. Consider `#[Locked]` on `$tree`
      in the same change.

  Note the interaction with Gate F's authorization item. "A panel user can publish arbitrary
  content" is out of scope in `SECURITY.md`, and that is the right call, but it is only the
  right call while every panel user is equally trusted. The moment Atelier grows a way to
  give someone page-editing rights and not full trust, both findings above change severity.
- [x] A simplification pass over `src/`, done 3 Sep 2026, before the API is frozen rather
      than after. 169 tests before and after, Pint clean. What it changed is in the
      changelog; what it found is Gate A2 above.
- [ ] **Decide what panel authorization Atelier offers.** Nothing in `src/Filament/`
      declares a `canAccess()` or checks a policy, so every screen the plugin adds is
      reachable by any user who can reach the panel at all. That is a defensible default,
      since a page editor is a page editor, but it is currently undocumented and a host app
      with several roles has no hook to narrow it. Either add the hooks or write down that
      access is panel-wide, before 1.0 makes the answer a promise.

## Tasks

The order that wastes the least time:

1. The CSS build check in CI. Ten lines, removes a shipped-broken class of bug.
2. Run 9 and run 7. Cheap, and run 9's numbers decide Gate A's performance question.
3. Gate A's decisions, all of them, in one sitting. Amend `prd.md` with dated banners.
4. Gate D, the documentation corrections. They are all small and all currently misleading.
5. Gate B, the public API list. This is the one that has to be right.
6. Gate C's test and tooling work, sized by what Gate B's support matrix promises.
7. Whatever Gate A decided to build.
8. Gate E's remaining runs, with runs 1 and 2 booked early.
9. Tag `v1.0.0`, and start the changelog's stability promise from there.

## Done when

- Every live PRD criterion is either met or explicitly retired with a dated banner and a
  reason, and [10](10-verification.md) records the result of all nine runs.
- The public API is written down, reviewed once, and matches what CI tests.
- `composer test` and `composer analyse` work from a clone of the package, or the scripts
  and dev dependencies that promise they do are gone.
- CI fails when a Blade view changes without its stylesheet.
- The wiki, `docs.md`, the README and `Docs/installation.md` agree with each other and with
  the code.
- The four open questions in `prd.md` are closed.
