# 07. SEO and sitemap

## What it is

Per-locale meta fields on every page (title, description, OG image, canonical), JSON-LD structured data, and an auto-generated sitemap covering both locales. Meta and JSON-LD via `ralphjsmit/laravel-seo`, sitemap via `spatie/laravel-sitemap`.

## Why we're building it

It's the reason the whole architecture is server-rendered rather than a client-side canvas. dsrpt's pitch against WordPress and Elementor is a hand-coded site's performance and crawlability with a client-editable CMS on top. If the SEO layer is an afterthought, there's no pitch.

It also has to be editable by the client, because "ask a developer to change a meta description" is exactly the workflow we're removing.

## How it should feel

Present but out of the way. A client building a page should not have to think about SEO to publish something correct, and the fields should have sensible defaults derived from the page (title falls back to the page title, description to the first paragraph). Someone who does care should find real controls without a plugin marketplace.

Nothing here should feel like a scorecard. No traffic-light SEO widget nagging about keyword density.

## In the dashboard

An **SEO** tab or section in the page settings, per locale:

- Meta title, with the fallback shown as placeholder text rather than pre-filled.
- Meta description, same.
- OG image picker.
- Canonical URL, blank by default meaning the page's own clean URL.
- Structured data type (Article, Product, FAQPage, and so on), a select.

The sitemap needs no UI. It regenerates on publish.

## Tasks

### Meta

- [ ] Per-locale SEO fields on the page, stored so adding a locale later isn't a migration.
- [ ] `ralphjsmit/laravel-seo` wired into the public layout head.
- [ ] Fallbacks: title from the page title, description from the first text content on the page, OG image from the first image.
- [ ] Canonical set to the clean URL by default, overridable.
- [ ] `hreflang` between locales (built in feature 05, verified here).
- [ ] `noindex` on preview responses (built in feature 06, verified here).

### Structured data

- [ ] Structured data type select, feeding the package.
- [ ] `FAQPage` JSON-LD generated from the FAQ block's actual content, not typed twice.
- [ ] `Organization` or `WebSite` on the home page.
- [ ] Validate output against Google's Rich Results Test before calling this done.

### Sitemap

- [ ] `Page` implements `Sitemapable`.
- [ ] Both locale URLs in the sitemap, with hreflang alternates.
- [ ] Drafts, unpublished pages and preview URLs excluded.
- [ ] Regenerate on publish and unpublish.
- [ ] `robots.txt` pointing at it.

## Done when

- Each page has editable SEO fields per locale that render into the head, plus a sitemap covering both locales (PRD criterion 9).
- Rich Results Test passes on a page with an FAQ block.
- The sitemap contains no draft or preview URL.

## Note

The FAQ block feeding `FAQPage` schema automatically is the highest-value item on this list and the easiest to skip. Don't skip it.
