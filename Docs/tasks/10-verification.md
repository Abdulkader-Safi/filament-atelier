# 10. Verification

> **Status, 18 Aug 2026. Not started.** None of the nine runs has been done. The 29
> automated tests in `example/` cover fragments of runs 3 and 5, and no automated test
> can stand in for runs 1 and 2, which are the two that need someone who is not Safi.
> Runs 7, 8 and 9 cannot start yet: the sitemap, the animation layer and the page cache
> do not exist.

## What it is

The runs that prove the eleven success criteria in `prd.md`. Not a code review, not a test suite, though both should exist. These are end-to-end runs by the people the software is for, doing the thing the software is for.

## Why we're building it

Every criterion in the PRD was written as testable on purpose. This is where they get tested. The specific risk with a page builder is that it demos beautifully on a three-section page built by its author and falls apart on a real twelve-section client site built by someone who has never seen it.

Two of these runs need a person who isn't Safi. That's the point of them.

## How it should feel

Uncomfortable. If the non-technical run goes perfectly first time, either the tester was coached or the run was too easy. Watch, don't help, write down every moment they hesitate.

## In the dashboard

Nothing. This is the last gate before calling v1 done.

## The runs

### Run 1: the non-technical build

Someone who has not seen the editor builds a page with hero, features, testimonials, CTA, FAQ and contact, and publishes it. No help, no documentation.

- [ ] They complete it.
- [ ] Note every hesitation and every wrong click.
- [ ] Note whether they understood the difference between Save and Publish without being told.
- [ ] Covers PRD criteria 1, 3.

### Run 2: the developer adds a block

A developer who didn't build Atelier adds a new block type from the docs alone.

- [ ] One PHP class, one Blade view, one registration.
- [ ] It appears in the picker with working controls.
- [ ] No file inside Atelier was edited.
- [ ] Covers PRD criterion 4.

### Run 3: server-side rendering

- [ ] JavaScript disabled, `/{slug}` returns full page content.
- [ ] JavaScript disabled, `/ar/{slug}` returns full page content in Arabic.
- [ ] View source, not the rendered DOM.
- [ ] Covers PRD criterion 6.

### Run 4: bilingual and RTL

- [ ] Both URLs exist, hreflang points both ways.
- [ ] `dir="rtl"` on the Arabic side and the layout actually mirrors, checked visually on every block.
- [ ] Covers PRD criterion 7.

### Run 5: draft safety

- [ ] Edit a published page heavily, save repeatedly, confirm the live page is unchanged.
- [ ] Publish, confirm it changes.
- [ ] Preview link works for a logged-out person and carries `noindex`.
- [ ] Covers PRD criterion 8.

### Run 6: preview fidelity and speed

- [ ] Twelve-section page, edit a field, measure time from pause to updated iframe.
- [ ] Compare the preview against the published page side by side. Any visual difference is a bug.
- [ ] Check at all three widths.
- [ ] Covers PRD criteria 2, 5.

### Run 7: SEO

Blocked. JSON-LD and the sitemap are not built. See 11.

- [ ] Meta and JSON-LD in the head, per locale.
- [ ] Rich Results Test passes on a page with an FAQ block.
- [ ] Sitemap covers both locales and contains no draft or preview URL.
- [ ] Covers PRD criterion 9.

### Run 8: animation

Blocked. Feature 08 is not started.

- [ ] Preset applied from the dropdown works on the public page.
- [ ] It survives Livewire SPA navigation between pages, both directions.
- [ ] Reduced-motion setting disables it.
- [ ] Covers PRD criterion 10.

### Run 9: performance

Can run today against the current build, and the numbers are worth having before 09 starts.

- [ ] Lighthouse on a representative page, both locales, numbers recorded in `09-performance.md`.
- [ ] Covers PRD criterion 11.

## Done when

All nine runs pass and every PRD success criterion is accounted for. Anything that failed and was consciously accepted gets written down here with the reason, rather than quietly dropped.
