# 09. Performance

> **Status, 18 Aug 2026. Barely started.** Three of the media items are partly there
> because the blocks were written carefully. Everything else, the per-block assets, the
> page cache and every measurement, is untouched. The conditional asset loading is the
> one this file warned about: it is a non-negotiable in `CLAUDE.md` precisely because
> retrofitting it across the block set is a rewrite of the block set, and there are now
> nine blocks to rewrite instead of zero.

## What it is

The pass that keeps a builder-made page as fast as a hand-coded one: load a block's CSS and JS only when that block is on the page, cache the rendered output, and hit green Core Web Vitals.

## Why we're building it

This is the argument against WordPress and Elementor, stated in the PRD's problem section. Elementor's reputation is bloat, and it earned it by loading everything everywhere. If an Atelier page scores worse than the bespoke site it replaced, the client notices and the pitch dies.

Conditional asset loading in particular is here rather than later because retrofitting it is painful. Once twelve blocks assume a global stylesheet, unpicking that is a rewrite of all twelve.

## How it should feel

The client should never think about it. There is no "optimise" button, no cache-clearing ritual, no performance tab. Publishing regenerates what needs regenerating. A page just loads fast.

For the developer, adding a block that needs its own CSS should mean declaring it on the block, not editing a global bundle.

## In the dashboard

No client-facing surface, deliberately. The only visible behaviour is that publishing takes an extra moment while caches regenerate.

For developers: an artisan command to clear and warm the page cache, for deploys.

## Tasks

### Assets

- [ ] Blocks declare their own CSS and JS, loaded only when the block appears on the page. Build this in from day one. Nothing exists. Every page loads the host app's single stylesheet.
- [x] Most styling comes from Tailwind utilities plus design tokens, so the common case needs no per-block CSS at all. True in practice: no block ships its own CSS. Tokens are still missing (02).
- [!] Per-element custom values compile to a small scoped stylesheet per page, cached where we control it (storage or inline `<style>`), regenerated on publish. Not a user-writable uploads directory, which is Elementor's deploy and load-balancer problem. Not needed yet: there are no per-element custom values, because there are no shared style controls (`supports()`, 03). Revisit when those land.
- [ ] Defer non-critical JS. The public page ships no JS at all today, so this is free until feature 08 adds GSAP.

### Rendering

- [ ] Cache the rendered page output, keyed by page, locale and published version. No cache. Every request re-renders the tree. The 01 spike measured a twelve-section render at 16ms mean, so this is not urgent, but it is also not done.
- [ ] Bust it on publish and unpublish.
- [x] Never cache preview responses. `Cache-Control: no-store, max-age=0` on every preview response.
- [x] Keep block markup lean, so the DOM stays small and INP stays low.

### Media

- [~] Explicit width and height on every image, to kill CLS. Hardcoded per view rather than read from the file, and the hero's image is a CSS background with no dimensions. See 03 and 11.
- [ ] WebP or AVIF output. Uploads are stored and served as-is, so a client uploading a 4MB PNG hero gets a 4MB PNG hero.
- [!] `loading="lazy"` on below-the-fold media, and never on the LCP image. Backwards today: every image is `loading="lazy"`, including the first one on the page, which is usually the LCP element. This actively costs LCP rather than saving it.
- [ ] Preload the LCP image with `fetchpriority="high"`. Worse for the hero, whose image is a CSS background and so cannot be preloaded or prioritised at all without changing the markup.

### Measurement

- [ ] Lighthouse on a representative page, both locales. Never run.
- [ ] Field-realistic INP check, not just lab numbers, since INP is where a JS-heavy editor output usually fails.
- [ ] Record the numbers in this file so a later regression is visible.

## Done when

- A published page hits LCP ≤ 2.5s, INP ≤ 200ms, CLS ≤ 0.1 on a representative page (PRD criterion 11).
- A page using three block types loads only those three blocks' assets, confirmed in the network tab.
- Publishing invalidates the cache and the new content appears immediately.

## Baseline numbers

Fill in after the first Lighthouse run. Empty until then, on purpose.

| Page | Locale | LCP | INP | CLS | Date |
| ---- | ------ | --- | --- | --- | ---- |
|      |        |     |     |     |      |
