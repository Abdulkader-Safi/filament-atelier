# Preview, drafts, GSAP, SEO

> Implementation research for the four moving parts that sit on top of the block engine: page preview, draft/publish/revisions, GSAP animation in a Livewire/Alpine context, and SEO/performance. Verified against official docs and Packagist, June 2026.

## Topic 1: page preview before publishing

The core principle: the public route filters to published content. Preview needs a parallel path that renders the same template from draft data, gated by something other than "is this published" (a signed URL or a preview token). Never make the preview path readable by flipping a guessable query param.

### Pattern A: signed / temporary signed routes (recommended default)

`URL::temporarySignedRoute()` generates a URL with a cryptographic signature plus an expiry. Tampered or expired links fail with 403. Cleanest "share a draft with a client for 30 minutes" mechanism, no auth session needed for the viewer.

```php
$url = URL::temporarySignedRoute('pages.preview', now()->addMinutes(30), ['page' => $page->id]);

// routes/web.php
Route::get('/preview/{page}', PreviewController::class)
    ->name('pages.preview')
    ->middleware('signed');
```

Use `temporarySignedRoute` (with expiry) over plain `signedRoute` so stale links die.

### Pattern B: explicit preview token

A `preview_token` column (random string, rotatable) on the page. The preview route looks the page up by token, not id. Revocable (rotate the token to kill all shared links), survives edits. Many teams combine both: a stable token for UX, wrapped in a signed URL for tamper-proofing.

### Pattern C: draft mode cookie (how headless CMSs do it)

WordPress headless mints a short-lived JWT preview token (~5 min). WPGraphQL issues dedicated preview tokens. Next.js Draft Mode validates a secret then sets a preview cookie; the same page component renders draft or published based on the cookie. The transferable lesson: one template, two data sources, gated by a token/cookie, not two separate templates.

### Rendering the same template from draft vs published

Don't build a second Blade view. Branch the data, keep the view identical:

```php
class PreviewController
{
    public function __invoke(Request $request, Page $page)
    {
        $content = $page->draft_content ?? $page->published_content;

        return view('pages.show', [
            'page'      => $page,
            'blocks'    => $content,
            'isPreview' => true,
        ]);
    }
}
```

In preview mode add `<meta name="robots" content="noindex">` and skip analytics/caching so a leaked preview never gets indexed. If pages are Livewire components, pass an `isPreview` boolean into `mount()` and have `render()` pick the data source.

## Topic 2: draft / publish / revisions

### Recommended primary pattern: two JSON columns

```
pages
  id, slug, title
  status              -- draft, published, scheduled, archived
  draft_content   json  -- the working copy the editor edits
  published_content json -- the frozen copy the public route renders
  published_at    timestamp nullable
```

Editing only ever touches `draft_content`. Publishing copies `draft_content` to `published_content`. The live page never changes mid-edit because the public route reads `published_content` exclusively.

Why two columns beats a single `content` + `is_published` flag: with a single column, the moment an editor saves a draft, the live page reflects unreviewed changes (or you're forced to block editing live pages). Two columns decouple "what I'm working on" from "what the world sees."

### State machine: spatie/laravel-model-states (verified)

Latest v2.13.1 (22 Feb 2026). Requires PHP ^8.4, supports Laravel 12 and 13, ~6M installs, zero advisories. Each state is a class (`Draft`, `Published`, `Scheduled`, `Archived`) serialized to a `status` column via `HasStates`. You declare allowed transitions so illegal jumps are blocked. Good when the publish workflow has real rules (scheduling, approval). Overkill if you only have draft/published, a plain enum is fine there.

### Revision history options (verified June 2026)

**owen-it/laravel-auditing** — actively maintained. Latest v14.0.4 (12 Jun 2026), PHP ≥ 8.2, Laravel ≥ 11, MIT. Records an audit row per change (old + new values, user, timestamp). More an audit log ("who changed what") than a "restore version N" system, though you can reconstruct from stored values.

**A dedicated `page_revisions` table** (roll-your-own, often the best fit):

```
page_revisions
  id, page_id
  content        json   -- full snapshot of the builder payload at publish/save
  created_by, created_at, label nullable
```

On each publish, insert a full snapshot. Restoring = copy a revision's `content` back into `draft_content`. Simple, explicit, easy to diff, you control retention (keep last 20). This is essentially what Craft CMS does.

### Two-column vs revisions table

| | Two JSON columns | Revisions table |
|---|---|---|
| Protects live page during edits | Yes (the point) | Only with a draft column too |
| History depth | None | Full, revert to any point |
| Storage | Tiny | Grows per save |
| Complexity | Very low | Moderate |
| Best for | The baseline you always want | Layered on top for version history |

Recommended combination: two columns for live-safety, plus a `page_revisions` snapshot table for history, optionally `laravel-model-states` for status workflow. They solve different problems; you usually want both.

## Topic 3: GSAP in a Livewire / Alpine context

### Licensing (verified, important nuance)

GSAP is 100% free for commercial use since April 2025, after Webflow acquired GreenSock. All formerly-paid Club plugins (ScrollTrigger, ScrollSmoother, SplitText, MorphSVG, DrawSVG) are now free. Current version 3.15.

It is NOT MIT / not open source. It's GreenSock's proprietary "no charge" license (now a Webflow license). You may use/reproduce/display it at no cost including in billed client products, but you may not decompile, rehost-as-your-own, or build a competing animation product from its code. The "gsap-skills" helper repo is MIT, but that's separate docs, not the library. So if the PRD says "MIT," correct it to "free for commercial use under GSAP's standard license, not open-source."

### The central problem: Livewire morphing + SPA navigation break animations

Two failure modes:

1. **Livewire DOM morphing.** After a round-trip, Livewire morphs the DOM in place. If GSAP set inline styles/transforms, morphing can clobber them, or your init never re-runs on swapped-in nodes. ScrollTrigger positions go stale when content height changes.
2. **`wire:navigate` SPA navigation.** GSAP/ScrollTrigger instances from the previous page are not auto-destroyed. They linger, point at dead elements, and leak.

### Solutions with the right hooks

**(a) Protect animated DOM from morphing, `wire:ignore`.** Wrap GSAP-controlled regions so Livewire leaves them alone. `wire:ignore.self` ignores only the element itself. Livewire 4 also offers `wire:replace` and a `morph` hook.

**(b) Re-init on every navigation, use `livewire:navigated` not `DOMContentLoaded`.** The single most important rule. With `wire:navigate`, `DOMContentLoaded` only fires on the first load. `livewire:navigated` fires on initial load and after every SPA navigation:

```js
document.addEventListener('livewire:navigated', () => { initAnimations(); });
```

**(c) Tear down before leaving, `livewire:navigating`.** Kill old ScrollTriggers/timelines so they don't run on stale elements:

```js
document.addEventListener('livewire:navigating', () => {
  ScrollTrigger.getAll().forEach(t => t.kill());
});
```

Guard against double-registration: document-level listeners persist across pages.

**(d) Best-practice scoping, `gsap.context()`.** GSAP's own recommendation for SPAs. It records every animation/ScrollTrigger created inside it so a single `.revert()` cleans them all up:

```html
<div x-data="heroAnim()" x-init="init()">...</div>
<script>
function heroAnim() {
  let ctx;
  return {
    init() {
      ctx = gsap.context(() => {
        gsap.from('.hero h1', { y: 40, opacity: 0, duration: 0.8 });
        gsap.utils.toArray('.reveal').forEach(el => {
          gsap.from(el, { opacity: 0, y: 30, scrollTrigger: { trigger: el, start: 'top 80%' } });
        });
      }, this.$el);
    },
    destroy() { ctx && ctx.revert(); }
  };
}
</script>
```

Alpine's `x-init` runs setup; Alpine's `destroy()` gives teardown. Scoping with `this.$el` keeps selectors from leaking across components.

**(e) Refresh after dynamic content.** When the builder injects/removes blocks and height changes, call `ScrollTrigger.refresh()`. Use function-based start/end values and `invalidateOnRefresh: true`. Avoid `scroll-behavior: smooth` on `<html>` (it desyncs ScrollTrigger); override with `html { scroll-behavior: auto !important; }`.

### Letting non-technical users pick animations (the builder UX)

Store animation as data, not code. Each block gets an `animation` config set via Filament Select/Radio fields:

```json
{ "type": "hero", "heading": "...", "animation": { "preset": "fade-up", "duration": 0.8, "delay": 0.1, "trigger": "onScroll" } }
```

Render that config onto the element as data attributes:

```blade
<section class="reveal"
         data-anim="{{ $block['animation']['preset'] }}"
         data-anim-duration="{{ $block['animation']['duration'] }}">
```

A single JS initializer (run on `livewire:navigated`) reads `[data-anim]` and maps each preset name to a GSAP recipe:

```js
const PRESETS = {
  'fade-up':    () => ({ from: { opacity:0, y:40 } }),
  'fade-in':    () => ({ from: { opacity:0 } }),
  'zoom-in':    () => ({ from: { opacity:0, scale:0.9 } }),
  'slide-left': () => ({ from: { opacity:0, x:60 } }),
};
function initAnimations() {
  gsap.utils.toArray('[data-anim]').forEach(el => {
    const recipe = PRESETS[el.dataset.anim];
    if (!recipe) return;
    gsap.from(el, { ...recipe().from, duration: +el.dataset.animDuration || 0.8,
      scrollTrigger: { trigger: el, start: 'top 80%' } });
  });
}
```

Non-technical users only pick from a dropdown of named presets; they never touch GSAP. New presets are added by you in one map. The stored content stays portable (just JSON) and the animation logic stays centralized.

## Topic 4: SEO + performance for a builder-rendered site

### Server-side rendering is non-negotiable

Builder output must be real server-rendered HTML. The Laravel + Blade + Livewire stack already does this. The failure mode to avoid: rendering blocks client-side by injecting them with JS after load. Crawlers (including AI crawlers, increasingly relevant in 2026) may not execute or wait for that JS, so JS-injected content can be invisible. Loop over the builder JSON in Blade and emit markup server-side.

### Per-page meta + structured data: package recommendation

**ralphjsmit/laravel-seo** (verified, updated 20 May 2026, actively maintained). Generates title (with sitewide suffix), meta tags, Open Graph, Twitter cards, canonical/alternate links, favicon, robots. Built-in structured data / JSON-LD with fluent builders: `Article`, `BreadcrumbList`, `FaqPage`, plus custom schema. Attach an SEO model to each page and set fields per page.

Alternatives: **artesaos/seotools** (facade-based, feature-rich, multiple JSON-LD blocks) and **romanzipp/laravel-seo** (lightweight, struct-based). For a page builder where each page stores its own SEO fields, ralphjsmit/laravel-seo is the strongest fit because its per-model SEO + first-class JSON-LD (including `FaqPage`) map cleanly onto builder pages. Store `meta_title`, `meta_description`, `og_image`, `canonical`, and a structured-data type as editable Filament fields; feed them to the package in the layout `<head>`.

Per-page checklist to expose in the builder: unique `<title>` and meta description, Open Graph, Twitter card (`summary_large_image`), canonical URL (critical, prevents duplicate-content from slug variants), JSON-LD per page type, `robots` (force `noindex` on preview routes).

### Core Web Vitals (2026 thresholds)

Targets: LCP ≤ 2.5s, INP ≤ 200ms, CLS ≤ 0.1. INP (replaced FID) is the most-failed metric in 2026 (~43% of sites miss it); LCP is hardest on mobile. Builder pages are prone to these because users can stack heavy blocks.

- **LCP:** preload the hero/LCP image with `fetchpriority="high"`; serve WebP/AVIF; inline critical CSS and defer the rest; preload fonts with `font-display: swap`. SSR itself is a high-impact LCP fix.
- **INP / DOM bloat:** keep block markup lean, avoid deeply nested wrappers per block, defer non-critical JS, minimize main-thread work. Heavy GSAP should be lazy/scroll-triggered, not all-at-once on load.
- **CLS:** every `<img>`, `<video>`, `<iframe>`, embed needs explicit `width`/`height` (or aspect-ratio).
- **Lazy-load images:** native `loading="lazy"` on below-the-fold media (not the LCP image, which should load eagerly/preloaded). Pair with explicit dimensions to avoid CLS.
- **Defer JS:** `defer`/`async`; keep GSAP init off the critical path, fire on `livewire:navigated`.

### Sitemap + clean URLs

**spatie/laravel-sitemap** (verified, v8.1.0, 12 Mar 2026). Have the `Page` model implement Spatie's `Sitemapable` so each published page contributes its URL, `lastmod`, `changefreq`, `priority`. Regenerate on publish (or on a schedule), reference in `robots.txt`, exclude draft/preview URLs. Clean slugs: store a unique `slug` per page, route as `/{slug}` (or nested for hierarchy), set canonical to the clean URL, avoid query-string page identity for public pages.

### Filament rendering note

The Builder field outputs a JSON array of typed blocks. Store in a JSON column with an `array` cast. Render by looping the JSON in Blade and mapping each block `type` to a Blade partial. `blockPreviews()` renders read-only block previews inside the admin. To shortcut routing/layouts, filament-fabricator (Z3d0X) handles the PageResource and frontend routing; statikbe/laravel-filament-flexible-content-blocks gives predefined blocks each with an extendable Blade view.

## Things to flag for the PRD

1. Fix the GSAP license claim. It's free for commercial use but NOT MIT / not open-source. It's Webflow/GreenSock's proprietary standard license.
2. Use two JSON columns (`draft_content` / `published_content`) as the baseline, layer a revisions snapshot table on top if clients want history. They solve different problems.
3. The whole preview design hinges on one rule: same template, different data source, gated by a signed URL or token, plus `noindex` on the preview route.
4. The biggest GSAP-on-Livewire trap is using `DOMContentLoaded`. Everything must re-init on `livewire:navigated` and tear down on `livewire:navigating`, ideally via `gsap.context()`.

## Sources

Preview: https://laravel.com/docs/13.x/urls, https://headstartwp.10up.com/docs/learn/wordpress-integration/previews/, https://github.com/vercel/next.js/blob/canary/examples/cms-wordpress/README.md

Drafts/revisions: https://github.com/spatie/laravel-model-states, https://github.com/owen-it/laravel-auditing, https://craftcms.com/docs/5.x/system/drafts-revisions.html

GSAP: https://gsap.com/community/standard-license/, https://webflow.com/blog/gsap-becomes-free, https://gsap.com/resources/st-mistakes/, https://gsap.com/resources/React, https://livewire.laravel.com/docs/4.x/navigate, https://livewire.laravel.com/docs/4.x/wire-ignore

SEO/performance: https://github.com/ralphjsmit/laravel-seo, https://github.com/artesaos/seotools, https://github.com/spatie/laravel-sitemap, https://www.digitalapplied.com/blog/core-web-vitals-2026-inp-lcp-cls-optimization-guide, https://web.dev/articles/browser-level-image-lazy-loading
