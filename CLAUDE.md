# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repository is right now

Specification only. There is no application code, no `composer.json`, no `package.json`, no test suite. One commit, and everything in it is Markdown under `Docs/`.

The PRD is awaiting sign-off and no build has started. Don't scaffold the package, add dependencies, or write PHP unless asked. When code does land, this file needs a real commands section, because none of the commands below exist yet.

## Planned stack

Laravel 12/13, Filament v4 with a `^4.0|^5.0` constraint, Livewire 4, Alpine 3, Tailwind 4, GSAP 3.15. Built as a composer package `safi/filament-atelier` that installs into a client's Laravel app. Built on top of `Z3d0X/filament-fabricator` for the page model, slug resolution and front-end routing.

## Document precedence

Read in this order. Later documents override earlier ones and say so in a banner at the top.

1. `Docs/prd.md` — current spec, v2, the authority. Five decisions from 14 Aug 2026.
2. `Docs/tasks/` — the work breakdown, one file per feature in build order. `tasks/README.md` is the index.
3. `Docs/architecture.md` — technical design from 22 June. **Partly superseded.** Its banner lists what's dead: the Builder-field editor, single-locale storage, the slug column. Its Phasing section and its Builder-field recommendation are explicitly marked reversed. Everything else in it still holds.
4. `Docs/research/` — four background briefs (Gutenberg block model, Elementor architecture, Filament plugin development, preview/drafts/GSAP/SEO). Source material, not decisions.

`Docs/quick-win-options.md` is about shipping a client on stock Fabricator, not about this build, and is marked partly stale. Its value here is section 1, on why Fabricator is the foundation.

## The architecture in one pass

**A page is a JSON tree of typed blocks, rendered by Blade at request time.** Gutenberg's data model, Elementor's render approach, neither one's baggage. Gutenberg stores rendered HTML and pays for it with attribute sourcing, block validation and deprecation chains. We skip all three by storing structured JSON and treating every block as dynamic.

**A block type is one PHP class plus one Blade view.** The class declares `type()`, `label()`, `icon()`, `category()`, `schema()` and `supports()`. `schema()` returns a plain Filament schema, which is why the whole control system comes free. Registered at boot into a `BlockRegistry`. Adding a block must never require editing a file inside the plugin.

**Three tables carry the model:** `pages` with `draft_content` and `published_content` as separate JSON columns, `page_slugs` (locale, slug, unique) because a JSON slug map can't carry a unique index, and `page_revisions` for snapshots. Editing writes the draft. Publishing copies draft to published. The public route reads published only.

**Translatable attributes hold a per-locale map inside the same tree**, not separate trees per language:

```json
"heading": { "en": "Welcome", "ar": "أهلا بك" }
```

One structure, one section order, translated text. Arabic mirrors via `dir="rtl"` and CSS logical properties. The accepted cost is that Arabic cannot have a different section order from English.

**Design tokens are stored as references, not literals** (`{ "token": "color.primary" }`), emitted as CSS custom properties, and read by both the editor preview and the public page. This is what keeps the preview honest.

## Non-negotiables

These are decisions with reasons behind them, not preferences. Changing one means reopening the PRD.

- **The public render is always Blade SSR.** Never inject blocks client-side. The whole SEO argument rests on this.
- **The preview and the public page share one render path.** Same Blade views, same layout, same stylesheet, different data source. A second rendering path for the editor defeats the point of the preview.
- **Block types are code-defined in v1.** Authoring block types from the panel is v2, and its research sits at the bottom of `prd.md`. Don't start it early. The raw-HTML block is the v1 escape hatch for one-off markup.
- **Never `Blade::render()` user input.** It compiles to PHP and turns a textarea into remote code execution. This applies whenever the v2 feature gets built.
- **GSAP is free for commercial use but is not MIT and not open source.** Don't write "MIT" next to it in a README, a proposal or a composer file.
- **Conditional per-block asset loading from day one.** Retrofitting it across twelve blocks is a rewrite of all twelve.
- **Feature 01 is a gate.** If the preview loop doesn't feel live on a twelve-section page, stop and redesign rather than continuing down the task list.

## Working on the docs

`Docs/tasks/*.md` share a fixed shape: what it is, why we're building it, how it should feel, in the dashboard, tasks, done when. Keep new files in that shape. Status markers are `- [ ]`, `- [x]`, `- [~]`, `- [!]` with a reason.

Task checklists reference PRD success criteria by number. If a criterion changes in `prd.md`, the "Done when" sections that cite it need updating too.

When a decision is reversed, add a dated banner and keep the original reasoning rather than deleting it. Both `architecture.md` and `prd.md` already do this, and the history is why the current decisions are legible.

Prose in this repo follows the writing rules in the user's global CLAUDE.md. Notably: no em-dashes, sentence case headings, and no hedging or padding.

## Open questions that block work

Four sit at the bottom of `prd.md`. Two of them block specific features and are flagged in the task files at the point they bite:

- **Contact form block destination** (real submissions table, email or CRM, versus presentational). Blocks `tasks/03-block-library.md`.
- **Filament version floor.** Supporting v4 and v5 costs compatibility work that's wasted if every dsrpt client app is on v5. Blocks `tasks/02-foundation.md`.

Ownership (Safi's package or dsrpt's IP) and distribution (private or public) don't block code but should settle before the first commit.
