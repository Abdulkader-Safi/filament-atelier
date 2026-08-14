# PRD: Filament Atelier

> Package `safi/filament-atelier`, repo `Abdulkader-Safi/filament-atelier`. Named 2026-08-14.

> Status: draft v2, awaiting Safi's sign-off. No build starts until this is approved (per build-protocol.md).
> Rewritten 2026-08-14 after the editor decision changed. v1 of this PRD is superseded, see "What changed" below.

## What changed since 22 June

The first PRD planned a Filament Builder-field editor: a stack of collapsible block forms, with the visual canvas deferred to v3. Safi rejected that. The requirement is a live preview that updates **while** editing, not a preview you open when you're done.

Four decisions taken on 14 Aug 2026:

1. **Three-pane editor is v1, not v3.** Section list on the left, live iframe in the middle, settings on the right. Modelled on FilamentCraft's editor.
2. **Single site per install.** It's a plugin you composer-install into a client's Laravel app and it works. No multi-tenancy.
3. **Bilingual from day one.** English + Arabic with RTL, because dsrpt's GCC clients need it and retrofitting locales into a stored block tree is a migration over every page.
4. **First target is the next new dsrpt client site**, not KIF. So the v1 block library is a generic marketing set, not an events set.

One more decision was forced by the research: FilamentCraft already does all of the above and sells for a one-time fee from about $74. Safi's reason for building anyway is that it becomes dsrpt's own tool for client websites, owned and extendable. That's the stated rationale and this PRD assumes it.

## Problem

dsrpt builds client websites repeatedly. Today that means bespoke code per site, where every content change needs a developer, or WordPress with Elementor, which is off our stack and slow. There's no Laravel-native way to hand a client a visual editor while keeping the performance and SEO of a hand-coded site.

The goal: a Filament plugin that turns a Laravel app into a CMS where a non-technical client builds pages visually and watches the real page update as they type, a dsrpt developer adds block types in code, and the public site is server-rendered and crawlable in both English and Arabic.

## Who it's for

- **Clients** (non-technical): build and edit pages by adding sections and filling in fields, in either language, seeing the real page as they work. Never see code.
- **dsrpt developers**: register block types in code, drop the plugin into any Laravel + Filament project, hand it over.
- **Safi as admin**: add extra blocks after handover by pasting HTML and CSS, without a deploy for the plain-CSS case.
- **Site visitors**: fast, server-rendered, crawlable pages, correct in RTL.

## Success criteria (concrete, testable)

1. A non-technical user builds a multi-section page (hero, features, testimonials, CTA, FAQ, contact) and publishes it, without touching code.
2. Editing a field updates the middle iframe within 1 second of the user pausing, without saving and without a full editor reload.
3. Reordering sections by drag updates the preview and persists the new order.
4. A developer registers a new block type (one PHP class + one Blade view) and it appears in the section picker with working controls, no core changes.
5. An admin creates a new block from pasted HTML + CSS in the panel, and it is usable in the editor in the same session, with no deploy.
6. Public pages are server-rendered: full content present in the initial response with JS disabled, in both locales.
7. Every page exists at `/{slug}` and `/ar/{slug}` with `hreflang` tags pointing at each other, and `dir="rtl"` applied on the Arabic side.
8. Editing a draft never changes the live page until Publish is clicked.
9. Each page has editable SEO fields per locale that render into the head, plus an auto-generated sitemap covering both locales.
10. An editor applies a scroll animation to a section from a dropdown, and it works live and survives Livewire SPA navigation.
11. A published page hits green Core Web Vitals (LCP ≤ 2.5s, INP ≤ 200ms, CLS ≤ 0.1) on a representative page.

## Scope

### In (v1)

- Plugin packaging: composer install, panel registration, publishable config and migrations.
- Three-pane editor as a custom Filament page: section list (drag to reorder, duplicate, delete, hide), live iframe, settings panel.
- Language switcher in the editor toolbar, English and Arabic.
- Block registry + a marketing block set: header, hero, features, logo wall, testimonials, CTA, FAQ, rich text, image, gallery, contact form, footer.
- Blade server-side rendering, one Blade view per block type.
- Admin-created blocks from pasted HTML + CSS, with an auto-generated field schema.
- Two-column draft/published model, plus a revisions snapshot table.
- Per-locale SEO fields and JSON-LD via `ralphjsmit/laravel-seo`, sitemap via `spatie/laravel-sitemap`.
- GSAP animation presets stored as block data, initialised Livewire-safe.
- Design tokens (palette, type scale, spacing) shared by editor and front end.

### Later (v2)

- Section presets and themes, the way FilamentCraft does design families.
- Synced blocks that update everywhere at once.
- Revisions UI with restore and diff.
- Scheduled publishing.
- Per-section refresh instead of whole-iframe refresh.

### Out

- A hosted SaaS. This is a plugin for dsrpt's own client apps.
- Multi-tenancy. One install, one site.
- Migrating existing WordPress or Elementor content.
- E-commerce, a forms engine, memberships.
- Public or commercial release. Internal dsrpt tool for now, see open question 3.

## Constraints

- **Stack:** Laravel 12/13, Filament (build against v4, support `^4.0|^5.0`), Livewire 4, Alpine 3, Tailwind 4, GSAP 3.15.
- **GSAP licensing:** free for commercial use since April 2025, including the former Club plugins. It is not MIT and not open source. Ships fine in client sites, don't call it open source.
- **SEO is a first-class requirement.** SSR is mandatory. The canvas is an editing tool, the public render is always Blade SSR. Never inject blocks client-side.
- **Security:** pasted HTML is never compiled as Blade. See the block-authoring section.
- **Performance:** lean DOM, conditional per-block assets, cached output. Don't repeat Elementor's bloat.
- **Reuse before custom:** `Z3d0X/filament-fabricator` still earns its place for the page model, routing and layout resolution. `pboivin/filament-peek` does not, because its Builder Preview is the modal we're replacing. Read its refresh strategy before writing ours.

## Data model changes from architecture.md

`architecture.md` still describes the single-locale model. Three changes:

**Per-locale content inside the block tree, not separate trees.** A block's translatable attributes hold a map keyed by locale:

```json
{
  "id": "b_8f3a",
  "type": "hero",
  "attributes": {
    "heading": { "en": "Welcome", "ar": "أهلا بك" },
    "background": { "token": "color.primary" }
  }
}
```

One structure, translated text. Arabic gets mirrored by `dir="rtl"` and CSS logical properties, not by reordering blocks. The cost of this choice: Arabic cannot have a different section order from English. That's the right trade for a marketing site and it keeps the two languages from drifting apart.

**Slugs move out to their own table.** A JSON slug map can't carry a unique index, so:

```
page_slugs
  id, page_id fk, locale, slug
  unique (locale, slug)
```

**A table for admin-created blocks:**

```
custom_blocks
  id
  type          string unique   -- registry key, e.g. 'custom.pricing-table'
  label, icon, category
  template      text            -- pasted HTML containing {{ placeholders }}
  styles        text nullable   -- plain CSS, scoped to the block on render
  fields        json            -- generated from the placeholders, then editable
  created_at, updated_at
```

## Admin-created blocks: how it works and what it costs

The flow: paste HTML into a textarea, mark the editable parts as `{{ heading }}`, `{{ image }}`, `{{ cta_label }}`. On save, the placeholders are extracted with a regex, a field schema is generated from them (defaulting to text, changeable to image, richtext, URL, repeater), and the block appears in the section picker.

**Rendering is placeholder substitution, not template compilation.** `Blade::render()` on user input compiles to PHP, which turns an admin textarea into remote code execution. Values are escaped on substitution, with a per-field opt-in for richtext.

**Tailwind is the real limitation, and it needs saying plainly.** Tailwind scans plain text source files and cannot see classes stored in a database, confirmed against Tailwind's own docs on 14 Aug 2026. So pasted Tailwind classes generate no CSS. Two paths, both shipped:

- **Default, plain CSS.** Paste HTML plus a CSS block. It's stored, scoped, and rendered in a `<style>` tag. Works immediately, no build step, no deploy.
- **Optional, Tailwind sync.** An artisan command extracts every class from `custom_blocks.template` and writes an `@source inline(...)` file that the site's normal `npm run build` picks up. Real Tailwind classes, but a new block is unstyled until the next deploy. The editor shows that warning when Tailwind classes are detected and the safelist is stale.

**Trust boundary:** pasted HTML runs on the public site, so anyone who can author a block can inject script. Gate block authoring behind its own permission, off by default, so a client editor cannot reach it.

## The live preview, and its one hard problem

The iframe points at a preview route that renders the current editor state, gated by a signed URL, always `noindex`.

The problem is refresh cost: naively, every keystroke triggers a full server render of the whole page. Peek solved it with an opt-in checkbox and debounced reactive fields, which is an admission the cost is real. v1 does three things: debounce at roughly 500ms after the user stops typing, refresh only on fields marked reactive, and keep scroll position across refreshes so the editor doesn't jump. v2 replaces the whole-page refresh with a single-block render swapped into the iframe.

This is the highest-risk part of the build and the first thing to prototype. If it feels laggy on a real page with 12 sections, the rest of the plan doesn't matter.

## Plan

1. **Spike the preview loop first.** One page, two hardcoded blocks, three-pane layout, debounced iframe refresh. Prove it feels live before building anything else. Kill or redesign here if it doesn't.
2. **Foundation.** Fabricator skeleton, plugin packaging, `pages` + `page_slugs` + `page_revisions` tables, `BlockRegistry`, design tokens.
3. **Block library + rendering.** The `Block` interface, the marketing block set, Blade SSR.
4. **Editor.** Drag to reorder, duplicate, delete, hide, the settings panel, the language switcher.
5. **Bilingual.** Per-locale attributes, `/ar/{slug}` routing, RTL layout, hreflang.
6. **Draft, publish, preview links.** Two-column flow, signed shareable preview, revisions snapshots.
7. **Admin-created blocks.** Placeholder extraction, generated schema, scoped CSS, the Tailwind sync command, the permission gate.
8. **SEO + sitemap.** Per-locale meta, JSON-LD, sitemap covering both locales.
9. **Animation.** GSAP presets, `livewire:navigated` init, `gsap.context()` teardown.
10. **Performance pass.** Conditional assets, per-page CSS cache, image dimensions and lazy loading, CWV check.
11. **Verification.** SSR check with JS off in both locales, a non-technical build-and-publish run, a developer add-a-block run, an admin paste-a-block run, Lighthouse.

## Open questions

1. **Who owns it?** It's built for dsrpt client work. Is this Safi's package that dsrpt uses, or dsrpt's IP? Worth settling before the first commit, not after.
2. **Which Filament version is the floor?** Building against v4 and supporting v5 costs compatibility work. If every dsrpt client app is on v5, drop v4 and save the effort.
3. **Distribution:** private package on a dsrpt repo, or eventually public? This only changes docs and polish, so it can wait, but it affects how much time goes into the block library.
4. **Contact form block:** does it submit to something real (a `submissions` table, an email, a CRM), or is it a presentational block pointing at a route the developer wires up? Cheapest is the second.

## What I'd push back on

- **Prototype the preview loop before anything else.** It's the reason for the whole rewrite and the only genuinely hard part. Everything else here is known work.
- **Don't build themes in v1.** FilamentCraft's design families look great in a screenshot and are a large amount of design work with no client asking for it yet.
- **Resist a general-purpose control panel.** Elementor's failure mode is a thousand style fields per block. Design tokens plus a small `supports` list per block keeps the surface small and the output consistent.
- **The honest cost check.** FilamentCraft is roughly $74 and exists today. This plan is weeks of evenings. The only thing that justifies it is dsrpt owning and extending the tool, so if that stops being true, stop building.
