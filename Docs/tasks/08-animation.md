# 08. Animation

## What it is

Scroll and entrance animations picked from a dropdown per section, stored as data on the block:

```json
"animation": { "preset": "fade-up", "duration": 0.8, "delay": 0, "trigger": "onScroll" }
```

One front-end initialiser reads `[data-anim]` attributes and maps each preset name to a GSAP recipe. The client never sees GSAP. A developer adds presets in one map.

## Why we're building it

It's the difference between a page that looks like a CMS built it and a page that looks like dsrpt built it, and it's the single feature clients ask for by name. Doing it as data rather than per-block code means twelve blocks don't each grow their own animation implementation.

## How it should feel

Tasteful by constraint. A short list of presets that all look good, not a timeline editor. The client picks "fade up" and it looks right, because the preset was tuned once by someone with taste rather than assembled from four sliders by someone without.

In the editor it should preview live: pick a preset, see it play in the middle pane, without saving.

## In the dashboard

In the settings pane for any block that declares `animation` in its `supports()`:

- **Animation:** a select. None, fade up, fade in, slide in, scale in, stagger children.
- **Duration:** a slider or number, with a sane default.
- **Delay:** same.
- **Trigger:** on scroll into view, or on page load.
- A **replay** button in the preview, because an animation you have to scroll away and back to see is an animation you can't judge.

## Tasks

### Front end

- [ ] GSAP 3.15 plus ScrollTrigger, bundled with the site's assets.
- [ ] Preset map: name to GSAP recipe, in one file.
- [ ] Initialiser reading `[data-anim]`, `[data-anim-duration]`, `[data-anim-delay]`, `[data-anim-trigger]`.
- [ ] Renderer emits those attributes from the block's `animation` data.
- [ ] Respect `prefers-reduced-motion` and skip animations when set. Not optional.
- [ ] Never animate the LCP element into view, it destroys the LCP measurement.

### The Livewire traps

These are the documented failure modes from `research/preview-drafts-gsap-seo.md`. Each one is a real bug we already know about.

- [ ] Initialise on `livewire:navigated`, not `DOMContentLoaded`. `DOMContentLoaded` only fires on first load and everything breaks on SPA navigation.
- [ ] Tear down on `livewire:navigating`.
- [ ] Scope each component's animations with `gsap.context()` so `.revert()` cleans them up.
- [ ] Wrap GSAP-controlled regions in `wire:ignore` so Livewire's DOM morphing doesn't clobber inline transforms.
- [ ] Call `ScrollTrigger.refresh()` after blocks change the page height, including after every preview refresh.

### Editor

- [ ] Animation controls injected for blocks that support it.
- [ ] Preview plays the animation on selection change.
- [ ] Replay button.

## Done when

- An editor applies a scroll animation from a dropdown, it works live in the preview, and it survives Livewire SPA navigation on the public site (PRD criterion 10).
- Reduced-motion users get a static page.
- Adding a new preset is one entry in one map.

## Licensing, and be accurate about it

GSAP is free for commercial use since April 2025, including the former Club plugins. It is **not** MIT and **not** open source. It ships fine in client sites. Don't write "MIT" in a README or a client proposal.
