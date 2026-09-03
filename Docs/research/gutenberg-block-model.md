# Gutenberg block editing, an architecture brief

A source-cited technical brief on how WordPress's Gutenberg block editor works under the hood, written so you can decide which parts to replicate in a server-rendered Laravel page builder. Verified against the official Block Editor Handbook (developer.wordpress.org/block-editor), June 2026.

Every quoted line below is from the handbook. Full URL list at the bottom.

---

## 1. The block concept and the block registry

A **block** is the single unit of content and layout in Gutenberg. A post or page is just an ordered list of blocks. Each block has a **type** (paragraph, image, columns, your custom thing) and that type is described by metadata, not just code.

A block type is described primarily by a **`block.json`** file. That file is the single source of truth for the block's identity, its data shape, and the features it opts into.

**The registry.** There is one global server-side registry, `WP_Block_Type_Registry`. The handbook flow:

> "If a string path to a file is provided to `register_block_type()`, it returns `register_block_type_from_metadata()`, otherwise it calls `WP_Block_Type_Registry::get_instance()->register()`."
>
> "The `register_block_type()` function utilises the name and arguments provided in the function call to create a new instance of `WP_Block_Type` class and the instance thus created is registered with the global `WP_Block_Type_Registry` instance."

Two-sided registration, the part most relevant to you:

- **Server (PHP)** registers the block type so the front end and REST API know it exists. "Block registration on the server takes place in the main plugin PHP file, hooked on `init`." You point `register_block_type()` at the folder containing `block.json`.
- **Client (JavaScript)** registers the same block type in the editor with `registerBlockType()`, supplying the interactive `edit` component and the `save` function. The two registrations share the same `name` and the same `block.json` metadata.

Since WordPress 6.8 there's a bulk path, `wp_register_block_types_from_metadata_collection()`, which registers every block in a `blocks-manifest.php` file in one call instead of many individual `register_block_type()` calls.

**The `name`** is the unique key in the registry: "A unique string that identifies a block. Names have to be structured as `namespace/block-name`." Core blocks omit the namespace in markup (`wp:image` means `core/image`); custom blocks keep it (`wp:my-plugin/notice`).

**Laravel takeaway:** you want one registry too: a service/container binding mapping a block type name (`hero`, `pricing-table`) to its definition (schema + a Blade view + an optional server resolver). Register them once at boot. Keep the type name namespaced so third parties can add blocks without collisions.

---

## 2. `block.json` schema in detail

`block.json` is the metadata file. The current Block API version is **`apiVersion: 3`** ("The most recent version is `3` and it was introduced in WordPress 6.3." Default if omitted: `1`).

Here is the canonical example from the handbook (the "notice" block), verbatim:

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "my-plugin/notice",
    "title": "Notice",
    "category": "text",
    "parent": [ "core/group" ],
    "icon": "star",
    "description": "Shows warning, error or success notices...",
    "keywords": [ "alert", "message" ],
    "version": "1.0.3",
    "textdomain": "my-plugin",
    "attributes": {
        "message": {
            "type": "string",
            "source": "html",
            "selector": ".message"
        }
    },
    "providesContext": {
        "my-plugin/message": "message"
    },
    "usesContext": [ "groupId" ],
    "selectors": {
        "root": ".wp-block-my-plugin-notice"
    },
    "supports": {
        "align": true
    },
    "styles": [
        { "name": "default", "label": "Default", "isDefault": true },
        { "name": "other", "label": "Other" }
    ],
    "example": {
        "attributes": {
            "message": "This is a notice!"
        }
    },
    "variations": [
        {
            "name": "example",
            "title": "Example",
            "attributes": {
                "message": "This is an example!"
            }
        }
    ],
    "editorScript": "file:./index.js",
    "script": "file:./script.js",
    "viewScript": [ "file:./view.js", "example-shared-view-script" ],
    "editorStyle": "file:./index.css",
    "style": [ "file:./style.css", "example-shared-style" ],
    "viewStyle": [ "file:./view.css", "example-view-style" ],
    "render": "file:./render.php"
}
```

### Top-level properties (each as defined by the handbook)

| Property | Type | Notes |
|---|---|---|
| `$schema` | string | JSON-schema URL for editor validation/autocomplete. |
| `apiVersion` | number | Block API version. Latest is `3` (WP 6.3). Default `1`. |
| `name` | string | **Required.** Unique `namespace/block-name`. Lowercase, dashes, one slash, must start with a letter. |
| `title` | string | **Required.** Display name in the inserter. |
| `category` | string | Grouping in the inserter. Core: text, media, design, widgets, theme, embed. |
| `parent` | string[] | Block is only insertable as a **direct child** of the listed blocks. |
| `ancestor` | string[] | Block is only insertable **anywhere inside** the listed blocks' subtree (looser than `parent`). WP 6.0. |
| `allowedBlocks` | string[] | Which block types may be **direct children** of this block. WP 6.5. |
| `icon` | string | Dashicon slug; can be overridden client-side with SVG. |
| `description` | string | Shown in the block inspector. |
| `keywords` | string[] | Search aliases. |
| `version` | string | Used for asset cache-busting. WP 5.8. |
| `textdomain` | string | i18n text domain. WP 5.7. |
| `attributes` | object | The block's structured data shape (see section 5). |
| `providesContext` | object | Maps a context name to one of this block's attributes, exposed to descendants. |
| `usesContext` | string[] | Context values this block inherits from an ancestor provider. |
| `selectors` | object | Custom CSS selectors used by theme.json/global-styles generation. WP 6.3. |
| `supports` | object | Opt-in editor features (see section 8). |
| `styles` | array | Named style variations (adds a class to the wrapper). |
| `example` | object | Sample attributes used to build the inserter preview. |
| `variations` | object[] / path | Pre-configured versions of the block. WP 5.9; can point to a PHP file since 6.7. |
| `blockHooks` | object | Auto-insert this block next to all instances of another block type. WP 6.4. |
| `editorScript` / `script` / `viewScript` / `viewScriptModule` | asset | JS for editor-only / both / front-end-only / front-end module. |
| `editorStyle` / `style` / `viewStyle` | asset | CSS, same split as scripts. |
| `render` | path | PHP file used to render the block on the server for the front end (dynamic blocks). WP 6.1. |

`attributes` and `supports` both default to `{}`. The two are linked: enabling a `support` auto-registers extra attributes for you (section 8).

The **`render`** field is the dynamic-rendering hook. "PHP file to use when rendering the block type on the server to show on the front end." The file gets `$attributes` (array), `$content` (string), and `$block` (`WP_Block` instance). Minimal example:

```php
<div <?php echo get_block_wrapper_attributes(); ?>>
    <?php echo esc_html( $attributes['label'] ); ?>
</div>
```

**Laravel takeaway:** a JSON (or PHP array) manifest per block is a good pattern: declarative, lintable, separable from rendering code. Mirror the split between "attributes" (the data shape), "supports" (toggle-able styling features), and "render" (the server template). For Laravel, `render` is just "the Blade view that renders this block," and since Laravel renders server-side, your equivalent of `render.php` is the default, not the exception (more in section 4).

---

## 3. The edit vs save model, and serialization

This is the conceptual heart of Gutenberg and the part worth understanding deeply before you copy or reject it.

### Two functions, two jobs

- **`edit`.** A React component. "The `edit` function describes the structure of your block in the context of the editor. This represents what the editor will render when the block is used." It is interactive and stateful: it can use hooks, read data stores, and call `setAttributes` to change the block's data.
- **`save`.** A pure function. "The `save` function defines the way in which the different attributes should be combined into the final markup, which is then serialized into `post_content`."

Why two? Because the editing experience (drag handles, toolbars, placeholders, live controls) is nothing like the final published HTML. Gutenberg deliberately separates "how you manipulate this block" from "what gets stored and shown."

The hard constraint on `save`:

> "The save function should be a pure and stateless function that depends only on the attributes used to invoke it. It shouldn't use any APIs such as `useState` or `useEffect`, nor retrieve information from another source... This is because if the external information changes, the block may be flagged as invalid when the post is later edited."

So `save` must be deterministic: same attributes in, exactly the same HTML out, every time. That determinism is what makes validation (section 9) possible.

### What gets stored in the database

Block content lives in the post's `post_content` column as **HTML annotated with HTML-comment delimiters**. From the handbook:

> "Blocks are stored in the database or within HTML templates using a unique HTML-based syntax, distinguished by HTML comments that serve as clear block delimiters. This ensures that block markup is technically valid HTML."

Rules for the delimiters:

> - "Core blocks begin with the `wp:` prefix, followed by the block name (e.g., `wp:image`). Notably, the `core` namespace is omitted."
> - "Custom blocks begin with the `wp:` prefix, followed by the block namespace and name (e.g., `wp:namespace/name`)."
> - "The comment can be a single line, self-closing, or wrapper for HTML content."
> - "Block settings and attributes are stored as a JSON object inside the block comment."

A static block (image), serialized:

```html
<!-- wp:image {"sizeSlug":"large"} -->
<figure class="wp-block-image size-large">
    <img src="source.jpg" alt="" />
</figure>
<!-- /wp:image -->
```

A purely dynamic block stores only a self-closing delimiter with its attributes, no inner HTML:

```html
<!-- wp:latest-posts {"postsToShow":4,"displayPostDate":true} /-->
```

### Serialization and parsing (the round trip)

The in-memory model the editor works on is **a tree of block objects**, not the HTML string. Each block object carries `clientId`, `type`, `attributes`, and `innerBlocks`.

- **Serialization** (tree → stored HTML): "the serialization process converts the block tree into HTML using HTML comments as explicit block delimiters—which can contain the attributes in non-HTML form." Attributes go in as "JSON literals inside the comment."
- **Parsing** (stored HTML → tree): a formal grammar reads `post_content` back into the block tree. The parser keys off the comment delimiters, which is why it tolerates broken markup: "Because the comments are so different from other HTML tags and because we can perform a first-pass to extract the top-level blocks, we don't actually depend on having fully valid HTML."

Why HTML comments specifically? The handbook is explicit about the design intent:

> "...by storing data in HTML comments, we would know that we wouldn't break the rest of the HTML in the document, that browsers should ignore it, and that we could simplify our approach to parsing the document."
>
> "These explicit boundaries also protect damage in a single block from bleeding into other blocks or tarnishing the entire document. It also allows the system to identify unrecognized blocks before rendering them."

And the single-source-of-truth motive for storing everything back into `post_content` rather than a separate tree:

> "Were we to store the object tree separately, we would face the risk of `post_content` and the tree getting out of sync and the problem of data duplication in both places."

A nice safety property falls out of this: if you render the stored HTML on a system that knows nothing about blocks, the post is "mostly intact". Static content displays, only dynamic/interactive parts degrade.

**Laravel takeaway:** you have a clean choice here that Gutenberg did not. Gutenberg stores rendered HTML + JSON-in-comments in one text column because WordPress's whole ecosystem expects `post_content` to be HTML. You are not bound by that. You can store the **block tree as structured JSON** in its own column/table and render through Blade at request time. That sidesteps the entire validation/deprecation headache (section 9) because you never re-derive a tree from HTML. The tree *is* the source of truth. Keep Gutenberg's good idea (a serializable tree of typed blocks with attributes and nested children); drop the "serialize to annotated HTML and re-parse it" mechanism unless you specifically need portable, self-describing HTML output.

---

## 4. Static vs dynamic blocks (the key insight for Laravel)

The handbook's framing:

> "A block's front-end markup can either be dynamically generated server-side upon request (dynamic blocks) or statically generated during the save process in the Block Editor (static blocks)."

**Static block:** "produce front-end output that is fixed and stored in the database upon saving. These blocks rely solely on their `save` function to define their HTML markup, which remains unchanged unless manually edited." The full HTML sits in `post_content`; on the front end WordPress just strips the delimiters and prints the inner HTML.

**Dynamic block:** "designed to generate their content and structure in real-time when requested on the front end... rely on server-side processing to construct their output dynamically, making them highly versatile and suitable for content that needs to be updated frequently or is dependent on external data." For these, `save` typically returns `null`, so only the delimiter + attributes are stored, and a PHP callback renders the real HTML on every request.

Two ways to wire up dynamic rendering:

> "1. Using the `render_callback` argument that can be passed to the `register_block_type()` function...
> 2. Using a separate PHP file usually named `render.php`. This file's path should be defined using the `render` property in the `block.json` file."

Both receive `$attributes`, `$content`, `$block`. A verbatim callback example:

```php
function gutenberg_examples_dynamic_render_callback( $block_attributes, $content ) {
    $recent_posts = wp_get_recent_posts( array(
        'numberposts' => 1,
        'post_status' => 'publish',
    ) );
    if ( count( $recent_posts ) === 0 ) {
        return 'No posts';
    }
    $post = $recent_posts[ 0 ];
    $post_id = $post['ID'];
    return sprintf(
        '<a class="wp-block-my-plugin-latest-post" href="%1$s">%2$s</a>',
        esc_url( get_permalink( $post_id ) ),
        esc_html( get_the_title( $post_id ) )
    );
}
```

> "The server-side rendering is a function taking the block and the block inner content as arguments, and returning the markup (quite similar to shortcodes)."

**Why dynamic blocks matter.** The handbook gives two concrete reasons (this is the SEO/live-data argument):

> "1. Blocks where content should change even if a post has not been updated: An example is the Latest Posts block, which will automatically update whenever a new post is published.
> 2. Blocks where updates to the markup should be immediately shown on the front end: If you update the structure of a block by adding a new class, adding an HTML element, or changing the layout in any other way, using a dynamic block ensures those changes are applied immediately on all occurrences of that block across the site."

Because dynamic blocks render real HTML server-side at request time, the markup is fully present in the initial page response, which is good for SEO and for any content that must reflect live data (latest posts, prices, stock, personalised content). And because the markup is generated, not stored, changing the template updates every instance at once with no re-saving and no validation errors.

**Fallback behaviour.** Dynamic blocks can also keep a saved HTML copy as a backup:

> "If you provide a server-side rendering callback, the HTML representing the block in the database will be replaced with the output of your callback but will be rendered if your block is deactivated... or your render callback is removed."

> "Server-side render is meant as a fallback; client-side rendering in JavaScript is always preferred (client rendering is faster and allows better editor manipulation)."

**Laravel takeaway, and this is your model.** In Gutenberg, dynamic/server-rendered is the special case bolted onto a client-render-first system. In Laravel, **server rendering is the native and only thing you need**. Every block is effectively a "dynamic block" rendered by a Blade view at request time. That gives you, for free, what WordPress had to engineer around:

- Full SEO-ready HTML in the first response.
- Live data in any block without re-saving content.
- Template changes apply everywhere instantly, with no validation/deprecation machinery (section 9).

The architecture you want: store each block as `{ type, attributes, children }`; at request time, look the type up in your registry, pass the attributes to its Blade view, render, recurse into children. That is Gutenberg's `render_callback` model promoted to be the whole system. The piece you still owe is a good editor UI (Gutenberg's `edit` half), which is the genuinely hard part.

---

## 5. Where attributes come from (sourcing)

Attributes are the block's structured data. Two things are declared per attribute: **`type`** (what the data is) and **`source`** (where it is stored / how it is read back out).

> "The `source` determines where data is stored in your content, and the `type` determines what that data is."

**Allowed `type` values:** `null`, `boolean`, `object`, `array`, `string`, `integer`, `number` (number is "same as `integer`").

**`source` values, verbatim:**

- "`(no value)` – when no `source` is specified then data is stored in the block's comment delimiter."
- "`attribute` – data is stored in an HTML element attribute."
- "`text` – data is stored in HTML text."
- "`html` – data is stored in HTML. This is typically used by `RichText`."
- "`query` – data is stored as an array of objects."
- "`meta` – data is stored in post meta (deprecated)."

So attributes are stored in one of two fundamentally different places:

**(a) In the comment delimiter, as JSON.** "Attributes without a `source` will be automatically saved in the block comment delimiter." Example: with `title` and `size` having no source but `url` sourced from the image, you get:

```html
<!-- block:your-block {"title":"hello world","size":"large"} -->
<img src="/image.jpg" />
<!-- /block:your-block -->
```

`title` and `size` live in the JSON; `url` is read from the `<img>`.

**(b) Scraped back out of the saved HTML** via a source + selector. Examples:

`attribute`, pull `src` off an `<img>`:

```js
url: {
    type: 'string',
    source: 'attribute',
    selector: 'img',
    attribute: 'src',
}
```

`html`, pull inner HTML of a `<figcaption>` (this is how rich text is stored):

```js
content: {
    type: 'string',
    source: 'html',
    selector: 'figcaption',
}
```

`query`, pull an array of objects, one per matched element ("effectively a nested block attributes definition"):

```js
images: {
    type: 'array',
    source: 'query',
    selector: 'img',
    query: {
        url:  { type: 'string', source: 'attribute', attribute: 'src' },
        alt:  { type: 'string', source: 'attribute', attribute: 'alt' },
    }
}
```

**`selector`:** "If no selector argument is specified, the source definition runs against the block's root node. If a selector argument is specified, it will run against the matching element(s) within the block. The `selector` can be an HTML tag, or anything queryable with querySelector."

The handbook's own guidance on which to use:

> "To reduce the amount of data stored it is usually better to store as much data as possible within HTML rather than as attributes within the comment delimiter."

The reason this dual system exists: Gutenberg stores everything in one HTML blob, so visible content (a heading's text, an image's src) is best kept *in the visible HTML* (no duplication, editable even by hand), while invisible settings (a layout choice, a boolean toggle) have nowhere natural to live in the HTML and so go into the comment JSON.

**Laravel takeaway:** if you store the block tree as structured JSON (recommended, section 3), **this entire dual-sourcing system disappears.** Every attribute is just a JSON field. You never scrape values back out of rendered HTML, because the rendered HTML is throwaway output, not storage. The whole `source`/`selector`/`html`/`query` apparatus is complexity Gutenberg took on purely because its storage format is HTML. Don't replicate it. Keep one place for data: the JSON attributes.

---

## 6. InnerBlocks / nesting

A block can contain other blocks via the `InnerBlocks` component.

> "You can create a single block that nests other blocks using the **InnerBlocks** component. This is used in the Columns block, Social Links block, or any block you want to contain other blocks." (One `InnerBlocks` per block.)

Declared in `edit` (`<InnerBlocks />`) and `save` (`<InnerBlocks.Content />`):

```js
edit: () => {
    const blockProps = useBlockProps();
    return ( <div { ...blockProps }><InnerBlocks /></div> );
},
save: () => {
    const blockProps = useBlockProps.save();
    return ( <div { ...blockProps }><InnerBlocks.Content /></div> );
},
```

`<InnerBlocks />` renders the editable nested region in the editor; `<InnerBlocks.Content />` is what serializes the children into the saved markup.

Controls on nesting:

- **`allowedBlocks`.** Restrict which block types can be inserted as direct children.
- **`template`.** "define a set of blocks that prefill the InnerBlocks component when it has no existing content," e.g. `[[ 'core/image', {} ], [ 'core/heading', { placeholder: 'Book Title' } ]]`.
- **`templateLock`.** `all` locks the template completely; `insert` "prevents additional blocks from being inserted, but existing blocks can be reordered."
- **`parent` / `ancestor`** (in `block.json`): the inverse constraint, declared by the child: `parent` means "only as a direct child of X"; `ancestor` means "anywhere inside X's subtree."

Nesting serializes as **nested delimiter comments**, each child sitting inside its parent's open/close pair, e.g. `<!-- wp:columns --> ... <!-- wp:column --> ... <!-- /wp:column --> ... <!-- /wp:columns -->`. In the block tree this is simply each block's `innerBlocks` array.

**Laravel takeaway:** model this as recursion. A block is `{ type, attributes, children: Block[] }`; rendering recurses into `children`. Carry over the useful guardrails: `allowedBlocks` (validation on insert), `template` (starter children), `templateLock` (editor lock). The `parent`/`ancestor` constraints are worth keeping for blocks that only make sense in a context (a "column" only inside "columns").

---

## 7. Patterns, templates, reusable / synced blocks

Three distinct things, often confused:

**Block patterns.** "Predefined block layouts available from the patterns tab of the block inserter. Once inserted into content, the blocks are ready for additional or modified content and configuration." A pattern is a **starting point**: you insert it, and from then on the copy is independent. Editing it changes only that one instance. Registered with `register_block_pattern( 'namespace/title', [...] )` on the `init` hook, where `content` is literal block markup:

```php
register_block_pattern(
    'my-plugin/my-awesome-pattern',
    array(
        'title'       => __( 'Two buttons', 'my-plugin' ),
        'description' => _x( 'Two horizontal buttons...', 'Block pattern description', 'my-plugin' ),
        'content'     => "<!-- wp:buttons -->...<!-- /wp:buttons -->",
    )
);
```

Patterns can be attached to block types (becoming transform suggestions) and can be marked as header/footer ("semantic block patterns") in block themes.

**Block templates.** A predefined list of blocks that prefill a *new* post of a given type, or the inner content of a block (the `template` prop in section 6). Same idea as patterns but applied as the initial scaffold rather than inserted on demand.

**Reusable blocks / synced patterns.** The synced counterpart. A synced pattern (formerly "reusable block," stored as the `wp_block` post type) is a **single shared source**: editing it updates **every** place it is used across the site. This is the opposite of a normal pattern, where each insertion is independent.

So the axis is: **pattern = copy once, then independent; synced pattern = one source, edits propagate everywhere.**

**Laravel takeaway:** all three are cheap and high-value to copy.
- Patterns = seedable "starter layouts" the user drops in and edits.
- Templates = the default block list a new page starts with.
- Synced patterns = a shared block record referenced by ID; render it by reference so one edit updates every page. Implement with a `synced_blocks` table and a reference block type that just holds an ID and renders the target.

---

## 8. Block supports and theme.json (centralised styling)

**Block supports** is the opt-in feature system in `block.json`:

> "Block Supports is the API that allows a block to declare support for certain features. Opting into any of these features will register additional attributes on the block and provide the UI to manipulate that attribute."

That is the key behaviour: flip a support on, and Gutenberg **auto-adds both the attribute and the editor UI control** for it, then applies the result to the wrapper element (via `useBlockProps()` in `edit`, `useBlockProps.save()` in `save`, or `get_block_wrapper_attributes()` in PHP). Example:

```js
supports: {
    color: {              // enables text + background color UI
        background: false, // ...but turn background off
        gradients: true    // ...and enable gradients
    }
}
```

Supports cover `align`, `anchor`, `className`, `color` (text/background/link/gradients/duotone), `spacing` (margin/padding/blockGap), `typography`, `border`, `dimensions`, `shadow`, `layout`, `position`, `html`, `multiple`, `reusable`, and more.

**theme.json** is the central configuration file that controls global styles and what each support exposes:

> "This describes the current efforts to consolidate the various APIs related to styles into a single point – a `theme.json` file."

It has two halves:

- **`settings`.** *What is available*: the color palette, font sizes, gradients, spacing units, layout widths (`contentSize`, `wideSize`), and which controls are shown or hidden. Works globally and **per block** (`settings.blocks`). It is opt-in/opt-out: "it's the block's responsibility to add support for the features that are relevant to it". theme.json can only expose what the block's `supports` allows. (`appearanceTools: true` is a shortcut that switches on a batch of common features at once.)
- **`styles`.** *The actual values applied*: top-level styles map to the `body` selector; per-block styles map to `.wp-block-<name>`; there's also an `elements` layer (links, headings, buttons, captions).

The linking sentence that ties supports and theme.json together:

> "Each block declares which style properties it exposes via the block supports mechanism. The support declarations are used to automatically generate the UI controls for the block in the editor. Themes can use any style property via the `theme.json` for any block."

The payoff the handbook claims: managing CSS centrally lets WordPress "reduce the amount of CSS enqueued" and "prevent specificity wars," and presets become CSS custom properties shared by editor and front end, so the editor preview matches the published page.

**Laravel takeaway:** this is the single most worth-copying idea after the block tree. Have a central design-tokens config (your `theme.json` equivalent): one palette, one type scale, one spacing scale, layout widths, defined once, used by every block, emitted as CSS custom properties so the editor and the live page share exactly the same styling. Pair it with a per-block "supports" declaration so a block opts into, say, "background colour + padding" and your builder automatically renders the right controls and applies the right classes/inline styles. This is what stops a page builder from devolving into a thousand bespoke style fields per block.

---

## 9. Block validation, invalidation, and deprecation

This whole subsystem exists **because Gutenberg stores rendered HTML and re-parses it.** Understanding it is mostly useful so you can decide to *avoid* it.

**How validation works.** On load, every block is re-checked:

> "During editor initialization, the saved markup for each block is regenerated using the attributes that were parsed from the post's content. If the newly-generated markup does not match what was already stored in post content, the block is marked as invalid. This is because we assume that unless the user makes edits, the markup should remain identical to the saved content."

In plain terms: the editor re-runs `save()` with the parsed attributes and **string-compares** the result to the stored HTML. Any difference = invalid block. The two common causes:

> "1. A flaw in a block's code would result in unintended content modifications...
> 2. You or an external editor changed the HTML markup of the block in such a way that it is no longer considered correct."

This is also why `save` must be pure (section 3): if `save` depended on anything external, the regenerated markup could differ from the stored markup and the block would falsely flag as invalid.

**What the user sees.** An invalid block prompts for recovery: an "Attempt Block Recovery" button, plus options to **Resolve**, **Convert to HTML** (edit the raw markup), or **Convert to Classic Block**. Disruptive, and a frequent source of the dreaded "This block appears to have been modified externally" message.

**Deprecation** is the escape hatch for when you *intentionally* change a block's `save` output (e.g. you change a `<p>` to a `<div>`). You supply a `deprecated` array: older versions of the block's `attributes` + `supports` + `save`, plus an optional `migrate`:

```js
registerBlockType( 'gutenberg/block-with-deprecated-version', {
    attributes, supports,
    save( props ) { return <div>{ props.attributes.text }</div>; }, // new
    deprecated: [
        {
            attributes, supports,
            save( props ) { return <p>{ props.attributes.text }</p>; }, // old
        },
    ],
} );
```

The mechanism, verbatim:

> "A deprecation will be tried if the current state of a parsed block is invalid... If the current `save` method does not produce a valid block the first deprecation in the deprecations array is passed the original saved content. If its `save` method produces valid content this deprecation is used to parse the block attributes. If it has a `migrate` method it will also be run... The attributes, and any innerBlocks, from the first deprecation to generate a valid block are then passed back to the current `save` method to generate new valid content."

`migrate` can also rename attributes or move data into inner blocks (returning `[attributes, innerBlocks]`). `isEligible` lets you force migration even for technically-valid blocks. Deprecations are listed reverse-chronologically and "are not automatically inherited from the current version."

**Laravel takeaway, and this is the cautionary tale.** Every bit of this complexity (validation diffs, "modified externally" errors, the entire `deprecated`/`migrate`/`isEligible` machinery) is a **direct tax on storing rendered HTML and re-deriving the tree from it.** If you store the block tree as structured JSON and render via Blade at request time, none of this exists:

- There is no stored markup to diff against, so no invalidation.
- Changing a block's Blade template just changes the output next request. No deprecation needed, ever.
- Migrating a block's data shape is a normal data migration (a script that rewrites the JSON), not a runtime save-function chain.

The one thing worth keeping the *spirit* of: **versioned attribute schemas with explicit migrations.** Store a schema version on each block; when you change a block's attribute shape, write a migration that upgrades old records. That gives you Gutenberg's forward-compatibility benefit without its runtime fragility.

---

## 10. What to copy, what to avoid

**Copy (Gutenberg does these well):**

1. **A block = typed unit with a declarative manifest.** `block.json`-style metadata (name, attributes schema, supports, render target) keeps blocks declarative and lintable. Have a registry mapping type → definition.
2. **The block tree as the model.** A page is an ordered tree of `{ type, attributes, children }`. Clean, recursive, serializable.
3. **Server rendering as the default.** Gutenberg's "dynamic block" (PHP render at request time) is exactly the Laravel model, and it is the half that gives SEO-ready HTML, live data, and instant template updates. In your builder, *every* block works this way.
4. **`supports` + central design tokens (theme.json).** Declare per-block which styling features exist; define palette/type/spacing once; emit as CSS custom properties shared by editor and front end. This keeps styling consistent and the editor preview faithful.
5. **InnerBlocks nesting with guardrails.** `allowedBlocks`, `template`, `templateLock`, parent/ancestor constraints.
6. **Patterns, templates, synced patterns.** Starter layouts, default page scaffolds, and shared blocks that update everywhere. Cheap, high user value.
7. **Versioned schemas with explicit migrations** (the *idea* behind deprecation, done as data migrations).

**Avoid (Gutenberg's self-inflicted complexity, mostly from storing HTML):**

1. **Storing rendered HTML as the source of truth.** Store the JSON tree instead. This single decision deletes sections 5, 9's pain.
2. **Attribute sourcing (`source`/`selector`/`html`/`query`).** Only needed because data must be scraped back out of stored HTML. With a JSON tree, every attribute is just a field.
3. **The validation/invalidation diff and "modified externally" errors.** No stored markup to diff means no invalidation.
4. **The deprecation/migrate/isEligible runtime chain.** Replace with ordinary data migrations.
5. **The pure-`save`-function constraint.** It exists only to make HTML re-serialization deterministic; you don't need it if rendering is request-time and the tree is the truth.
6. **The two-place attribute split (comment JSON vs HTML).** One place for data.

The honest summary: Gutenberg's *data model* (typed blocks, attribute schemas, supports, a nested tree, server rendering for dynamic content, central theme tokens) is excellent and worth copying. Its *storage and consistency model* (everything as annotated HTML, re-parsed and re-validated on every load, with deprecation chains to survive changes) is a workaround for WordPress's `post_content`-is-HTML legacy. A greenfield Laravel builder should keep the model and throw away the workaround. Store the tree as JSON, render with Blade at request time, and you inherit Gutenberg's strengths while skipping its hardest, most fragile machinery. The real work you can't shortcut is the *editor UI*, Gutenberg's `edit` components, which is where most of the actual engineering effort lives.

---

## Sources

All pages from the official WordPress Block Editor Handbook (developer.wordpress.org), verified June 2026.

- Markup representation of a block: https://developer.wordpress.org/block-editor/getting-started/fundamentals/markup-representation-block/
- Metadata in block.json: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/
- block.json (Fundamentals): https://developer.wordpress.org/block-editor/getting-started/fundamentals/block-json/
- Block API Reference: https://developer.wordpress.org/block-editor/reference-guides/block-api/
- Attributes: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-attributes/
- Edit and Save: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/
- Static or Dynamic rendering of a block: https://developer.wordpress.org/block-editor/getting-started/fundamentals/static-dynamic-rendering/
- Creating dynamic blocks: https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/creating-dynamic-blocks/
- Data Flow and Data Format: https://developer.wordpress.org/block-editor/explanations/architecture/data-flow/
- Nested Blocks / InnerBlocks: https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/nested-blocks-inner-blocks/
- Block Deprecation: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-deprecation/
- Block Patterns: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-patterns/
- Global Settings and Styles (theme.json): https://developer.wordpress.org/block-editor/how-to-guides/themes/global-settings-and-styles/
- Block Supports: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/
- Registration of a block: https://developer.wordpress.org/block-editor/getting-started/fundamentals/registration-of-a-block/
- register_block_type(): https://developer.wordpress.org/reference/functions/register_block_type/
- WP_Block_Type_Registry::register(): https://developer.wordpress.org/reference/classes/wp_block_type_registry/register/
- Static vs. dynamic blocks (Developer Blog): https://developer.wordpress.org/news/2023/02/27/static-vs-dynamic-blocks-whats-the-difference/
- Understanding block attributes (Developer Blog): https://developer.wordpress.org/news/2023/09/understanding-block-attributes/
