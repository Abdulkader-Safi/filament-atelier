# Tasks

One file per feature. Build order is the file order. Nothing here starts until `prd.md` is signed off.

Every file follows the same shape:

- **What it is** — the thing itself, in plain terms.
- **Why we're building it** — what breaks or stays painful without it.
- **How it should feel** — the experience we're aiming at. This is the acceptance bar that a checklist can't hold.
- **In the dashboard** — what the client or developer actually clicks, and where.
- **Tasks** — the checklist.
- **Done when** — the testable end state, tied back to the PRD's success criteria.

## Build order

Status as of 18 Aug 2026, audited against the code rather than against memory. Every file
carries a dated banner saying what its own gaps are.

| #                          | Feature                       | Status         | Why it's here                                                                   |
| -------------------------- | ----------------------------- | -------------- | ------------------------------------------------------------------------------- |
| [01](01-preview-engine.md) | Live preview engine           | Done           | The only genuinely hard part. Prove it or redesign before anything else exists. |
| [02](02-foundation.md)     | Foundation                    | Mostly done    | Plugin packaging, tables, page model, block registry, design tokens.            |
| [03](03-block-library.md)  | Block library and rendering   | Mostly done    | The `Block` contract plus the marketing set. Nothing to edit until these exist. |
| [04](04-editor.md)         | Three-pane editor             | Mostly done    | The product. Section list, preview, settings pane.                              |
| [05](05-bilingual.md)      | Bilingual and RTL             | Mostly done    | Per-locale attributes, `/ar/{slug}`, `dir="rtl"`, hreflang.                     |
| [06](06-draft-publish.md)  | Draft, publish, preview links | Half done      | Editing must never touch the live page.                                         |
| [07](07-seo-sitemap.md)    | SEO and sitemap               | Half done      | Per-locale meta, JSON-LD, sitemap across both locales.                          |
| [08](08-animation.md)      | Animation                     | Not started    | GSAP presets picked from a dropdown, Livewire-safe.                             |
| [09](09-performance.md)    | Performance                   | Barely started | Conditional assets, CSS cache, Core Web Vitals.                                 |
| [10](10-verification.md)   | Verification                  | Not started    | The runs that prove the success criteria, not a vibe check.                     |
| [11](11-seo-v0.2.md)       | SEO depth (v0.2.0)            | Not started    | The half of 07 that never shipped: sitemap, JSON-LD, redirects, robots.         |

### What blocks what

Four gaps hold up more than their own feature:

- **`supports()` is declared and read by nothing** (03). Feature 08's animation controls
  and 09's per-element styles both attach through it. Neither can start until it exists.
- **Design tokens do not exist** (02). 05's Arabic font stack and 09's styling story both
  assume them, and the PRD's argument that the preview stays honest rests on them.
- **`page_revisions` was never created** (02), so all of 06's revision work is blocked and
  the editor's delete really is permanent.
- **No sitemap** (07), so 06's regenerate-on-publish is blocked.

### The one bug worth fixing before anything else

`/services/web-design` returns 200 and renders the `services` page. `PageController`
discards every path segment after the first. A nested slug is unreachable and the URL
serves the wrong page rather than a 404. Written up in 02 under routing.

01 is a spike. If it doesn't feel live on a page with 12 sections, stop and redesign rather than continuing down the list.

## Status legend

- `- [ ]` not started
- `- [x]` done
- `- [~]` in progress
- `- [!]` blocked, with the reason on the line

## Not in this folder

Block types authored from the panel, Gutenberg-style. That's v2 and the research sits at the bottom of `prd.md`. Don't start it early.
