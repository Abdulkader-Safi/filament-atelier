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

| #                          | Feature                       | Why it's here                                                                   |
| -------------------------- | ----------------------------- | ------------------------------------------------------------------------------- |
| [01](01-preview-engine.md) | Live preview engine           | The only genuinely hard part. Prove it or redesign before anything else exists. |
| [02](02-foundation.md)     | Foundation                    | Plugin packaging, tables, page model, block registry, design tokens.            |
| [03](03-block-library.md)  | Block library and rendering   | The `Block` contract plus the marketing set. Nothing to edit until these exist. |
| [04](04-editor.md)         | Three-pane editor             | The product. Section list, preview, settings pane.                              |
| [05](05-bilingual.md)      | Bilingual and RTL             | Per-locale attributes, `/ar/{slug}`, `dir="rtl"`, hreflang.                     |
| [06](06-draft-publish.md)  | Draft, publish, preview links | Editing must never touch the live page.                                         |
| [07](07-seo-sitemap.md)    | SEO and sitemap               | Per-locale meta, JSON-LD, sitemap across both locales.                          |
| [08](08-animation.md)      | Animation                     | GSAP presets picked from a dropdown, Livewire-safe.                             |
| [09](09-performance.md)    | Performance                   | Conditional assets, CSS cache, Core Web Vitals.                                 |
| [10](10-verification.md)   | Verification                  | The runs that prove the success criteria, not a vibe check.                     |

01 is a spike. If it doesn't feel live on a page with 12 sections, stop and redesign rather than continuing down the list.

## Status legend

- `- [ ]` not started
- `- [x]` done
- `- [~]` in progress
- `- [!]` blocked, with the reason on the line

## Not in this folder

Block types authored from the panel, Gutenberg-style. That's v2 and the research sits at the bottom of `prd.md`. Don't start it early.
