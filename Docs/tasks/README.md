# Tasks

One file per feature. Build order is the file order. Nothing here starts until `prd.md` is signed off.

Every file follows the same shape:

What it is names the thing itself, in plain terms. Why we're building it says what breaks or stays painful without it. How it should feel sets the experience we're aiming at, the acceptance bar a checklist can't hold on its own. In the dashboard says what the client or developer actually clicks, and where. Tasks is the checklist. Done when is the testable end state, tied back to the PRD's success criteria.

## Build order

Status as of 18 Aug 2026, audited against the code rather than against memory. Every file
carries a dated banner saying what its own gaps are.

Work on the repository rather than the package (CI, the security policy, the changelog,
the documentation surface) has no feature file of its own and is tracked at the bottom of
[02](02-foundation.md).

| #                          | Feature                       | Status         | Why it's here                                                                   |
| -------------------------- | ----------------------------- | -------------- | ------------------------------------------------------------------------------- |
| [01](01-preview-engine.md) | Live preview engine           | Done           | The only genuinely hard part. Prove it or redesign before anything else exists. |
| [02](02-foundation.md)     | Foundation                    | Mostly done    | Plugin packaging, tables, page model, block registry, design tokens.            |
| [03](03-block-library.md)  | Block library and rendering   | Mostly done    | The `Block` contract plus the marketing set. Nothing to edit until these exist. |
| [04](04-editor.md)         | Three-pane editor             | Mostly done    | The product. Section list, preview, settings pane.                              |
| [05](05-bilingual.md)      | Bilingual and RTL             | Mostly done    | Per-locale attributes, `/ar/{slug}`, `dir="rtl"`, hreflang.                     |
| [06](06-draft-publish.md)  | Draft, publish, preview links | Half done      | Editing must never touch the live page.                                         |
| [07](07-seo-sitemap.md)    | SEO and sitemap               | Half done      | Per-locale meta, JSON-LD, sitemap across both locales.                          |
| [08](08-animation.md)      | Animation                     | Dropped        | Animation lives in each block's own view. Nothing was built.                    |
| [09](09-performance.md)    | Performance                   | Barely started | Conditional assets, CSS cache, Core Web Vitals.                                 |
| [10](10-verification.md)   | Verification                  | Not started    | The runs that prove the success criteria, not a vibe check.                     |
| [11](11-seo-v0.2.md)       | SEO depth (v0.2.0)            | Mostly done    | Sitemap shipped. Robots, redirects, noindex too. JSON-LD moved to 12.           |
| [12](12-structured-data.md)| Structured data (JSON-LD)     | Planned        | The full schema list, three sources, and the site settings screen it needs.     |
| [13](13-menu-manager.md)   | Menu manager                  | Mostly done    | Navigation editable from the panel. On a branch; blocked on 03 for a nav block. |
| [14](14-v1-release.md)     | Release 1.0.0                 | Not started    | The gates between 0.3.6 and a stable tag. Half decisions, half release work.     |

### What blocks what

Four gaps hold up more than their own feature:

- ~~**`supports()` is declared and read by nothing** (03).~~ Built 18 Aug 2026 with
  background and padding. 09's per-element styles attach through it when they arrive.
- ~~**Design tokens do not exist** (02).~~ Built 18 Aug 2026, emitted to both the preview
  and the public page from the shared layout.
- ~~**`page_revisions` was never created** (02).~~ Built 18 Aug 2026. A browsing and
  restore UI is still not built, but the data is kept.
- **No sitemap** (07), so 06's regenerate-on-publish is blocked.

### Fixed since this audit

`/services/web-design` returned 200 and rendered the `services` page, because
`PageController` discarded every path segment after the first. Fixed 18 Aug 2026: a slug
is the whole path after the locale.

The meta tags were trapped inside `atelier::layouts.site`, so pointing `atelier.layout` at
a host app's own view lost the entire head. Fixed 18 Aug 2026 with
`atelier::partials.meta` and `atelier::partials.tokens`.

01 is a spike. If it doesn't feel live on a page with 12 sections, stop. Redesign instead of continuing down the list.

## Status legend

- `- [ ]` not started
- `- [x]` done
- `- [~]` in progress
- `- [!]` blocked, with the reason on the line

## Not in this folder

Block types authored from the panel, Gutenberg-style. That's v2 and the research sits at the bottom of `prd.md`. Don't start it early.
