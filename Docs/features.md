# Features, and how they compare to Gutenberg

> Written 15 Aug 2026 against the MVP. Everything marked "built" was verified running, not planned.
> Gutenberg details come from `research/gutenberg-block-model.md`.

The short version: Atelier copies Gutenberg's data model and skips its storage model. Gutenberg stores rendered HTML as the source of truth and pays for it with three subsystems Atelier simply doesn't have. What Gutenberg has that Atelier doesn't is mostly breadth and a decade of editor polish.

## What's built

### The page model

- A page is a JSON tree of typed blocks. Each node is `{ id, type, attributes, children, hidden }`.
- Blocks render through Blade at request time. Every block is what Gutenberg calls a dynamic block.
- Nesting is supported by the renderer through `children`, though no shipped block uses it yet.
- `draft_content` and `published_content` are separate columns. Editing writes the draft. Publishing copies it across. The public route reads published only, so an unfinished page cannot leak.
- Slugs live in their own table with a unique index on `(locale, slug)`.

### Blocks

- A block type is one PHP class plus one Blade view. The class declares `type`, `label`, `icon`, `category`, `schema`, `supports`, `translatable` and `defaults`.
- `schema()` returns a plain Filament schema, so the whole control system comes free: text, textarea, select, rich text, file upload, repeater, and anything else Filament has.
- Registered at boot into a `BlockRegistry`. Adding a block never means editing a file inside the plugin.
- Nine blocks ship: hero, features, rich text, image, gallery, logo wall, testimonials, FAQ, call to action.
- Repeaters inside a block are reorderable, so items sort within a section as well as sections sorting within a page.
- Images upload through a shared `Media` helper. Blocks store a path; the helper turns it into a URL.
- An unknown block type renders nothing publicly and a visible warning in the editor, rather than throwing.

### The editor

- Full-screen, outside the Filament panel chrome, opened from the page's settings screen.
- One sidebar. It lists sections, and selecting one swaps the same panel to that section's inspector.
- Add, duplicate, hide, delete and reorder sections. Hidden sections stay visible in the editor and vanish from the public page.
- Live preview in an iframe, rendering the real page through the public layout and the public stylesheet.
- Refresh works by fetching the preview and swapping the canvas contents, so scroll position survives and there's no stylesheet flash. A twelve-section render measures 16ms.
- Desktop, tablet and mobile width switcher, at fixed widths rather than whatever the pane happens to be.
- Locale switcher. Section rows are labelled by the block's own heading, not "Block 4".
- Clicking a section in the preview selects it in the sidebar.

### Bilingual

- English and Arabic, from one tree. Translatable attributes hold a per-locale map inside the same block.
- `dir="rtl"` and `lang` on the Arabic render, with hreflang pointing both ways.
- A missing translation falls back to the default locale, so a half-translated page reads as untranslated rather than broken.

### Pages and SEO

- Page settings screen: title, and a tab per locale holding slug, meta title, meta description, social share image and canonical.
- Public routes at `/{slug}` and `/{locale}/{slug}`. The host app's own routes still win.
- Canonical, hreflang, Open Graph and Twitter tags in the head. Meta title falls back to the page title.
- Drafts 404 on the public site.

### Packaging

- A composer package that installs into any Laravel 13 + Filament 5 app.
- Publishable config and migrations, and its own compiled stylesheet registered through `FilamentAsset`, so a consumer needs no build step.
- A demo seeder that creates Home, About and a draft Contact in both languages.

## Side by side with Gutenberg

### Where the two agree

| | Gutenberg | Atelier |
|---|---|---|
| Page as a tree of typed blocks | Yes | Yes |
| Declarative block manifest | `block.json` | PHP class with static methods |
| Block registry, type to definition | Yes | Yes |
| Per-block attribute schema | Yes | Filament schema |
| Nesting | `InnerBlocks` | `children` in the tree |
| Server-side render | Dynamic blocks only | Every block, always |
| Central design tokens | `theme.json` | Config file, emitted as CSS custom properties |
| Per-block feature opt-in | `supports` | `supports()` |
| Third-party blocks without core changes | Yes | Yes |

### Where Atelier is simpler on purpose

Each of these exists in Gutenberg only because it stores rendered HTML as the source of truth.

| Gutenberg | Atelier | Why |
|---|---|---|
| Attribute sourcing (`source`, `selector`, `query`) | Not needed | Attributes are fields in JSON, never scraped back out of markup |
| Block validation and "this block was modified externally" | Not needed | No stored markup to diff against a re-render |
| Deprecation and migration chains at runtime | Not needed | A block's attribute shape changes with an ordinary data migration |
| `save()` must be a pure function | Not needed | Rendering is request-time; the tree is the truth |
| Data split between HTML comments and markup | Not needed | One place for data |
| Static vs dynamic blocks as a decision | Not needed | Every block is dynamic |

This is the whole argument for the project's data model. Four of Gutenberg's hardest subsystems are consequences of one storage decision, and not making that decision deletes all four.

### Where Gutenberg is genuinely ahead

Honest list. Some of these are planned, some are not worth copying.

| Gutenberg | Atelier | Status |
|---|---|---|
| Drag to reorder | Arrow buttons | Next up |
| Edit text directly on the canvas | Fields in the sidebar | Not planned for v1. The preview is for judging, the sidebar is for editing |
| Block patterns, insert then edit independently | None | Worth copying, listed as v2 |
| Synced patterns, one source edits everywhere | None | Worth copying, listed as v2 |
| Block templates, the default blocks a new page starts with | None | Cheap, not built |
| Revision history with restore and diff | Publish overwrites | Snapshot table designed, no UI |
| Block transforms, turn a hero into a CTA | None | Not planned |
| Undo and redo | Browser only | Not built |
| Creating block types from the browser | Code only | v2, deliberately not started |
| Multi-select and bulk actions on blocks | One at a time | Not built |
| Copy and paste blocks between pages | None | Not built |
| An enormous third-party block ecosystem | Nine blocks and your own | Never going to compete, and doesn't need to |
| Full site editing, headers and templates in the editor | Layouts are code | Not planned |
| Media library with reuse and editing | Plain file upload | Not built |

### Where Atelier is ahead

| | Gutenberg | Atelier |
|---|---|---|
| Preview fidelity | The editor approximates the front end, and themes routinely disagree with it | The preview is the front end: same Blade, same layout, same stylesheet, different data source |
| Draft safety | Revisions and autosave, but the editor works on the live record | Two columns. The published page is a separate copy and cannot change until Publish |
| Bilingual | A plugin decision, usually one page per language | Built in. One tree, one section order, per-locale values, RTL |
| Controlling what editors can do | Blocks expose broad styling, and locking is a per-case fight | The block author decides every control that exists |
| Stack | PHP, but WordPress | Laravel, Filament, Livewire, a normal app |
| Output | Depends on the theme and plugins | Blade you wrote, no injected markup |

## The honest summary

Gutenberg's data model is better than its reputation and Atelier copies it closely. Gutenberg's storage model is a workaround for WordPress storing post content as HTML, and Atelier skips it entirely, which removes four subsystems rather than reimplementing them.

What Gutenberg has that this doesn't is the editor surface: drag, canvas editing, patterns, transforms, undo, multi-select, and ten years of edge cases. That's the part the research brief called out as the work you cannot shortcut, and it's where the remaining effort goes.

The trade Atelier makes deliberately: a client can arrange and fill sections, and can't invent new ones. In Gutenberg that's a constant fight against a permissive editor. Here it's the default, because the block author writes the schema.
