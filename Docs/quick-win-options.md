# Filament content options: the quick win

> **Scope note, 2026-08-15.** This document is about shipping a client fast on an off-the-shelf package. For that purpose its recommendation still stands, and its Filament 5 compatibility note on Fabricator was verified correct on 15 Aug 2026 (Fabricator 4.x supports Filament ^5, actively maintained). What no longer holds is the last section's claim that Fabricator is the foundation under Atelier. Atelier is built from scratch. See `prd.md` under "Why not Fabricator".

> Research for the client we need live this week, while the custom page builder is still being built.
> Verified against filamentphp.com plugin pages and package docs, June 2026. Where the plugin-page "supported version" badge disagrees with the package's own docs, I trust the docs and flag it.

## The short answer

Ship this client on **Z3d0X/filament-fabricator**.

It's the same skeleton the custom builder is already planned to be built on (see `prd.md` and `architecture.md`), so it's not throwaway work. It tracks Filament closely: a 4.x branch for Filament 5 already shipped (latest v4.1.0, May 2026), within weeks of Filament 5's release. It's MIT, tested, and by far the most-used option. Your editor edits a page as a stack of content blocks, each block is a Blade view you control, and the public site is server-rendered, so SEO is fine out of the box. That is exactly what a non-technical client needs and exactly what you want for a fast launch.

Pair it with `pboivin/filament-peek` if the client wants a live "see the page as I edit" preview. Add `awcodes/filament-curator` if they need a proper media library for images.

If the client specifically wants the editing screen to feel more like a real-time visual preview with less developer setup, **Redberry's page builder** is the credible second pick: it has built-in iframe live preview, block categories, thumbnails, and reusable global blocks. It's newer and less proven, but it's built by a 200-person Laravel agency and is honest about performance. I'd still default to Fabricator because it's the one you're standardising on.

## Why not the others (quick version)

The drag-and-drop "feels like Elementor" options look tempting but cost you time and SEO this week. Filamentor is a true visual canvas but is stuck on Filament 3 and needs a Vue/Inertia or Livewire stack decision plus manual Tailwind safelisting, with templates you have to wire into your layout yourself. TomatoPHP's CMS is a big, capable system but it's post/category content management with a lot of ecosystem dependencies, not a clean block page builder, and it's a heavier thing to learn under deadline. The plain Filament Builder field is the engine inside Fabricator anyway, so using Fabricator gives you the field plus the routing and rendering you'd otherwise hand-build.

---

## The options, compared

### 1. Z3d0X/filament-fabricator (the pick)

What it is: a block-based page-builder skeleton. It hands you the `PageResource` and front-end routing; you define Layouts and Page Blocks. Each block is a PHP class plus one Blade view. Storage is a JSON stack of blocks; rendering is server-side Blade.

The editor experience: the client adds, reorders, clones, and collapses blocks in a Filament form. Block picker can be a dropdown or a modal with icons. Not a free-form canvas, a structured block stack. For most marketing/brochure client sites that's the right level: controllable, hard to break, fast to build.

- Filament support: Fabricator 4.x → Filament ^5 (PHP ^8.3, latest v4.1.0, May 2026), 3.x → Filament ^4 (PHP ^8.2), 2.x → Filament ^3, 1.x → Filament ^2. Filament 5 support shipped within weeks of Filament 5's January 2026 release. Strong maintenance signal. Match the branch to your panel's Filament version.
- Stars: ~278 on the plugin (author total ~484). The most-used option here by a wide margin.
- Licence: MIT.
- Preview: credits and works with `pboivin/filament-peek`.
- SEO: server-rendered Blade, full content in the initial HTML response. Good.
- Speed to ship: fast. Install, define a handful of blocks (hero, text, image, CTA, FAQ), point your layout at `page-blocks`, done.

Why it wins for this job: it's the lowest-risk way to give a non-technical client block-based editing on a fast, crawlable Laravel site, and it's the exact foundation the custom builder is already designed around. Nothing you build here is wasted.

### 2. Redberry page builder (strong second)

What it is: a page-builder form field plus infolist entries, with real-time preview via iframe or view files. Blocks are classes with a Filament schema; a generator command scaffolds block class + view. Extras worth noting: block categories and thumbnail pickers (nicer block selection UX), reusable "global blocks" (configure once, use on every page), and a deliberately performance-tuned render path (they replaced Filament actions with plain buttons after a real project slowed down at ~65 blocks).

- Filament support: current 3.x of the plugin needs Filament 5 (PHP 8.2+, Laravel 11.28+). Filament 4 is on plugin branch 2.x, Filament 3 on 1.x. So match the branch to your panel's Filament version.
- Stars: ~31. Newer, less battle-tested, but the author is an established agency (Redberry, Tbilisi).
- Licence: MIT.
- Preview: built-in iframe live preview, including auto-resize, and a documented way to preview a separate frontend (handy if the public site is a separate Vue/Nuxt app).

When to pick it over Fabricator: the client really wants live visual preview baked in, or you're rendering the public site from a separate frontend repo and want the iframe-preview bridge. Otherwise Fabricator's larger user base and your own standardisation tip the call.

### 3. Filament Builder field (the native engine, not a product)

What it is: Filament's built-in `Builder` form field. A repeatable field where each item is one of several predefined block types, each with its own schema. This is the native block editor, and it's literally what Fabricator wraps.

- It gives you the block-editing form, nothing else. No page model, no slug/URL routing, no front-end rendering, no preview. You'd hand-build all of that.
- Use it directly only if your need is "let the client edit a few content blocks inside an existing page/record," not "build whole pages." For a full site, Fabricator saves you the routing and rendering you'd otherwise write yourself.

### 4. Filamentor (geosem42) (skip for this client)

What it is: a genuine drag-and-drop, grid-based visual page builder. Closest to the Elementor feel. Good for a client who wants to push blocks around a canvas.

Why not now:

- Filament version: its own docs say Filament 3.x, Laravel 11, PHP 8.1. The filamentphp.com page shows a "Supports v5.x" badge, but the README and install steps are Filament 3 only. The badge and the docs disagree, so treat v4/v5 support as unconfirmed. On a deadline, that's a real risk.
- Setup overhead: you pick a Vue/Inertia or Livewire stack at install, add a manual Tailwind `safelist` for grid classes, and the page templates are not pre-wired into your layout, you integrate them yourself.
- Stars: ~29. Small, more experimental.
- SEO: workable on the Livewire path (needs a `@stack('meta')` in your layout), but more moving parts than a Blade-rendered block stack.

Keep it as a reference for drag-and-drop UX when you build the custom canvas later (v3 of your own plugin), not as the thing you ship this week.

### 5. TomatoPHP (filament-cms / filament-page-builder) (skip for this client)

What it is: TomatoPHP is a large ecosystem of Filament packages by Fady Mondy (43 plugins, ~856 stars across them). Two are relevant: `filament-cms` (a full CMS, posts + categories + comments + import/export + theme manager) and a separate `filament-page-builder` (dynamic sections with drag-and-drop).

Why not now:

- `filament-cms` is content/post management, not a clean block page builder. Its documented features are Posts, Categories, comments and ratings, and import/export. The "page builder" lives in the separate package.
- Ecosystem weight: TomatoPHP packages tend to pull in other TomatoPHP packages. That's a lot of surface area to learn and carry for one client site on a tight timeline.
- There's an explicit upgrade caution in its own docs (don't jump to v4 from v1 or you lose features), a sign the moving parts need care.

Capable for a content-heavy site if you adopt the whole ecosystem deliberately. Wrong tool for a fast, single-client launch.

---

## Recommended setup for the client (this week)

1. Install Fabricator into the client's Laravel + Filament panel: `composer require z3d0x/filament-fabricator`, then `php artisan filament-fabricator:install`, register `FilamentFabricatorPlugin` in the panel, `php artisan filament:assets`.
2. Create one Layout that matches the client's site shell (header, footer, fonts), pointing at `<x-filament-fabricator::page-blocks>`.
3. Build the block set the client actually needs: hero, rich text, image, image + text, CTA, FAQ, and a raw-HTML escape hatch for anything custom. Each is one PHP class + one Blade view.
4. Add `pboivin/filament-peek` if they want live preview while editing.
5. Add `awcodes/filament-curator` if they need a reusable media library.
6. Per-page SEO fields (title, meta description, OG image) on the page model, rendered into the head. Sitemap via `spatie/laravel-sitemap`.

This is the same path the custom builder PRD lays out, so the client work doubles as the first real proving ground for the bigger plugin.

## How this bridges to the custom builder

You're not choosing between "quick win" and "the real thing." Fabricator is the foundation under both. Ship the client on Fabricator now; the blocks, Blade views, SEO wiring, and preview you build for them carry straight into the custom plugin. When you later add the drag-and-drop canvas (your v3), it sits on top of this same skeleton. Filamentor and Redberry are the reference points for that canvas UX and for live-preview patterns, respectively.

## Sources

- Filament Builder field: https://filamentphp.com/docs/3.x/forms/fields/builder
- Fabricator plugin page: https://filamentphp.com/plugins/z3d0x-fabricator
- Fabricator on GitHub (compatibility table): https://github.com/z3d0x/filament-fabricator
- Redberry page builder plugin page: https://filamentphp.com/plugins/redberry-page-builder
- Redberry on GitHub: https://github.com/RedberryProducts/filament-page-builder-plugin
- Filamentor plugin page: https://filamentphp.com/plugins/george-semaan-filamentor-page-builder
- Filamentor on GitHub: https://github.com/geosem42/filamentor
- TomatoPHP CMS plugin page: https://filamentphp.com/plugins/3x1io-tomato-cms
- TomatoPHP site: https://tomatophp.com/en
- TomatoPHP page builder: https://github.com/tomatophp/filament-page-builder
- filament-peek: https://github.com/pboivin/filament-peek
- Prior research in this folder: research/filament-plugin-development.md
