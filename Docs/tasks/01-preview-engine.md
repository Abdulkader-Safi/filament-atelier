# 01. Live preview engine

> Spike first. This is the reason the PRD was rewritten and the only part of the plan with real unknowns.

## What it is

An iframe in the middle of the editor showing the page as it will actually ship, updating as the client types. Behind it is a preview route that renders the current draft block tree through the same Blade views and the same layout the public site uses, gated by a signed URL and always `noindex`.

Two things make it a preview rather than a screenshot: it refreshes without saving and without reloading the editor, and it loads the public stylesheet at a real viewport width.

## Why we're building it

Without it this is a stack of collapsible forms, which Fabricator already does for free. A form full of collapsed block panels tells a client nothing about whether their headline wraps onto three lines, whether two cards sit unevenly, or whether the Arabic version reads right. They save, open the site in another tab, look, come back, fix, save again. The preview removes that loop, and it's the whole reason for building the plugin at all.

## How it should feel

Like typing into the page. Pause typing, and about half a second later the section you're editing is different, in place, with the page still scrolled where you left it. No spinner covering the canvas, no jump back to the top, no flash of white between renders.

It should feel trustworthy above all. The moment the client finds one thing that looks different in the preview than on the live site, they stop believing the preview and go back to opening a second tab. Fidelity beats speed here.

## In the dashboard

The middle pane of the page editor. Toolbar above it has the width switcher (desktop, tablet, mobile) and the language switcher. Nothing to configure, nothing to turn on. There is no "enable preview" checkbox, unlike filament-peek, because a preview you have to opt into is a preview nobody uses.

## Tasks

- [!] Read `pboivin/filament-peek`'s refresh strategy before writing ours. Skipped: the swap-the-canvas approach below came out simpler than Peek's, and the render cost turned out not to be the problem. Revisit only if per-section refresh (v2) needs it.
- [x] Preview route: `temporarySignedRoute` to a controller that renders the draft tree through the public layout.
- [x] Force `noindex` and skip analytics on the preview route.
- [x] Custom Filament page with a three-pane layout, hardcoded to one page and two blocks for the spike.
- [x] Iframe loads the preview route, isolated from panel CSS.
- [x] Debounced refresh, around 500ms after the user stops typing.
- [x] Only fields marked reactive trigger a refresh.
- [x] Preserve scroll position across refreshes.
- [x] Width switcher: constrain the iframe to fixed desktop, tablet and mobile widths, not to the pane's own width.
- [x] Test with 12 real sections, not two. Measure the refresh time.
- [x] Write down what the spike proved or didn't, in this file, before moving to 02.

## Done when

- Editing a field updates the iframe within 1 second of pausing, with no save and no editor reload (PRD criterion 2).
- The preview uses the public stylesheet, and a heading pushed to a third line is visible before saving (PRD criterion 5).
- It still feels live on a 12-section page.

## Known risk

Full-page render per refresh is the naive approach and it may not hold. v2's answer is rendering the single changed block and swapping it into the iframe. If the spike is slow, decide here whether to pull that forward rather than shipping something laggy.

## What the spike proved (15 Aug 2026)

**The gate passes on everything measurable. One thing still needs a human.**

Built: signed `noindex` preview route rendering the draft through the public
layout, a three-pane Filament page, twelve real sections, English and Arabic,
and a width switcher.

**Render cost is not the problem, which was the whole worry.** A full
twelve-section render measured 16ms mean and 31ms worst of ten, on `artisan
serve` with no opcache tuning. The PRD assumed full-page rendering per
keystroke might be too expensive and parked per-section refresh in v2 as the
fix. On this evidence that fix is not needed for v1, and probably not for
pages several times this size. Revisit if a page gets genuinely heavy, for
example a gallery pulling many records.

**Refresh works by swapping the canvas, not reloading the iframe.** On change,
Livewire persists the draft and dispatches `atelier-refresh`. The editor
fetches the preview URL and replaces the contents of `[data-atelier-canvas]`
inside the iframe. Scroll position survives for free because the document is
never reloaded, and the stylesheet is never re-fetched, so there's no flash.
This came out simpler than reloading and restoring scroll.

**Fidelity is real.** The preview loads the example app's compiled
`resources/css/app.css`, the same file the public page will load. Tailwind
does not scan package views by default, so the host app needs an `@source`
line pointing at the package. That is a required install step and the plugin
has to document it, because the failure mode is silent: unstyled blocks with
no error.

**Still unproven: whether it feels live to a person.** 16ms of render sits
inside a chain of field debounce, Livewire round trip, fetch and DOM swap.
The numbers say it should feel immediate. Nobody has typed into it yet. Do
that before feature 04 builds on this.

**Deliberately not built here**, since they belong to later features: drag to
reorder (04), the `page_slugs` table and public routing (02), and per-locale
fallback behaviour when a translation is missing (05). The hero currently
renders no heading at all in Arabic when the Arabic value is absent, which is
a decision 05 has to make rather than inherit.
