# Hyperframes composition brief: Filament Atelier

## Objective
Create a short launch-style brag video for Filament Atelier.

## Output
- Composition directory: `brag-output/composition/`
- Rendered video: `brag-output/brag.mp4`
- Format: landscape, 1920x1080
- Duration: 22 seconds

## Source material
- Project root: the repository root (`filament-atelier/`)
- Primary files read: `README.md`, `Docs/features.md`, `art/atelier.png`, `src/Tokens.php`, `resources/views/filament/pages/page-editor.blade.php`, `resources/views/blocks/hero.blade.php`, `example/database/seeders/SparkleCleanSeeder.php`
- Product name: Filament Atelier
- Tagline: "A visual page builder for Laravel and Filament"
- Key UI moment to recreate: the full-screen editor (top bar with Draft badge, width switcher, locale switcher, Publish; Sections list; live preview canvas)
- Copy that must appear verbatim:
  - A visual page builder for Laravel and Filament
  - A clean flat, without the Sunday spent on it
  - composer require safi/filament-atelier

## Creative direction
- Tone preset: polished
- Creative direction: an atelier product film in the colours of the project's banner art
- Interpretation: few scenes, settled holds, soft slides, one bronze accent
- Angle: the preview is the real page. Show a client editing a headline, the page updating beside it, a mobile width check, and a publish.
- Hook: "Clients want to edit the page." / "You want to keep the code."
- Outro: FILAMENT / Atelier wordmark with the composer command
- Avoid: generic SaaS language, abstract filler, restyling the product away from its own art

## Visual identity
- Background: #f1eee6
- Text: #151411
- Accent: #a8742f
- Display font: EB Garamond 700 (local woff2)
- Label font: JetBrains Mono (local woff2)
- Body font: Inter (local woff2)
- References: bronze selection box linked to a section row, spaced mono labels, thin grey rules

## Storyboard
Creative contract: `brag-output/brag-plan.md`.

1. Hook, 3.5s: two serif lines
2. Reveal, 3.0s: wireframe plus wordmark
3. Editor, 8.5s: select hero, retype heading with live preview, mobile width, publish
4. Under it, 3.5s: three facts on every other beat
5. Outro, 3.5s: wordmark and composer command

## Audio
- Role: warm bed, sparse accents
- Music: `assets/music/happy-beats-business-moves-vol-12-by-ende-dot-app.mp3`, about 0.3, 1.5s fade out
- Cue guidance: `assets/music/cues/happy-beats-business-moves-vol-12-by-ende-dot-app.music-cues.json`. Locks at 8.74, 13.11, 18.56. Grid 15.29, 16.38, 17.47.
- Audio-reactive: subtle, RMS on the bronze selection glow and background warmth
- SFX: interface click_003 on clicks, thinned keyboard ticks, bong_001 on publish, impactSoft_medium_001 on wordmark landings, drop_001 on first fact
- SFX analysis: `assets/sfx/sfx-analysis.md` in the brag skill

## Requirements
- Show real UI and copy from the project.
- Keep text readable, 15 to 25 seconds, audio layer included.
- All assets local. Run `npx hyperframes check` before render.
