# Elementor architecture

> How Elementor is built, written so I can borrow the good ideas for a Laravel/Filament builder. Verified against Elementor's developer docs (developers.elementor.com) plus reputable performance sources, June 2026.

## 1. The element model: the page is a tree

Elementor models a page as a tree of elements. Every node is one of three `elType` values, plus the document root:

- **Document (root).** The page/template. Holds `page_settings` and a `content` array of top-level elements.
- **`container`.** The modern layout element (since Elementor 3.6, early 2022). A CSS Flexbox or Grid box that holds other containers or widgets, nested arbitrarily deep. This replaced the old rigid model.
- **`widget`.** A leaf element with its own `widgetType` and controls (heading, image, button, form). Some newer "nested" widgets (Menu, Accordion, Tabs) can contain children.
- **Legacy `section` / `column`.** The original model: Section to Column to (optional inner Section) to Widget. Still supported for backward compatibility but deprecated in favour of containers. This is the single most important architectural lesson: the old model forced 3 to 4 nested `<div>` wrappers around every widget, the root of Elementor's bloat reputation.

The idea worth borrowing: a page is a recursive tree where each node has a `type`, a unique `id`, a settings bag, and a `children` array. Layout nodes and content nodes share the same envelope, so one renderer walks the whole tree.

## 2. Data structure: how a page is stored

On Save, Elementor serialises the whole element tree to JSON and stores it as WordPress post metadata (`_elementor_data` in `wp_postmeta`). Standardized so it can export/import across sites.

Document envelope (what a downloaded template `.json` looks like; the `content` array is what lives in `_elementor_data`):

```json
{
  "title": "Template Title",
  "type": "page",
  "version": "0.4",
  "page_settings": [],
  "content": []
}
```

`type` = document type (page, post, header, footer, error-404, popup). `version` = data-schema version (latest documented 0.4; they version so they can migrate old data). `content` = array of top-level element nodes.

Every element node shares the same envelope:

| Field | Type | Meaning |
|---|---|---|
| `id` | string | Unique element key (short hex) |
| `elType` | string | `container`, `widget` (legacy `section`/`column`) |
| `widgetType` | string | Present only when `elType` is `widget` |
| `isInner` | boolean | Whether it's a nested element |
| `settings` | array/object | Control values keyed by control id (`[]` if none) |
| `elements` | array | Child nodes, what makes it a tree |

Worked example, a container holding three widgets:

```json
{
  "title": "About Page",
  "type": "page",
  "version": "0.4",
  "content": [
    {
      "id": "6af611eb",
      "elType": "container",
      "settings": [],
      "elements": [
        { "id": "6a637978", "elType": "widget", "widgetType": "heading",
          "settings": { "title": "Add Your Heading Text Here", "align": "center" }, "elements": [] },
        { "id": "687dba89", "elType": "widget", "widgetType": "image",
          "settings": { "_padding": { "unit": "px", "top": "100", "right": "0", "bottom": "100", "left": "0", "isLinked": false } }, "elements": [] },
        { "id": "6f58bb5", "elType": "widget", "widgetType": "button",
          "settings": { "text": "Click Me", "button_text_color": "#000000", "background_color": "#E7DFF5" }, "elements": [] }
      ]
    }
  ]
}
```

Settings detail worth copying: control values stored key to value (the key is the control's id). Unit/dimension values are objects (`{ "unit": "px", "size": 70 }` for sliders; `{ unit, top, right, bottom, left, isLinked }` for box dimensions). Store the unit alongside the number, don't bake it into a string. Properties prefixed `_` (e.g. `_padding`) are common/advanced controls shared by all elements.

## 3. The editor: live visual canvas in an iframe

Two regions:

- **Preview.** A live preview rendered by a JS engine, typically without a server round-trip. Loaded in an iframe (the actual front-end render, so styles match production), while the editor chrome sits in the parent frame. This iframe isolation is deliberate.
- **Panel.** The controls column on the left, swapping between Widgets, Page Settings, Site Settings, History, Menu.

How live preview works: the editor is a JS app holding the element tree in memory. Change a control and the JS re-renders that element in the iframe immediately, using the widget's `content_template()` (a JS template), no PHP round-trip. Drag-and-drop, inline text editing, right-click menu, hover edit buttons all inherit from the canvas. On Save, the in-memory tree serialises to JSON and POSTs back.

Two-template model is the crux: each widget ships two renderers, a PHP `render()` for production and a JS `content_template()` for the live editor. For Laravel, the analog: a Blade/PHP renderer for production and a client-side renderer for the canvas, or server-render the canvas via Livewire and accept a round-trip per edit (simpler, slightly less instant).

## 4. Controls system: how widgets declare fields

Each widget declares its settings ("controls") in `register_controls()`. Controls are wrapped in sections (collapsible groups) under tabs (`TAB_CONTENT`, `TAB_STYLE`, `TAB_ADVANCED`).

Control taxonomy (relevant to which Filament fields you map to): Text, Number, Textarea, WYSIWYG, Code, Switcher, Select, Choose (icon toggle group), Color, Font, Gallery, Repeater, URL, Media, Icons, Slider (number + unit), Dimensions (top/right/bottom/left + link toggle), plus group controls (Typography, Border, Background, Box Shadow) that add a whole cluster at once.

Three control flavours:

1. **Regular.** `add_control()`, one value.
2. **Responsive.** `add_responsive_control()`, one value per breakpoint. Stores separate `default`, `tablet_default`, `mobile_default`; at render maps each breakpoint into media-query CSS. Stored as parallel keys (`space_between`, `space_between_tablet`, `space_between_mobile`).
3. **Group.** `add_group_control()`, a reusable bundle.

Conditional display, dynamic content, and CSS selectors are all declared in the control definition (`condition`, `dynamic`, `selectors`) rather than in render code. The control declaration is the single source of truth for the field, its default, when it shows, and what CSS it produces. Strong pattern to copy.

## 5. Widget registration: the visual-vs-code split

The heart of Elementor's extensibility and the clearest thing to borrow. Editors work on the canvas; developers extend the system with PHP widget classes.

A custom widget extends `\Elementor\Widget_Base`:

```php
class Elementor_Test_Widget extends \Elementor\Widget_Base {
    public function get_name(): string {}        // machine id
    public function get_title(): string {}       // label in panel
    public function get_icon(): string {}        // panel icon
    public function get_categories(): array {}    // which panel group
    protected function register_controls(): void {} // the fields
    protected function render(): void {}            // PHP to front-end HTML
    protected function content_template(): void {}  // JS to editor preview HTML
}
```

Registered via `add_action( 'elementor/widgets/register', ... )`.

The split that matters for Laravel: non-technical users never touch code, they drag registered widgets and fill in controls. Developers add capabilities by writing a class that declares controls and how to render. The platform handles persistence, panel UI, drag-and-drop, and CSS generation from those declarations.

A Filament-flavoured equivalent: a `Widget` abstract class with `schema()` (returns Filament form components = controls), `render()` (Blade), `getName()/getIcon()/getCategory()`, registered into a `WidgetRegistry`. Filament's form field types map almost 1:1 onto Elementor's control taxonomy.

## 6. Rendering: server-side HTML + per-page generated CSS

Front end is server-rendered. On a visit, Elementor walks the saved JSON tree and calls each element's PHP `render()` to emit HTML via `$this->get_settings_for_display()`.

CSS is generated, not hand-written, and cached per page. This is the cleverest part. Controls that affect style don't hardcode CSS, they declare a `selectors` map using tokens:

```php
$this->add_control( 'color', [
    'type'      => \Elementor\Controls_Manager::COLOR,
    'selectors' => [ '{{WRAPPER}} h3' => 'color: {{VALUE}}' ],
] );
```

Tokens: `{{WRAPPER}}` (this element instance's unique selector, e.g. `.elementor-element-6a637978`), `{{VALUE}}`, `{{SIZE}}`, `{{UNIT}}`, `{{URL}}`. At save time, Elementor compiles every element's `selectors` plus stored values into real CSS scoped to that instance's wrapper class. Because `{{WRAPPER}}` is unique per element, styles never leak.

Where the CSS goes: a separate file per post, `wp-content/uploads/elementor/css/post-{id}.css`, plus site-wide `global.css`, enqueued with the page. Regenerated on save; can alternatively be output inline.

For your builder: the selectors-as-data pattern is the big takeaway. Store style intent declaratively (control to selector template to value), then compile to a cached per-page stylesheet (a file in `storage/app/public`, or inline `<style>`). Clean HTML, deterministic cacheable CSS.

## 7. Global settings: a design system that cascades

Elementor has a real, referenced design system, not copy-pasted values. Users define Global Colors (`primary`, `secondary`, `text`, `accent`, plus custom) and Global Fonts/typography in Site Settings, plus Theme Style defaults. These live in a special WordPress post called the Kit, not inside any page.

When an element uses a global, its `settings` stores a reference, not the literal value, under `__globals__`:

```json
{
  "settings": {
    "title": "Add Your Heading Text Here",
    "__globals__": {
      "title_color": "globals/colors?id=primary",
      "typography_typography": "globals/typography?id=primary"
    }
  }
}
```

Reference format `globals/<type>?id=<id>`. At render, Elementor resolves against the active Kit. The cascade: Kit (globals + theme styles) to page settings to element settings to element responsive overrides. Change a global colour once and every element referencing it updates. Store a token reference, resolve at render, so a theme change ripples everywhere instead of requiring a data migration.

## 8. Dynamic content / dynamic tags

Dynamic Tags let a control pull live data instead of a static value (Post Title, Author, Site Logo, a custom field). The framework is in core, the actual tags ship with Elementor Pro. Mechanically, a control marked as supporting dynamic content shows a "dynamic" switch; flipping it swaps the static input for a tag picker. Tags are like functions: they take parameters and output a value. Developers register custom tags (including ones hitting external APIs) via a `Tag` class.

For your builder: model a "dynamic value" as a small object `{ source, params, fallback }` that a control can hold instead of a literal, and a resolver layer that turns it into a value at render. In Laravel, a `DynamicTag` interface with `resolve($context): string` and a registry, where context carries the current model/record. This is what makes templates (one design, many records) possible.

## 9. Templates, blocks, template library, save/reuse

Because the whole page is portable JSON, reuse is essentially "serialize a subtree, store it, paste it back." Elementor ships pre-designed Pages and Blocks (a Block = a section-sized design; a Page = a full layout). Any element/section/page can be saved to the library as a reusable JSON template. Export/import as `.json`; Kit/global data travels along.

The enabling decision: one serialization format for a single widget, a block, or a whole page. Same envelope, different `type`. Worth copying: store reusable blocks as the exact same JSON your pages use, scoped to a subtree.

## 10. SEO & performance reputation: the honest version

Elementor's bloat reputation is real and well-documented, and Elementor has done substantial, measurable work to fix it. Both are true.

The problems: DOM bloat from nested wrappers (the legacy Section to Column to Inner-Section to Widget model wraps every widget in multiple `<div>`s, easily exceeding the ~1,400-node threshold PageSpeed flags); heavy un-targeted CSS/JS (core historically loaded assets for many widgets regardless of use; Pro and add-on packs load whole libraries on every page); render-blocking resources and external font requests hurting LCP. This drags Core Web Vitals and Lighthouse scores, a direct SEO concern since INP joined CWV in March 2024.

What Elementor actually shipped: Flexbox Containers (v3.6, early 2022; HTML output cut up to 39%); Grid Containers (late 2023; up to 85% vs the old structure); Optimized DOM Output (strips legacy wrapper divs); per-page widget CSS/JS loading; Inline Font Icons (SVG instead of whole Font Awesome); lazy image loading; Element Caching (v3.22, June 2024, on by default in 3.32; renders a static widget once, caches the HTML, ~99% server-side render time reduction). Their headline: v3.18 to v3.22 measured ~50% TTFB and ~40% LCP improvement.

Operational gripe: CSS in a user-writable `uploads/` dir complicates deploys and load-balancing. For a Laravel builder, prefer a build step or cache layer you control, not user-writable uploads.

Current consensus (2024 to 2026): on defaults, still heavier than lean/code-first builders, but it can hit top scores when built with Containers + performance features + a cache plugin (WP Rocket tests reached 96 to 98 PageSpeed). Still bloats pages via third-party add-on packs loading globally, Pro's extra assets, un-switched Google Fonts/Font Awesome, and deep legacy layouts.

## 11. Key lessons

Borrow (Elementor does these well):

1. One recursive JSON tree for the whole page, uniform node envelope (`id`, `type`, `widgetType`, `settings`, `children`). Save/reuse/export comes nearly free.
2. Declarative control system. A widget declares its fields, defaults, conditions, and the CSS each field produces, all in one place. Maps cleanly onto Filament form schemas, which is the single biggest win available here.
3. Selectors-as-data + scoped CSS. Store style intent as selector template to value, scoped per element, compiled to a cached per-page stylesheet.
4. Referenced global design system. Store token references not literals, resolved at render against a Kit. Theme changes ripple instantly.
5. Dynamic values as first-class. A control holds `{source, params, fallback}` resolved at render, enabling templates over live data.
6. Developer-extends-with-classes, editor-uses-canvas. Clean separation.

Avoid (Elementor's scars):

1. Don't ship a deep wrapper hierarchy. Start with a single flexible Flexbox/Grid container.
2. Don't load all widget CSS/JS globally. Load per-widget assets only when the widget is on the page, from day one.
3. Don't put generated CSS in a user-writable uploads dir. Use a build/cache layer you control.
4. Cache rendered output early.
5. Keep the DOM lean and markup semantic. Render nothing when a widget has no content.
6. Version your data schema from the start so you can migrate stored JSON.

Net: Elementor's data model, declarative controls, and referenced design system are genuinely excellent and directly portable. Its original layout model was the costly mistake. Start with containers, conditional assets, and output caching, and you skip the decade of cleanup Elementor is still finishing.

## Sources

Official Elementor developer docs:
- https://developers.elementor.com/docs/data-structure/
- https://developers.elementor.com/docs/data-structure/general-structure/
- https://developers.elementor.com/docs/data-structure/container-element/
- https://developers.elementor.com/docs/data-structure/widget-element/
- https://developers.elementor.com/docs/data-structure/global-styles/
- https://developers.elementor.com/docs/editor/elementor-preview/
- https://developers.elementor.com/docs/widgets/widget-structure/
- https://developers.elementor.com/docs/widgets/widget-controls/
- https://developers.elementor.com/docs/widgets/widget-rendering/
- https://developers.elementor.com/docs/widgets/rendering-style/
- https://developers.elementor.com/docs/editor-controls/responsive-control/
- https://developers.elementor.com/docs/managers/registering-widgets/
- https://developers.elementor.com/docs/dynamic-tags/

Performance / SEO:
- https://elementor.com/blog/performance-improvements/
- https://elementor.com/blog/introducing-flexbox-containers/
- https://elementor.com/help/element-caching-help/
- https://nitropack.io/blog/elementor-slow/
- https://shortpixel.com/blog/elementor-performance-problems/
- https://wp-rocket.me/blog/divi-vs-elementor-performance-speed/
- https://github.com/elementor/elementor/issues/6859
