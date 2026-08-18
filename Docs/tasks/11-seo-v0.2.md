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

- [x] Extract the head block from `resources/views/layouts/site.blade.php` into
      `atelier::partials.meta` and include it from the layout. A custom layout then gets
      the full head with one `@include`. Done 18 Aug 2026, with a second
      `atelier::partials.tokens` for the design tokens, because those have to sit after
      the host app's stylesheet and the meta does not care where it goes. Three tests,
      including one asserting the stock layout and a host app's layout emit the same head.
- [x] Document the include in `Docs/installation.md`, next to `atelier.layout`. Also in the wiki.
- [ ] `og:site_name`, `og:locale` and `og:locale:alternate` from the configured locales.
- [ ] `og:image:width`, `og:image:height` and `og:image:alt`. Read the dimensions off the
      stored file rather than asking the client for them.
- [ ] `og:type` follows the structured data type instead of being hardcoded `website`.
- [ ] `hreflang="x-default"` pointing at the default locale.
- [ ] `twitter:image`, `twitter:site` and `twitter:creator`.
- [x] `->visibility('public')` on the og_image upload in `PageResource`. It is missing
      there and present in `Media::upload()`, so the field breaks on S3 and works locally,
      which is the worst way for it to break. Fixed 18 Aug 2026.

### Fallbacks

- [ ] Meta description falls back to the first paragraph of text in the published tree.
- [ ] OG image falls back to the first image in the published tree, then to the site
      default. Today an unfilled field means no tag at all, so a share card shows nothing.
- [ ] Fallbacks are computed at render, not written into the field, so the placeholder
      still shows the client what will be used.

### Indexing control

- [x] `noindex` / `nofollow` per page per locale, emitted as `<meta name="robots">`. Two
      independent toggles: a page can be indexed and still not pass link credit. The tag is
      emitted only when it says something, because `index, follow` is what every crawler
      assumes anyway.
- [x] A noindex page is excluded from the sitemap. One switch, both effects. Decided per
      locale, so an English page can be listed while its Arabic translation is not, and the
      alternates drop with it.
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

- [x] `spatie/laravel-sitemap` as a dependency, or a route that builds the XML directly.
      **Decided: written, no dependency.** The package's job is crawling a site to discover
      URLs, and we already know every URL we have. `Safi\Atelier\Sitemap` is the forty lines
      that were left.
- [x] `/sitemap.xml` route, both locales, with `xhtml:link` hreflang alternates per entry.
- [x] URLs from outside Atelier. Added 18 Aug 2026, and not in the original plan: a client
      site is rarely only Atelier pages, and a blog or services resource on its own panel
      tab has URLs that belong in the same sitemap. `SitemapRegistry` takes closures or
      invokable class names through `AtelierPlugin::sitemap()`, the same shape as
      `->blocks()`. Sources run at request time, entries dedupe on `loc`, and a source that
      throws fails the sitemap rather than silently shrinking it.
- [x] `lastmod` from `published_at`, which the table already stores and nothing reads.
- [x] Drafts, noindexed pages and preview URLs excluded.
- [~] `robots.txt` published as a stub pointing at the sitemap, and disallowing the panel
      and the preview route. Served from a route rather than published as a file. The catch
      is documented rather than solved: Laravel ships a real `public/robots.txt`, and the
      web server answers that before Laravel runs, so the host app deletes it or copies the
      `Sitemap:` line across. A package cannot tell which one the app meant.
- [!] Regenerated or cache-busted on publish and unpublish, not on a schedule. Nothing to
      regenerate: the sitemap is built per request from two queries. Revisit if a site gets
      big enough for that to show up in a profile.

### Redirects

- [x] `atelier_page_redirects` table: locale, from slug, page id, status code, unique on (locale, from slug). Named for the convention the other tables already use.
- [x] Changing a slug writes a 301 from the old one. This is the silent link-rot fix and
      it is the reason this section exists. Done 18 Aug 2026 in `Page::setSlugs()`, which
      is the one path both the create and the edit screen go through.
- [x] The public route checks redirects before it 404s. An unpublished target still 404s,
      because redirecting someone to a 404 is worse than the 404 itself.
- [x] A redirect is dropped when a new page claims that slug, so the two never fight.
      Whoever claims a slug owns it.

The target is stored as the page rather than as a slug, which was not in the original
plan and is what makes chains impossible: a page renamed twice leaves two rows both
resolving to wherever it lives now, so there is nothing to follow and nothing to clean up.
Six tests.

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
