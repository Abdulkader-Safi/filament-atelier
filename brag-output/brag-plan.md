# Brag plan: Filament Atelier

## What is this app?
A Filament plugin where a developer defines page sections in code and a client builds pages from them in a full-screen editor, watching the real server-rendered page update as they type.

## The angle
The preview is not a mock of the site. It is the site: same Blade, same layout, same stylesheet. The video proves it by showing a client edit a headline, watching the page change beside it, squeezing it to mobile width, and publishing. The look borrows the project's own art: cream paper, ink serif, bronze selection box, spaced monospace labels. It should read like a well-made tool from a workshop, not a SaaS ad.

## Hook (first 2-3 seconds)
Cream canvas, bronze hairline frame. Two serif lines land one after the other:
"Clients want to edit the page." then "You want to keep the code."
The tension every Laravel agency knows, stated plainly.

## Key moments (the middle)
- The editor recreated from `page-editor.blade.php`: section list on the left (Hero, Features, Logo wall, Testimonials, Call to action, FAQ), live canvas on the right showing the Sparkle Clean demo page.
- Cursor clicks the Hero row, the bronze selection box snaps onto the hero in the preview, and the sidebar swaps to the Heading field.
- The heading is retyped, and the preview headline changes letter by letter with it.
- The width switcher goes to mobile: the canvas narrows to 390px and the headline rewraps. Caption: "Catch the headline that wraps onto three lines."
- Publish clicked: the amber "Draft" badge flips to green "Published".
- Three quick facts, each held long enough to read: one PHP class plus one Blade view per block; any language, RTL included, from one tree; server-rendered Blade with sitemap, hreflang and JSON-LD.

## Outro / punchline
The wordmark from `art/atelier.png`: spaced "FILAMENT" above a large serif "Atelier", rule, then `composer require safi/filament-atelier`.

## User flow worth showing
Open the page in the editor, select the hero section, retype the heading while the preview updates, switch to mobile width, publish.

## Tone
- Preset: polished
- Creative direction: an atelier product film, quiet and exact, in the colours of the project's own banner art
- Interpretation: few scenes, long settled holds, soft slides and crossfades, one bronze accent colour, no jokes. The confidence comes from showing the real flow working.

## Format: landscape, 1920x1080
## Duration: 22s

## Visual identity (from the project)
- Background: #f1eee6 (cream paper from `art/atelier.png`)
- Accent: #a8742f (bronze selection outline in the art)
- Text: #151411 (ink)
- Muted: #6f6a60 (label grey, darkened for contrast)
- Editor chrome: white panels, #e5e5e5 borders, amber draft badge, green published badge (from the Tailwind classes in `page-editor.blade.php`); public page primary #171717 from `Tokens::defaults()`
- Display font: EB Garamond 700 (closest bundled match to the art's tall serif)
- Label font: JetBrains Mono, wide letter-spacing, uppercase (matches "SECTIONS", "HERO" in the art)
- Body font: Inter
- Strongest visual element: the bronze selection box around a section, linked by a line to its row in the section list

## Share copy (draft)
Filament Atelier: clients build pages from sections you define in code, and the preview is the real Blade page, not an imitation of it.

## Audio direction
- Role: warm bed with sparse professional accents
- Music: happy-beats-business-moves-vol-12 (steady, clean; bundled for polished)
- Music treatment: start at 0, volume about 0.3, fade out over the last 1.5s
- Music cue guidance: preset read (109.96 BPM). Strong cues: 8.74s (selection lands in the editor), 13.11s (Publish), 18.56s (wordmark lands). Beat grid for the three facts: 15.29, 16.38, 17.47 (every other beat, so each line holds about a second before the next).
- Audio-reactive treatment: subtle; music RMS makes the bronze selection glow and the background radial warmth breathe. No waveform visuals.
- SFX posture: sparse, low high-frequency risk
- Audio-coupled moments: cursor clicks, thinned key ticks during the heading retype, a soft bong on publish, a soft impact on the wordmark
- Restraint rule: nothing louder than the bed on repeated events; no SFX on the three facts beyond the first

## Storyboard

### Scene 1: Hook, 3.5s (0 to 3.5)
Cream canvas, faint grid, bronze hairline frame inset like the art. Mono label "SECTIONS" top left. Serif line 1 "Clients want to edit the page." at 0.3s, line 2 "You want to keep the code." at 1.5s in bronze-inked italic weight. Both hold to 3.3s.
Sequential/interaction: two lines, one after the other, each held over a second.
Audio intent: bed starts clean.
Audio-coupled idea: none.
Music: steady bed.
Transition mood: soft slide up to Scene 2.

### Scene 2: Reveal, 3.0s (3.5 to 6.5)
The wordmark lockup: "FILAMENT" spaced mono, big serif "Atelier", rule, "A VISUAL PAGE BUILDER FOR LARAVEL AND FILAMENT". To the left, the art's wireframe: section rows and a page outline with the bronze box around the hero.
Sequential/interaction: section rows draw in one by one quickly, bronze box snaps on last.
Audio intent: soft impact as "Atelier" lands.
Audio-coupled idea: bronze box snap.
Transition mood: soft crossfade into the editor.

### Scene 3: The editor, 8.5s (6.5 to 15)
Recreated editor. Top bar: back arrow, "Home", amber "Draft" badge, desktop/tablet/mobile switcher, EN / AR, Publish button. Left rail, Sections list, preview canvas showing the Sparkle Clean hero ("A clean flat, without the Sunday spent on it"), features row below.
- 7.0s cursor moves to "Hero" row; 8.0s click; 8.74s bronze selection box lands on the preview hero (beat-locked) and the sidebar swaps to "Hero" settings with a Heading field.
- 9.2s to 11.0s heading retyped to "A clean flat, and your Sunday back". Preview headline updates in step.
- Caption bottom-left from 9.4s: "The preview is the real page."
- 11.4s click mobile; canvas narrows to phone width, headline rewraps. Caption swaps to "Same Blade. Same stylesheet. Any width."
- 13.11s click Publish (beat-locked); badge flips to "Published" in green.
Sequential/interaction: cursor clicks, typing, width switch, publish.
Audio intent: tactile, precise.
Audio-coupled idea: click on each press, thinned key ticks, bong on publish.
Transition mood: soft slide to Scene 4.

### Scene 4: What's under it, 3.5s (15 to 18.5)
Three rows on cream, mono index numbers, serif statements:
1. "One PHP class and one Blade view per block."
2. "Any language, RTL included, from one tree."
3. "Server-rendered. Sitemap, hreflang, JSON-LD."
Rows at 15.29, 16.38, 17.47 (every other beat). All three held together until 18.4.
Sequential/interaction: yes, three rows, each held about 1s alone before the next, then all held.
Audio intent: light, no clutter.
Audio-coupled idea: one soft drop on the first row only.
Transition mood: crossfade to outro.

### Scene 5: Outro, 3.5s (18.5 to 22)
Wordmark lockup lands at 18.56 (beat-locked). Under the rule: `composer require safi/filament-atelier` in mono inside a thin bordered chip. Hold, music fades.
Sequential/interaction: none.
Audio intent: settle.
Audio-coupled idea: soft impact on landing.
Transition mood: end on hold.

**Music mood for this video:** steady, clean, quietly upbeat
**Audio summary:** a steady bed under precise UI clicks and typing, one bong on publish, a soft landing on the wordmark, then a fade.
