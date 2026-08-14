# PRD: Filament Atelier

> Package `safi/filament-atelier`, repo `Abdulkader-Safi/filament-atelier`. Named 2026-08-14.

> Status: draft v2, awaiting Safi's sign-off. No build starts until this is approved (per build-protocol.md).
> Rewritten 2026-08-14 after the editor decision changed, then re-scoped the same day to code-defined blocks. v1 of this PRD is superseded, see "What changed" below.

## What changed since 22 June

The first PRD planned a Filament Builder-field editor: a stack of collapsible block forms, with the visual canvas deferred to v3. Safi rejected that. The requirement is a live preview that updates **while** editing, not a preview you open when you're done. The scope then narrowed in the other direction: the editor is for content and structure, and block types stay in code.

Five decisions taken on 14 Aug 2026:

1. **Three-pane editor is v1, not v3.** Section list on the left, live iframe in the middle, settings on the right. Modelled on FilamentCraft's editor.
2. **Single site per install.** It's a plugin you composer-install into a client's Laravel app and it works. No multi-tenancy.
3. **Bilingual from day one.** English + Arabic with RTL, because dsrpt's GCC clients need it and retrofitting locales into a stored block tree is a migration over every page.
4. **First target is the next new dsrpt client site**, not KIF. So the v1 block library is a generic marketing set, not an events set.
5. **Blocks are code-defined in v1. Creating block types from the panel moves to v2.** v1 is Fabricator's model (a developer writes a PHP class and a Blade view, it appears in the section picker) with a visual editor on top. The client edits content and structure, never block types. Authoring new block types from the panel, Gutenberg-style, is a later phase and carries its own research, kept at the bottom of this doc.

One more decision was forced by the research: FilamentCraft already does all of the above and sells for a one-time fee from about $74. Safi's reason for building anyway is that it becomes dsrpt's own tool for client websites, owned and extendable. That's the stated rationale and this PRD assumes it.

## What v1 actually is, in one paragraph

Fabricator's developer model with a real visual editor. A dsrpt developer defines the block types in code. The client opens a page in the panel, sees the site rendered in the middle pane with the site's own CSS at the site's own width, clicks a section, edits its fields on the right, and watches the render update. They add sections from a picker of the code-defined blocks, drag to reorder, duplicate, hide, delete. The point of the render is judgement the form can't give: whether a headline wraps badly, whether two cards sit unevenly, whether the section reads right in Arabic. Nothing about block *types* is editable from the panel in v1.

## Problem

dsrpt builds client websites repeatedly. Today that means bespoke code per site, where every content change needs a developer, or WordPress with Elementor, which is off our stack and slow. There's no Laravel-native way to hand a client a visual editor while keeping the performance and SEO of a hand-coded site.

The goal: a Filament plugin that turns a Laravel app into a CMS where a non-technical client builds pages visually and watches the real page update as they type, a dsrpt developer adds block types in code, and the public site is server-rendered and crawlable in both English and Arabic.

## Who it's for

- **Clients** (non-technical): build and edit pages by adding sections and filling in fields, in either language, seeing the real page as they work. Never see code.
- **dsrpt developers**: register block types in code (a PHP class and a Blade view), drop the plugin into any Laravel + Filament project, hand it over. This is the only way new block types get created in v1.
- **Site visitors**: fast, server-rendered, crawlable pages, correct in RTL.

## Success criteria (concrete, testable)

1. A non-technical user builds a multi-section page (hero, features, testimonials, CTA, FAQ, contact) and publishes it, without touching code.
2. Editing a field updates the middle iframe within 1 second of the user pausing, without saving and without a full editor reload.
3. Reordering sections by drag updates the preview and persists the new order.
4. A developer registers a new block type (one PHP class + one Blade view) and it appears in the section picker with working controls, no core changes.
5. The middle pane renders with the public site's own stylesheet, not panel styles, so what the client sees is what ships. A text change that pushes a heading onto a third line is visible in the preview before saving, and the client can switch the preview between desktop, tablet and mobile widths to check the same thing at each.
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
- Preview fidelity: the iframe loads the site's real front-end layout and stylesheet, plus a desktop/tablet/mobile width switcher in the toolbar.
- Language switcher in the editor toolbar, English and Arabic.
- Block registry, code-defined only: a block type is a PHP class plus a Blade view, registered at boot. Plus a marketing block set: header, hero, features, logo wall, testimonials, CTA, FAQ, rich text, image, gallery, contact form, footer.
- A raw-HTML block as the content-level escape hatch, so a one-off bit of markup doesn't need a new block type.
- Blade server-side rendering, one Blade view per block type.
- Two-column draft/published model, plus a revisions snapshot table.
- Per-locale SEO fields and JSON-LD via `ralphjsmit/laravel-seo`, sitemap via `spatie/laravel-sitemap`.
- GSAP animation presets stored as block data, initialised Livewire-safe.
- Design tokens (palette, type scale, spacing) shared by editor and front end.

### Later (v2)

- **Block types authored from the panel**, Gutenberg-style: paste HTML and CSS, get a generated field schema, use it in the editor without a deploy. This was in v1 and was moved out on 14 Aug. It's a separate feature with its own security and Tailwind problems, and it's worth nothing until the code-defined path is proven. Research kept at the bottom of this doc.
- Per-section refresh instead of whole-iframe refresh.
- Click a section in the preview to select it, instead of only in the left list.
- Section presets and themes, the way FilamentCraft does design families.
- Synced blocks that update everywhere at once.
- Revisions UI with restore and diff.
- Scheduled publishing.

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
- **The preview and the public page render through the same code path.** Same Blade views, same layout, same stylesheet, different data source. Any second rendering path for the editor is a bug waiting to happen and defeats the point of the preview.
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

No `custom_blocks` table in v1. Block types live in code and in the registry, nowhere else.

## How a developer defines a block

The whole v1 contract, borrowed from Fabricator and kept deliberately small:

```php
interface Block
{
    public static function type(): string;      // registry key, 'hero'
    public static function label(): string;     // shown in the section picker
    public static function icon(): string;
    public static function category(): string;
    public function schema(): array;            // Filament form components = the settings pane
    public static function supports(): array;   // ['background', 'padding', 'animation']
}
```

Plus one Blade view at a conventional path, rendered with the block's attributes. Register the class once at boot and it appears in the picker with working controls. No core changes, no editor code, no JS.

`schema()` returning a plain Filament schema is the leverage here: the settings pane, validation and the whole control system come free, and translatable fields get the per-locale treatment described above without each block knowing about it.

## The live preview, and its two hard problems

The iframe points at a preview route that renders the current editor state, gated by a signed URL, always `noindex`.

**Problem one, refresh cost.** Naively, every keystroke triggers a full server render of the whole page. Peek solved it with an opt-in checkbox and debounced reactive fields, which is an admission the cost is real. v1 does three things: debounce at roughly 500ms after the user stops typing, refresh only on fields marked reactive, and keep scroll position across refreshes so the editor doesn't jump. v2 replaces the whole-page refresh with a single-block render swapped into the iframe.

**Problem two, fidelity.** The preview is only worth building if it is the real page. That means the preview route uses the public layout and the public compiled stylesheet, not panel CSS, and renders inside an iframe so the panel's own styles can't leak in. The iframe is width-constrained by a toolbar switcher (desktop, tablet, mobile) rather than by whatever space the middle pane happens to have, so the client is judging real breakpoints. Without this the whole feature degrades into a rough sketch and the client still has to open the site in another tab, which is what we're trying to remove.

This is the highest-risk part of the build and the first thing to prototype. If it feels laggy on a real page with 12 sections, the rest of the plan doesn't matter.

## Plan

1. **Spike the preview loop first.** One page, two hardcoded blocks, three-pane layout, debounced iframe refresh against the real front-end stylesheet. Prove it feels live and looks like the site before building anything else. Kill or redesign here if it doesn't.
2. **Foundation.** Fabricator skeleton, plugin packaging, `pages` + `page_slugs` + `page_revisions` tables, `BlockRegistry`, design tokens.
3. **Block library + rendering.** The `Block` interface, the marketing block set, the raw-HTML block, Blade SSR.
4. **Editor.** Drag to reorder, add from the picker, duplicate, delete, hide, the settings panel, the width switcher, the language switcher.
5. **Bilingual.** Per-locale attributes, `/ar/{slug}` routing, RTL layout, hreflang.
6. **Draft, publish, preview links.** Two-column flow, signed shareable preview, revisions snapshots.
7. **SEO + sitemap.** Per-locale meta, JSON-LD, sitemap covering both locales.
8. **Animation.** GSAP presets, `livewire:navigated` init, `gsap.context()` teardown.
9. **Performance pass.** Conditional assets, per-page CSS cache, image dimensions and lazy loading, CWV check.
10. **Verification.** SSR check with JS off in both locales, a non-technical build-and-publish run, a developer add-a-block run, Lighthouse.

## Deferred to v2: block types authored from the panel

Not built in v1. Kept here because the research is done and the constraints won't change.

The intended flow: paste HTML into a textarea, mark the editable parts as `{{ heading }}`, `{{ image }}`, `{{ cta_label }}`. On save, the placeholders are extracted with a regex, a field schema is generated from them (defaulting to text, changeable to image, richtext, URL, repeater), and the block appears in the section picker alongside the code-defined ones. It needs a `custom_blocks` table holding the type key, label, icon, category, the template, optional CSS, and the generated field schema.

Three constraints found on 14 Aug 2026, all still true whenever this gets built:

**Rendering must be placeholder substitution, not template compilation.** `Blade::render()` on user input compiles to PHP, which turns an admin textarea into remote code execution. Values get escaped on substitution, with a per-field opt-in for richtext.

**Tailwind cannot see classes stored in a database.** It scans plain text source files, confirmed against Tailwind's own docs. So pasted Tailwind classes generate no CSS. Two paths, and this feature needs both: plain CSS pasted alongside the HTML, stored, scoped and rendered in a `<style>` tag with no build step; or an artisan command that extracts every class from the stored templates into an `@source inline(...)` file the site's normal `npm run build` picks up, which means a new block is unstyled until the next deploy.

**Pasted HTML runs on the public site**, so anyone who can author a block can inject script. It needs its own permission, off by default, so a client editor cannot reach it.

## Open questions

1. **Who owns it?** It's built for dsrpt client work. Is this Safi's package that dsrpt uses, or dsrpt's IP? Worth settling before the first commit, not after.
2. **Which Filament version is the floor?** Building against v4 and supporting v5 costs compatibility work. If every dsrpt client app is on v5, drop v4 and save the effort.
3. **Distribution:** private package on a dsrpt repo, or eventually public? This only changes docs and polish, so it can wait, but it affects how much time goes into the block library.
4. **Contact form block:** does it submit to something real (a `submissions` table, an email, a CRM), or is it a presentational block pointing at a route the developer wires up? Cheapest is the second.

## What I'd push back on

- **Prototype the preview loop before anything else.** It's the reason for the whole rewrite and the only genuinely hard part. Everything else here is known work.
- **Keep block types in code for as long as possible.** Every serious page builder that let non-developers create block types ended up carrying a template language, a security boundary and a support burden. v1 has an escape hatch already: the raw-HTML block covers the one-off case without any of that.
- **Don't build themes in v1.** FilamentCraft's design families look great in a screenshot and are a large amount of design work with no client asking for it yet.
- **Resist a general-purpose control panel.** Elementor's failure mode is a thousand style fields per block. Design tokens plus a small `supports` list per block keeps the surface small and the output consistent.
- **The honest cost check.** FilamentCraft is roughly $74 and exists today. This plan is weeks of evenings. The only thing that justifies it is dsrpt owning and extending the tool, so if that stops being true, stop building.
