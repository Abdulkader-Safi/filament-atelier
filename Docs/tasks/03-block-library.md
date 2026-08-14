# 03. Block library and rendering

## What it is

The `Block` contract, the renderer that walks a JSON tree and outputs HTML, and the starter set of marketing blocks a client site actually needs.

A block type is one PHP class plus one Blade view. The class declares its registry key, its label, icon and category for the picker, its Filament schema (which becomes the settings pane), and which shared features it supports.

```php
interface Block
{
    public static function type(): string;      // 'hero'
    public static function label(): string;
    public static function icon(): string;
    public static function category(): string;
    public function schema(): array;            // Filament form components
    public static function supports(): array;   // ['background', 'padding', 'animation']
}
```

## Why we're building it

This is the visual-and-code split the whole product rests on. The developer keeps control of markup, styling and quality. The client gets a fixed set of well-built sections they can't break. `supports()` plus design tokens is what stops this becoming Elementor, where every block grows a hundred style fields and every page ends up looking different.

`schema()` returning a plain Filament schema is the biggest saving in the project: the entire control system, validation and field rendering come free.

## How it should feel

For the developer: adding a block should be boring. Write a class, write a Blade view, register it, done. No editor code, no JavaScript, no touching the plugin's internals. If adding a block ever needs a change inside Atelier, the contract is wrong.

For the client: the section picker should read like a menu of things they recognise, grouped sensibly, with icons that mean something. Not a dropdown of 30 technical names.

## In the dashboard

Blocks appear in the section picker when the client clicks "Add section" in the editor. Grouped by `category()`, labelled by `label()`, iconed by `icon()`. Selecting one inserts it and opens its settings on the right, built from `schema()`.

Developers never see a UI for this. Registration is code.

## Tasks

### Contract and renderer

- [ ] `Block` interface as above.
- [ ] Convention for the Blade view path, so `render()` isn't needed on the interface.
- [ ] Renderer that walks the JSON tree and resolves each node through the registry.
- [ ] Recursive rendering for `children`, for blocks that nest.
- [ ] Unknown block type renders nothing on the public site and shows a clear placeholder in the preview, rather than throwing.
- [ ] `supports()` handling: shared background, padding and animation controls injected into the settings pane and applied by the renderer, so each block doesn't reimplement them.

### The marketing set

- [ ] Header
- [ ] Hero
- [ ] Features
- [ ] Logo wall
- [ ] Testimonials
- [ ] CTA
- [ ] FAQ
- [ ] Rich text
- [ ] Image
- [ ] Gallery
- [ ] Contact form (see open question)
- [ ] Footer
- [ ] Raw HTML, the escape hatch for one-off markup

### Quality bar per block

- [ ] Lean markup, no wrapper divs that exist only to hold a class.
- [ ] Tailwind utilities plus design tokens, no per-block bespoke CSS unless there's no alternative.
- [ ] Explicit width and height on every image, to protect CLS.
- [ ] Correct in RTL using logical properties, not `left`/`right`.
- [ ] Every block registered with an icon and a category.

## Done when

- A developer adds a thirteenth block type with one class and one Blade view, it appears in the picker with working controls, and no Atelier file was edited (PRD criterion 4).
- A page built from hero, features, testimonials, CTA, FAQ and contact renders correctly server-side in both locales.

## Open question to settle before building it

The contact form block: does it submit to a real destination (a `submissions` table, an email, a CRM), or is it presentational, pointing at a route the developer wires up per site? The second is far cheaper and is the current recommendation. Decide before writing the block, not after.
