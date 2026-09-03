# Architecture spec

> **Partly superseded, 2026-08-14.** Read `prd.md` (v2) first. Three things here are out of date: the editing canvas is now a three-pane live-preview editor in v1 (not the Builder field, not deferred to v3), content is stored per locale for English + Arabic, and slugs move to their own `page_slugs` table. Everything else here still stands, and section 3 of this doc ("The block registry") is now the centre of v1 rather than a supporting piece: the JSON block tree, the registry and `Block` interface, the three layers of who-can-do-what, design tokens and `supports`, the CSS strategy, drafts and publishing, GSAP, and the SEO layer.
>
> Note on the three extensibility layers below: v1 ships layers 1 and 3 (editor arranges registered blocks, developer registers new ones in code) plus the raw-HTML escape hatch at layer 2. Creating whole new _block types_ from the panel is v2.

> The recommended technical design for the Filament page-builder plugin, built from the four research briefs in `research/`. This is a proposal for sign-off, not a final build. Nothing here is wired to LaralCN-UI; that stays a possible future block-library source, noted at the end.

## The one big decision: store a JSON tree, render with Blade at request time

Gutenberg and Elementor agree on the data model (a page is a tree of typed nodes with attributes and children) but split on storage. Gutenberg stores rendered HTML with attributes hidden in HTML comments, then re-parses and re-validates it on every load. That single choice is the root of three painful subsystems: attribute sourcing, block validation ("this block was modified externally"), and the deprecation/migrate chain.

We skip all of it. Store the block tree as structured JSON, render each block through a Blade view at request time. In Gutenberg terms, every block is a "dynamic block" by default. That buys us, for free, what WordPress had to engineer around: SEO-ready HTML in the first response, live data in any block without re-saving, and template changes that apply everywhere instantly with no validation machinery.

Elementor already works this way (server-render the JSON tree to HTML), and it's the natural Laravel model. So the recommendation is: Gutenberg's data model, Elementor's render-and-cache approach, neither one's baggage.

## Rendering recommendation: Blade server-side render, with a hybrid editing canvas

You asked me to recommend between "Blade-rendered server-side" and "live visual canvas." The answer is both, in layers, because they're not actually competing, they answer different questions (how it's stored/served vs how it's edited).

**Front end: always Blade server-side render.** Non-negotiable for SEO and performance. The public route loads the page's published JSON tree, walks it, and renders one Blade component per block type. No client-side block injection, ever.

**Editing: start with Filament's Builder field, add a visual canvas later.** _(Reversed on 14 Aug. The canvas is v1. See `prd.md`.)_ The Builder field is a stack of typed blocks with reordering, collapsing, cloning, per-block schemas. It's the native, batteries-included block editor and gets you 80% of the Gutenberg feel for a fraction of the effort. A true live visual canvas (edit on a rendered preview, Elementor-style) is a custom Filament field built as an async Alpine component. It's the hard, expensive part and the right place to phase it: ship the Builder-field version first, prove the block library and rendering, then build the canvas as v2.

This phasing matters. The genuinely hard engineering in both Gutenberg and Elementor is the editor UI, not the data model. Don't pay for the canvas before the rest works.

## Data model

### Tables

```
pages
  id
  title
  slug                 unique, indexed
  status               draft | published | scheduled | archived
  draft_content        json   -- the working block tree (editor edits this)
  published_content    json   -- the frozen block tree (public route renders this)
  layout               string -- which layout wraps the blocks (header/footer choice)
  meta_title           string nullable
  meta_description     text nullable
  og_image             string nullable
  canonical_url        string nullable
  structured_data_type string nullable  -- Article | Product | FAQPage | ...
  preview_token        string nullable, indexed
  published_at         timestamp nullable
  created_at, updated_at

page_revisions
  id
  page_id              fk
  content              json   -- full snapshot at publish/save
  created_by           fk users
  label                string nullable
  created_at

synced_blocks            -- shared blocks that update everywhere (Gutenberg "synced patterns")
  id
  name
  content              json   -- a block subtree
  created_at, updated_at
```

Two content columns is the baseline: editing only touches `draft_content`, publishing copies it to `published_content`, the public route reads `published_content` only. The live page never changes mid-edit. Layer `page_revisions` snapshots on top for version history and revert. These solve different problems; we want both.

### The block tree shape

Each block, recursively:

```json
{
  "id": "b_8f3a",
  "type": "hero",
  "attributes": {
    "heading": "Welcome",
    "subheading": "...",
    "background": { "token": "color.primary" },
    "padding": { "unit": "px", "top": 80, "bottom": 80 }
  },
  "animation": { "preset": "fade-up", "duration": 0.8, "trigger": "onScroll" },
  "children": []
}
```

Borrowed deliberately: `id` (stable per block), `type` (registry key), `attributes` (one place for all data, no scraping out of HTML), `children` (nesting via recursion), `animation` (data not code, see below), and design tokens stored as references (`{ "token": "color.primary" }`) not literals, so a theme change ripples everywhere.

Store a `schema_version` per block type so a future change to a block's attribute shape is a normal data migration over the JSON, not a runtime save-function chain.

## The block registry (the visual-vs-code split)

This is the heart of "a mix between visual page builder and code," and the answer to your "both" on how deep the code mix goes.

A block type is one PHP class plus one Blade view. The class declares:

```php
interface Block
{
    public static function type(): string;        // 'hero', registry key, namespaced for 3rd parties
    public static function label(): string;        // shown in the block picker
    public static function icon(): string;
    public static function category(): string;     // grouping in the picker
    public function schema(): array;               // Filament form components = the controls
    public function render(array $attributes, array $children): View;  // Blade
    public static function supports(): array;      // ['background', 'padding', 'animation']
}
```

Register them once at boot into a `BlockRegistry` (a container binding mapping `type` to the class). Three layers of "who can do what," which is exactly the Gutenberg + Elementor extensibility model:

1. **Non-technical editor.** Drags registered blocks and fills in controls (Filament form fields). Never touches code.
2. **Power user.** Uses a built-in `html` / `code` block to drop raw HTML/CSS/Blade-safe markup into a page. This is the "visual plus code" escape hatch at the content level.
3. **Developer.** Registers new block types in code (a class + a Blade view), the way Elementor devs write a `Widget_Base` and Gutenberg devs write `block.json` + render. New capability = a new class, and the platform handles the picker UI, persistence and rendering.

Filament's form field types map almost 1:1 onto Elementor's control taxonomy (text, select, color, slider, repeater, responsive), so `schema()` is just a Filament schema. That is the single biggest win: the entire control system comes free.

### `supports` + central design tokens

Copy Gutenberg's `supports` + `theme.json` idea. A central design-tokens config (one palette, one type scale, one spacing scale, layout widths) defined once, emitted as CSS custom properties shared by editor and front end so the preview matches the live page. Each block declares which features it opts into (`['background', 'padding']`), and the builder renders the right controls and applies the right classes automatically. This is what stops the builder from devolving into a thousand bespoke style fields per block.

## CSS strategy (learn from Elementor's scars)

Elementor's selectors-as-data is clever: store style intent as `selector template to value`, scoped to a unique per-element wrapper, compiled to a cached per-page stylesheet. Borrow the pattern, fix the mistake.

- For most styling, lean on Tailwind utility classes emitted by the Blade views plus the shared design tokens. This keeps CSS tiny and avoids per-element generated CSS entirely for the common case.
- For per-element custom values (a specific padding, a one-off color), compile a small scoped stylesheet per page and cache it. Do not write it to a user-writable uploads dir (Elementor's deploy/load-balancing headache). Cache it where you control it (`storage`, or inline `<style>` in the rendered page, regenerated on publish).
- Load block CSS/JS only when a block is on the page, from day one. Retrofitting conditional loading later is painful.

## Preview, drafts, publish

- **Preview:** a `temporarySignedRoute` (expiring signed URL) to a `PreviewController` that renders the same `pages.show` template from `draft_content` instead of `published_content`. Same template, different data source. Add `noindex` and skip analytics in preview mode so a leaked link never gets indexed. Optionally also a rotatable `preview_token` for a stable shareable link.
- **Draft/publish:** editing writes `draft_content`. "Publish" copies it to `published_content`, sets `status` and `published_at`, snapshots to `page_revisions`, and regenerates the sitemap. A plain enum `status` is fine to start; reach for `spatie/laravel-model-states` only if you add a real approval/scheduling workflow.
- **Revisions:** full JSON snapshot per publish, restore by copying a revision's `content` back into `draft_content`. Keep the last N.

## Animation (GSAP)

Animation is stored as data on each block (`{ preset, duration, delay, trigger }`), set by the editor from a dropdown of named presets. A single front-end initializer reads `[data-anim]` attributes and maps each preset to a GSAP recipe. Non-technical users never touch GSAP; you add presets in one map.

The critical Livewire detail: initialize on `livewire:navigated` (not `DOMContentLoaded`, which only fires on first load), tear down on `livewire:navigating`, and scope each component's animations with `gsap.context()` so `.revert()` cleans them up. Wrap GSAP-controlled regions in `wire:ignore` so Livewire's DOM morphing doesn't clobber inline transforms. Call `ScrollTrigger.refresh()` after blocks change page height.

License note for the PRD: GSAP is free for commercial use (Webflow, since April 2025), including all former Club plugins, but it is not MIT / not open source. Don't describe it as MIT.

## SEO + performance layer

- **SSR by default** (the whole architecture is built on this).
- **Per-page meta + JSON-LD** via `ralphjsmit/laravel-seo`: store `meta_title`, `meta_description`, `og_image`, `canonical`, `structured_data_type` as editable Filament fields, feed them to the package in the layout head. `FaqPage` schema is first-class, which matters for your FAQ-on-everything habit.
- **Core Web Vitals built in:** preload the LCP image with `fetchpriority="high"`, WebP/AVIF, `loading="lazy"` on below-the-fold media (never the LCP image), explicit width/height on all media to kill CLS, defer non-critical JS, keep block markup lean to protect INP.
- **Sitemap** via `spatie/laravel-sitemap`: `Page` implements `Sitemapable`, regenerate on publish, exclude drafts/previews. Clean `/{slug}` URLs, canonical set to the clean URL.

## Build vs fork

> **Reversed 2026-08-15.** We are not building on Fabricator. The two claims made against it (no Filament 5 support, unmaintained) were both false, and the correction is recorded in `prd.md` under "Why not Fabricator". The reason we dropped it anyway is that our spec replaces four of the five things it provides, so we'd inherit its constraints without using the parts that justify them. The original recommendation is kept below because its reasoning about *where to spend effort* still holds.

Strong recommendation: fork or build on `Z3d0X/filament-fabricator` for the skeleton (PageResource, slug/URL resolution, front-end routing, the Layouts + Page Blocks abstraction). It's MIT, tested, tracks Filament v5 within weeks. Spend your effort on the block library, the optional canvas, the design-token system, and the SEO/preview/animation layers, the parts that differentiate you. Bolt on `pboivin/filament-peek` for live preview rather than building it. Build fully from scratch only if you commit to a nested component-tree canvas from day one (the harder, more Elementor-like path).

## Phasing

> **Superseded 2026-08-14.** The phasing below was written when the visual canvas was v3. It isn't. Use the plan in `prd.md`: the three-pane live editor is v1, and the thing pushed to v2 is authoring block types from the panel. Kept for the reasoning only.

1. **v1, block-stack builder.** Fabricator skeleton + BlockRegistry + a starter block library (hero, text, image, columns, CTA, FAQ, raw-HTML). Builder field editing. Blade SSR. Draft/publish two-column. Signed-URL preview. Per-page SEO + sitemap. GSAP presets. This is a complete, shippable, SEO-ready builder.
2. **v2, design system + reuse.** Central design tokens + `supports`, synced blocks, block patterns/templates, revisions UI.
3. **v3, visual canvas.** The custom Filament field with drag-and-drop on a live preview (async Alpine component). The expensive, differentiating piece, built only once the foundation is proven.

## Open architecture questions (for the PRD)

- Single-tenant plugin for one site at a time, or multi-tenant for many client sites from one install?
- Does the canvas (v3) need true edit-on-render WYSIWYG, or is a side-by-side "form left, live preview right" enough? The latter is far cheaper and often enough.
- Target Filament version to build against (recommend v4 APIs, composer constraint `^4.0|^5.0`).
- Multilingual pages in scope (dsrpt serves English + Arabic clients)? Affects the data model early.

## Future hook: LaralCN-UI (not in scope now)

Your LaralCN-UI Blade component system could later become the rendering layer for blocks: a block's Blade view composes LaralCN components instead of raw markup, giving a consistent, owned design system across every builder block. Noted as a possible future integration only. Nothing in this spec depends on it, and v1 ships without it.
