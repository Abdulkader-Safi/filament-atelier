# 11. SEO depth (v0.2.0)

> Written 18 Aug 2026. Feature 07 shipped the meta half and nothing else. This file is
> the gap list for v0.2.0, audited against the code rather than against 07's checklist.

## What it is

The rest of feature 07, plus what the audit turned up that 07 never listed: a sitemap,
JSON-LD, per-page indexing control, redirects when a slug changes, and the head markup
fixes that decide whether a page renders correctly when a crawler or a share card reads it.

## Why we're building it

The PRD's whole argument for Blade SSR is SEO. Right now a page emits a title, a
description, a canonical, hreflang and Open Graph tags, and that is the entire surface.
There is no sitemap, no robots.txt, no structured data, no way to keep a page out of the
index, and no redirect when someone renames a slug on a page that is already ranking.
A client can rename a slug today and silently 404 every inbound link to it.

The second reason is narrower and worse: every meta tag lives inside
`atelier::layouts.site`. `config('atelier.layout')` invites a host app to swap that view,
and any app that does loses the entire head. The SEO layer is only shipped for sites that
use the stock layout, which is not the sites we build.

## How it should feel

Correct by default, invisible until someone wants control. A client who fills in nothing
should still get a complete head, a valid sitemap entry and correct structured data,
because everything derivable from the page is derived. Someone who does want control gets
a small set of real switches, not a scorecard.

Nothing here adds a traffic-light widget or a keyword-density meter. That stands from 07.

## In the dashboard

Additions to the existing per-locale SEO tab in page settings:

- **Search engines** toggle group: index / noindex, follow / nofollow. Default index, follow.
- **Structured data type** select: none, WebPage, Article, Product, FAQPage, Organization.
  Fields for the type appear underneath, and FAQPage takes its content from the FAQ block
  rather than asking for it twice.
- **Priority** and **change frequency** for the sitemap, both optional, both hidden behind
  a collapsed advanced section.

Page-level, not per locale:

- A **Redirects** relation on the page, listing the old slugs that now 301 here. It fills
  itself when a slug changes; the row is only there so someone can delete one.

Panel-level, in a settings page or config:

- Site name, default share image, Twitter handle, and the organisation details that feed
  `Organization` JSON-LD. These are site constants, not page fields, and asking for them
  per page is why every WordPress SEO plugin feels like work.

## Tasks

### Head markup, and getting it out of the layout

- [ ] Extract the head block from `resources/views/layouts/site.blade.php` into
      `atelier::partials.meta` and include it from the layout. A custom layout then gets
      the full head with one `@include`.
- [ ] Document the include in `Docs/installation.md`, next to `atelier.layout`.
- [ ] `og:site_name`, `og:locale` and `og:locale:alternate` from the configured locales.
- [ ] `og:image:width`, `og:image:height` and `og:image:alt`. Read the dimensions off the
      stored file rather than asking the client for them.
- [ ] `og:type` follows the structured data type instead of being hardcoded `website`.
- [ ] `hreflang="x-default"` pointing at the default locale.
- [ ] `twitter:image`, `twitter:site` and `twitter:creator`.
- [ ] `->visibility('public')` on the og_image upload in `PageResource`. It is missing
      there and present in `Media::upload()`, so the field breaks on S3 and works locally,
      which is the worst way for it to break.

### Fallbacks

- [ ] Meta description falls back to the first paragraph of text in the published tree.
- [ ] OG image falls back to the first image in the published tree, then to the site
      default. Today an unfilled field means no tag at all, so a share card shows nothing.
- [ ] Fallbacks are computed at render, not written into the field, so the placeholder
      still shows the client what will be used.

### Indexing control

- [ ] `noindex` / `nofollow` per page per locale, emitted as `<meta name="robots">`.
- [ ] A noindex page is excluded from the sitemap. One switch, both effects.
- [ ] `/` and `/{default}/home` both serve the home page today. Decide one, 301 the other.

### Structured data

- [ ] JSON-LD emitted from the same partial as the meta tags.
- [ ] `FAQPage` generated from `FaqBlock`'s repeater. The content is already structured and
      already per-locale, so this is close to free and is the highest-value item here.
- [ ] `Organization` or `WebSite` on the home page, from the panel-level settings.
- [ ] `BreadcrumbList` once there is a page hierarchy. Blocked: pages are flat today.
- [ ] Structured data type select on the page, feeding the emitter.
- [ ] Validate against Google's Rich Results Test before this is called done.

### Sitemap and robots

- [ ] `spatie/laravel-sitemap` as a dependency, or a route that builds the XML directly.
      A sitemap of published pages is about forty lines, and the package brings a crawler
      we do not need. Decide before starting.
- [ ] `/sitemap.xml` route, both locales, with `xhtml:link` hreflang alternates per entry.
- [ ] `lastmod` from `published_at`, which the table already stores and nothing reads.
- [ ] Drafts, noindexed pages and preview URLs excluded.
- [ ] `robots.txt` published as a stub pointing at the sitemap, and disallowing the panel
      and the preview route.
- [ ] Regenerated or cache-busted on publish and unpublish, not on a schedule.

### Redirects

- [ ] `atelier_redirects` table: from slug, locale, page id, status code.
- [ ] Changing a slug writes a 301 from the old one. This is the silent link-rot fix and
      it is the reason this section exists.
- [ ] The public route checks redirects before it 404s.
- [ ] A redirect is dropped when a new page claims that slug, so the two never fight.

### Block markup

- [ ] Hero renders `<h1>` unconditionally. Two heroes on a page means two `h1`s. Add a
      heading level control, defaulting to `h1` for the first hero and `h2` after it.
- [ ] Hero's image is a CSS background, so it is invisible to image search, carries no alt
      text and cannot be preloaded. Move it to a real `<img>` behind the content.
- [ ] Above-the-fold images are `loading="lazy"` like everything else, which delays the
      LCP element. First block on the page renders `loading="eager"` and `fetchpriority="high"`.
- [ ] `width` and `height` are hardcoded per view (1600x900, 800x600, 80x80). If the upload
      is a different ratio the attributes cause the layout shift they exist to prevent.
      Read them from the file on upload and store them alongside the path.
- [ ] Alt text is optional everywhere and empty by default. Keep it optional, because
      forcing alt text on decorative images is worse than leaving it blank, but surface
      unfilled alt in the editor rather than in a validation error.

## Done when

- A page with no SEO fields filled still emits a complete head, a sitemap entry and valid
  structured data.
- `curl` on a page served through a custom layout returns the same head as the stock one.
- Rich Results Test passes on a page with an FAQ block, with no hand-written JSON.
- The sitemap contains every published, indexable page in both locales and nothing else.
- Renaming a slug on a published page leaves the old URL returning 301, not 404.
- PRD criterion 9 is fully met, which it is not today.

## Note

The sitemap and the slug redirect are the two that change what a client can break without
noticing. Everything else on this list improves a page that already works.
