# 09. Performance

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

- [ ] Blocks declare their own CSS and JS, loaded only when the block appears on the page. Build this in from day one.
- [ ] Most styling comes from Tailwind utilities plus design tokens, so the common case needs no per-block CSS at all.
- [ ] Per-element custom values compile to a small scoped stylesheet per page, cached where we control it (storage or inline `<style>`), regenerated on publish. Not a user-writable uploads directory, which is Elementor's deploy and load-balancer problem.
- [ ] Defer non-critical JS.

### Rendering

- [ ] Cache the rendered page output, keyed by page, locale and published version.
- [ ] Bust it on publish and unpublish.
- [ ] Never cache preview responses.
- [ ] Keep block markup lean, so the DOM stays small and INP stays low.

### Media

- [ ] Explicit width and height on every image, to kill CLS.
- [ ] WebP or AVIF output.
- [ ] `loading="lazy"` on below-the-fold media, and never on the LCP image.
- [ ] Preload the LCP image with `fetchpriority="high"`.

### Measurement

- [ ] Lighthouse on a representative page, both locales.
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
