# Claude Code prompt: set up Fabricator as the client CMS

> Paste the block below into Claude Code, running inside the client's Laravel project.
> Before you paste: fill in the four bracketed spots at the top (design source, brand, site basics, and which blocks you actually want). Everything else is ready.
> Context: existing Laravel app, no Filament yet. Target Filament 5. This builds a block-based page CMS on Z3d0X/filament-fabricator so a non-technical client edits pages, and the public site is server-rendered Blade (SEO-safe).

---

## The prompt

You are setting up a content-managed website inside this existing Laravel application. The client is non-technical and will edit pages themselves through an admin panel. The public site must be server-rendered (full HTML in the initial response, works with JavaScript disabled) so it stays fast and SEO-friendly.

We are using Filament 5 and the Z3d0X/filament-fabricator page-builder skeleton. Build on Fabricator, do not reinvent page routing, the page resource, or block rendering.

### Things I'm giving you (fill these in before running)

- **Design to match:** [point to the design here, e.g. "follow the Figma/Pencil file at <path or URL>", or "match the existing site at https://...", or "use the static HTML in resources/design/"]. Match its layout, spacing, typography, and colours. Do not invent a new look.
- **Brand basics:** [site name, primary colour(s), font choice if known, logo path].
- **Site essentials:** [list the pages the client needs first, e.g. Home, About, Services, Contact]. [Languages: English only, or English + Arabic with RTL].
- **Blocks I want:** Hero, Rich text, Image, Image + text, CTA, FAQ, Logo strip, Testimonials, Image gallery, Pricing table, Contact section, and a Raw HTML escape-hatch block. [Trim this list if any aren't needed.]

### Ground rules

- Confirm the current Laravel and PHP versions in this repo before installing anything (`composer show laravel/framework`, `php -v`). Filament 5 needs PHP 8.2+. If PHP is too old, stop and tell me rather than forcing it.
- Install Filament 5 and the plugins by letting Composer resolve compatible versions. Do not hardcode patch versions. Use these constraints and let Composer pick the rest:
  - `filament/filament:^5.0`
  - `z3d0x/filament-fabricator:^4.0` (this is the Filament 5 line of Fabricator; the package being "4.x" while Filament is "5.x" is expected, that's just the plugin's own numbering)
  - `pboivin/filament-peek:^4.0` (live preview, Filament 5 line)
  - `awcodes/filament-curator` (media library, latest compatible with Filament 5)
  - `ralphjsmit/laravel-seo` (per-page SEO/meta, latest compatible)
  - `spatie/laravel-sitemap` (sitemap, latest compatible)
- After each install step, run the package's own install/publish command, then run `php artisan filament:assets`.
- Work in small, verifiable steps. After each phase, tell me what you did and what I should see, then continue. Do not do the whole thing in one silent run.
- If any package's Filament 5 support is unresolved at install time, stop and report it. Do not downgrade Filament to make a package fit without asking me first.
- Keep the public render server-side (Blade). Never inject block content client-side. SEO depends on this.

### Phase 1: Filament 5 panel

1. Install `filament/filament:^5.0` and run the panel install (`php artisan filament:install --panels`).
2. Create an admin user.
3. Confirm I can log in to `/admin` (or the panel path you set). Report the URL and that the panel loads.

### Phase 2: Fabricator

1. Install `z3d0x/filament-fabricator:^4.0`, run `php artisan filament-fabricator:install` (publishes config + migrations), register `FilamentFabricatorPlugin::make()` in the panel provider, then `php artisan filament:assets` and `php artisan migrate`.
2. Create a `DefaultLayout` (Fabricator layout) whose Blade wraps the site shell (header, nav, footer) to match the design I gave you, and renders `<x-filament-fabricator::page-blocks :blocks="$page->blocks" />` for the body.
3. Confirm the Pages resource shows in the admin and a blank page can be created and viewed on its public URL.

### Phase 3: the block library

Build each block I listed as a Fabricator Page Block: one PHP block class (its Filament schema) plus one Blade view (its server-side render). Style every block's Blade view to match the design source. For each block:

- Give it a clear label and icon so the client's block picker is readable.
- Use sensible field types: `RichEditor` for body copy, `FileUpload`/Curator for images with required alt text, `Repeater` for lists (FAQ items, testimonials, gallery images, pricing rows, logo strip), `Select` for layout variants (e.g. image left/right).
- Keep the rendered DOM lean. No framework bloat.

Blocks to build (skip any I removed above):

- **Hero**: heading, subheading, background or side image, up to two CTA buttons (label + URL), layout variant.
- **Rich text**: a single `RichEditor` body, optional max width.
- **Image**: image (Curator), alt text, caption, width option.
- **Image + text**: image, heading, rich body, image-side toggle, optional CTA.
- **CTA**: heading, supporting line, button label + URL, background variant.
- **FAQ**: repeater of question + answer; render as accessible accordion (works without JS as plain expanded content if JS is off, or use `<details>`).
- **Logo strip**: repeater of logo image + optional link; render as a responsive row.
- **Testimonials**: repeater of quote, name, role/company, optional avatar.
- **Image gallery**: repeater of images (Curator) with alt text; responsive grid, lazy-loaded.
- **Pricing table**: repeater of plans (name, price, period, feature list, CTA label + URL, "highlighted" toggle).
- **Contact section**: heading, intro, and a working contact form (name, email, message) that validates server-side and emails [the client's address] or stores submissions in a `contact_submissions` table I can view in Filament. Tell me which you did and how to change the destination. Add basic spam protection (honeypot).
- **Raw HTML**: a single textarea rendered with `{!! !!}`, clearly labelled "advanced, for developer use," so we have an escape hatch.

After this phase, confirm the client can build a full page by stacking these blocks, reorder them, and see them render correctly on the public URL.

### Phase 4: SEO + sitemap

1. Add per-page SEO fields to the page model/resource: meta title, meta description, OG image (Curator), canonical URL, and an index/noindex toggle. Render them into the page `<head>` via `ralphjsmit/laravel-seo`.
2. Make pages `Sitemapable` and generate `/sitemap.xml` with `spatie/laravel-sitemap`, excluding noindex pages.
3. Ensure clean URLs from the page slug, and a sensible 404 for unknown slugs.
4. Confirm: view source on a published page shows the right title/description/OG tags, and the sitemap lists the public pages.

### Phase 5: live preview (filament-peek)

1. Install and wire `pboivin/filament-peek` so the client can preview a page (including unsaved edits) from the page editor.
2. Confirm a Preview button appears on the page edit screen and shows the page rendered with the current edits.

### Phase 6: draft / publish

1. Add a publish state so editing never changes the live page until the client clicks Publish. Simplest robust approach: a `status` (draft/published) plus a separate stored "published content" snapshot, or a published_at + a draft content column, your call, but the live public route must render only published content while the editor edits a draft.
2. Public routes serve published content only. Preview (Phase 5) shows the draft.
3. Confirm: edit a published page, save as draft, the public page is unchanged; click Publish, the public page updates.

### Phase 7: verification (do this and report results)

- Server-render check: load a published page with JavaScript disabled (or `curl` it) and confirm the full content is in the HTML.
- Non-technical run: from the admin only, build a new page from blocks, set its SEO fields, preview it, publish it, and load it publicly. Confirm each step works.
- Developer run: confirm I can add a brand-new block type with one PHP class + one Blade view and it appears in the editor with no core changes.
- Contact form: submit it, confirm the submission lands where you said (email or DB).
- Sitemap + meta: confirm `/sitemap.xml` and the head tags are correct.
- Run the app's linter/formatter and the test suite if one exists; report anything failing.

When everything passes, give me: the admin URL, how to log in, a one-paragraph "how the client edits a page" note I can hand to them, and a short list of where each block class and Blade view lives so I can extend them later.

### What to flag, not silently decide

- If the design I gave you is missing a state a block needs (e.g. no testimonial design), build a clean minimal version that fits the system and tell me.
- If multilingual/RTL is in scope, raise how you're handling per-locale content before building the schema, since it affects the page model.
- If any package can't resolve against Filament 5, stop and tell me before changing versions.

---

## Notes for Safi (not part of the prompt)

- This bridges straight into your custom `filament-page-builder` plugin. The blocks, Blade views, SEO wiring, draft/publish, and preview you build here are the same pieces your PRD describes, so the client work doubles as the first real proving ground.
- The version constraints use `^` ranges on purpose. Composer resolves the exact build for your installed Filament 5, so the prompt won't rot if a package ships a new patch next week. The one fixed fact: Fabricator's 4.x line is the Filament 5 line, peek's 4.x line is the Filament 5 line.
- If the client site's public frontend is a separate app (not Blade in this repo), that changes the render story and Redberry's iframe-preview path becomes more relevant. This prompt assumes one Laravel app rendering its own pages, which is the simpler, faster, more SEO-solid setup.
- Curator vs the plain Filament `FileUpload`: I told Claude Code to use Curator for a reusable media library. If you'd rather keep it dead simple for one small site, you can drop Curator from the install list and the prompt still works with `FileUpload`.
