# 01. Live preview engine

> Spike first. This is the reason the PRD was rewritten and the only part of the plan with real unknowns.

## What it is

An iframe in the middle of the editor showing the page as it will actually ship, updating as the client types. Behind it is a preview route that renders the current draft block tree through the same Blade views and the same layout the public site uses, gated by a signed URL and always `noindex`.

Two things make it a preview rather than a screenshot: it refreshes without saving and without reloading the editor, and it loads the public stylesheet at a real viewport width.

## Why we're building it

Without it this is Fabricator, which already exists and is free. A form full of collapsed block panels tells a client nothing about whether their headline wraps onto three lines, whether two cards sit unevenly, or whether the Arabic version reads right. They save, open the site in another tab, look, come back, fix, save again. The preview removes that loop, and it's the whole reason for building the plugin at all.

## How it should feel

Like typing into the page. Pause typing, and about half a second later the section you're editing is different, in place, with the page still scrolled where you left it. No spinner covering the canvas, no jump back to the top, no flash of white between renders.

It should feel trustworthy above all. The moment the client finds one thing that looks different in the preview than on the live site, they stop believing the preview and go back to opening a second tab. Fidelity beats speed here.

## In the dashboard

The middle pane of the page editor. Toolbar above it has the width switcher (desktop, tablet, mobile) and the language switcher. Nothing to configure, nothing to turn on. There is no "enable preview" checkbox, unlike filament-peek, because a preview you have to opt into is a preview nobody uses.

## Tasks

- [ ] Read `pboivin/filament-peek`'s refresh strategy before writing ours. We're not using it, but it solved this problem once.
- [ ] Preview route: `temporarySignedRoute` to a controller that renders the draft tree through the public layout.
- [ ] Force `noindex` and skip analytics on the preview route.
- [ ] Custom Filament page with a three-pane layout, hardcoded to one page and two blocks for the spike.
- [ ] Iframe loads the preview route, isolated from panel CSS.
- [ ] Debounced refresh, around 500ms after the user stops typing.
- [ ] Only fields marked reactive trigger a refresh.
- [ ] Preserve scroll position across refreshes.
- [ ] Width switcher: constrain the iframe to fixed desktop, tablet and mobile widths, not to the pane's own width.
- [ ] Test with 12 real sections, not two. Measure the refresh time.
- [ ] Write down what the spike proved or didn't, in this file, before moving to 02.

## Done when

- Editing a field updates the iframe within 1 second of pausing, with no save and no editor reload (PRD criterion 2).
- The preview uses the public stylesheet, and a heading pushed to a third line is visible before saving (PRD criterion 5).
- It still feels live on a 12-section page.

## Known risk

Full-page render per refresh is the naive approach and it may not hold. v2's answer is rendering the single changed block and swapping it into the iframe. If the spike is slow, decide here whether to pull that forward rather than shipping something laggy.
