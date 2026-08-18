# 05. Bilingual and RTL

> **Status, 18 Aug 2026.** Data, routing and rendering are done. What is missing is the
> editor's honesty about translation: nothing shows which sections are untranslated, and
> nothing marks a field as shared across locales. The Arabic font stack is missing too,
> because design tokens were never built.

## What it is

English and Arabic on every page. Translatable block attributes hold a map keyed by locale inside the same block tree:

```json
{
  "id": "b_8f3a",
  "type": "hero",
  "attributes": {
    "heading": { "en": "Welcome", "ar": "أهلا بك" },
    "background": { "token": "color.primary" }
  }
}
```

One tree, one section order, translated text. Arabic is mirrored by `dir="rtl"` and CSS logical properties, not by reordering blocks.

## Why we're building it

dsrpt's GCC clients need Arabic. More to the point, retrofitting locales into a stored block tree later is a migration over every page on every client site, which is exactly the kind of thing that never gets done. It's cheap now and expensive in six months.

The known cost of this design: Arabic cannot have a different section order from English. For a marketing site that's the right trade, and it stops the two languages drifting into different pages.

## How it should feel

Like one page with a switch, not two pages to maintain. Flip to Arabic in the toolbar and the same sections are there in the same order, with the text fields showing Arabic values and the preview flipping to RTL. Flip back and nothing was lost.

Untranslated fields should be visible as untranslated, not silently empty and not silently falling back to English in a way that hides the gap.

## In the dashboard

- **Toolbar toggle: EN / AR.** Switching changes which locale's values the settings pane edits and which locale the preview renders.
- Non-translatable settings (a background token, padding, an animation preset) are shared across locales and should be marked as such, so nobody wonders why editing them in Arabic changed the English page.
- Each locale has its own slug, edited in the page settings.
- Each locale has its own SEO fields (feature 07).

## Tasks

### Data

- [x] Per-locale maps on translatable attributes, with a way for a block's `schema()` to declare which fields are translatable. Declared by a static `translatable()` on the block rather than inside `schema()`, which keeps the schema a plain Filament schema.
- [x] Sensible behaviour when a locale's value is missing: decide between empty and English fallback, then apply it everywhere consistently. **Decided: fall back to the default locale.** `Renderer::localise()` does it in one place, so it is consistent by construction. This closes the question 01 left open when the Arabic hero rendered no heading.
- [x] `page_slugs` rows per locale, unique on (locale, slug).

### Routing and rendering

- [x] `/{slug}` for English, `/ar/{slug}` for Arabic.
- [x] Locale set from the route, not from a session or a cookie.
- [x] `dir="rtl"` and `lang="ar"` on the Arabic render.
- [x] `hreflang` tags on both, pointing at each other. `x-default` is still missing, carried to 11.
- [x] Arabic font stack in the design tokens. `font.arabic`, swapped in by a `[dir="rtl"]` rule rather than by locale code, so any RTL language gets it.

### Editor

- [x] Locale toggle in the toolbar.
- [x] Settings pane edits the selected locale's values. `flattenLocale()` and `mergeLocale()` keep the other locale untouched.
- [x] Preview renders the selected locale, including RTL.
- [ ] Shared vs translatable fields visually distinguished. Nothing marks them, so editing a shared field while on Arabic silently changes English too. `translatable()` already knows which is which, so this is a label, not a mechanism.
- [ ] An at-a-glance indicator of which sections have untranslated content. Nothing shows it, and the default-locale fallback actively hides it: an untranslated Arabic section renders English and looks finished.

### Blocks

- [~] Every block in the marketing set audited in RTL: logical properties, no hardcoded `left`/`right`, icons and arrows that need mirroring get mirrored. Grepped 18 Aug 2026: no physical direction utilities in any block view. The visual pass is still a human run (10, run 4).

## Done when

- Every page exists at `/{slug}` and `/ar/{slug}` with hreflang pointing at each other, and `dir="rtl"` on the Arabic side (PRD criterion 7).
- Content is present server-side in both locales with JS disabled (PRD criterion 6).
- Switching locale in the editor preserves everything and requires no reload.
