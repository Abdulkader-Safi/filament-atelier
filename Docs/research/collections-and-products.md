# Collections and products: how repeatable content fits a block-based page model

> Research brief, 31 Aug 2026. Written to answer one question: can Atelier hold services,
> blog posts and products as their own types, and loop over them from a page, without
> turning into a different product. Not a decision and not a task file. Prior art verified
> against lunarphp.com, developer.wordpress.org and elementor.com in August 2026.
>
> The open questions at the bottom are the ones to answer before a task file gets written.

## The question

Today a page is a JSON tree of blocks. That works for a homepage and an about page. It
falls apart the moment the content repeats:

- A services index has to list every service. Right now that means adding each one by hand
  as a block, and adding a service later means editing the index page too.
- A blog needs the same thing at a hundred times the volume.
- A product needs all of that plus price, categories, variants, per-variant images and
  per-variant descriptions.

The ask is a second kind of thing in the panel next to Pages: a Services tab, a Blog tab, a
Products tab, each with its own entries, each entry built from blocks, and a way for a page
to loop over them.

## The one distinction that makes this tractable

There are two different things being conflated, and every CMS that handles this well keeps
them apart:

**Content** is typed once and laid out freely. A service description, an about page, a case
study. The layout is part of the message, so the person writing it wants to arrange it. A
block tree is exactly right.

**Records** are typed many times into the same shape and read by code. A price is a number
that gets compared, formatted in a currency and sorted on. A variant is a row that changes
what goes in a cart. A category is something you filter by. These need columns, not a
freeform tree, because something other than a human reads them.

A service is mostly content with a couple of record-ish fields. A blog post is content with
a date and an author. A product is mostly record with some content wrapped around it.

Trying to store a price inside a block tree means you can never sort by it, filter on it, or
show it in a cart. Trying to store a page layout in columns means every page looks the same.
The answer is not to pick one. It is to let the record own the data and the blocks own the
presentation, with the blocks able to read from the record.

That single sentence is what the rest of this document unpacks.

## What everyone else does

**WordPress with ACF.** A custom post type gives you the tab in the sidebar. ACF bolts typed
fields onto it. The single-product template is a PHP file the developer writes, reading
those fields. The client fills a form and never touches layout. This is why WordPress ate
the market for this: the split between "developer owns the layout, client owns the data" is
absolute and everyone understands it.

**Gutenberg.** The Query Loop block loops posts and renders a template for each one. The
newer Block Bindings API lets a normal block attribute be bound to a post meta field instead
of a typed literal, so a heading block inside the loop can say "this post's title" rather
than fixed text. Inside a Query Loop the binding resolves against whichever post the current
iteration is on. Two mechanisms: one to loop, one to bind.

**Elementor Pro.** The Loop Grid does the looping, a theme-builder single template does the
per-entry layout, and dynamic tags are the binding. Every field in the editor grows a small
lightning-bolt icon that switches it from a typed value to a data source. Same two
mechanisms, different names.

**Statamic and Craft.** Collections of entries, with a blueprint declaring the fields.
Layout comes from templates. Craft's Matrix field is the escape hatch when an entry does
need a freeform tree, which is the same shape as our block tree.

**Lunar, the Laravel one worth reading.** A product belongs to a product type, and the
product type decides which attributes the product has. A product always has at least one
variant, and when there is only one, the panel hides the distinction and looks like you are
editing the product directly. Variants hold price, stock, SKU and shipping. Options and
option values (size, colour, and their values) attach to variants through a pivot table, and
both carry a free-form `meta` JSON column for things like a colour's hex value. Shopify's
model is the same idea: a small number of options, and one variant row per combination of
them.

The pattern across all five: **the loop and the binding are separate features, and the
record is never stored in the layout.**

## How this maps onto Atelier

Three tiers, in increasing order of how much work they are.

### Tier 1: pages (built)

Freeform block tree, one row in `atelier_pages`, a slug per locale. Nothing changes.

### Tier 2: content collections (small)

Services, blog posts, case studies, team members. These are pages that happen to be grouped.

The storage decision is already made: a `collection` column on `atelier_pages`. Null means a
normal page, `services` means a service. Every single thing pages already have then works on
day one with no new code: per-locale slugs, redirects when a slug changes, draft and
published trees, revisions, the live preview, meta tags, JSON-LD, the sitemap and
`/services/web-design` routing. The Pages list gets scoped to `collection is null` so
entries do not show up there, and each collection gets its own scoped resource in the
sidebar.

A collection is declared in code, next to the blocks, and says which blocks its entries may
use:

```php
AtelierPlugin::make()
    ->blocks([...])
    ->collections([
        Collection::make('services')
            ->label('Services')
            ->icon('heroicon-o-wrench-screwdriver')
            ->prefix('services')          // entries live at /services/{slug}
            ->blocks(['hero', 'features', 'faq', 'cta']),
    ]);
```

That is the whole of the "specify what block it can use" ask: an allowlist the section
picker filters by. Leave it out and the entry gets every block, same as a page.

### Tier 3: catalogue records (the real work)

Products. Also services when a service grows a price and a booking flow. Here the entry is a
record with real columns, and Atelier is the presentation layer over it rather than the
owner of it.

## Walking through a product

This is the case worth being concrete about, because it is where the block model stops being
obviously right.

### What a product page is actually made of

Take a t-shirt page. Break it into pieces and each piece belongs to a different owner:

| Piece | Who owns it |
| --- | --- |
| Name, SKU, price, stock | The record |
| Colour and size options, and the variant rows they produce | The record |
| Images, including per-variant images | The record |
| Categories | The record |
| The gallery, the colour swatches, the size picker, the price that changes when you pick, the add-to-cart button | One block, written in code |
| Long description, spec table, FAQ, shipping info, related products, reviews | Ordinary Atelier blocks the client arranges |

The middle row is the one that breaks the naive approach. A variant picker is not layout. It
is behaviour: clicking blue has to change the price, swap the gallery image, update the
stock line and change what goes in the cart. Four things move together. If those were four
separate blocks the client could drag apart, they would need to share state across the page,
and the client could delete the price and keep the button.

So it is one block. Call it the product panel or the buy box. The client places it and
chooses a variant style (swatches or a dropdown), and its internals are code. This is not a
limitation, it is the same call every serious builder makes: WooCommerce ships a single
add-to-cart block, not a kit of parts.

Everything around it stays fully freeform. That is where the client actually wants control,
and it is where they get it.

### Where does the product page's layout live

Not on each product. Two hundred products cannot be laid out by hand, and more importantly
they should not be: a catalogue where every product page is arranged differently is a worse
catalogue.

**One template per collection.** You build `/products/[entry]` once in the builder, using
the same three-pane editor, with the panel block and whatever prose blocks belong there. It
is a `atelier_pages` row flagged as that collection's template. Every product renders through
it. Change the template, all two hundred change.

While editing the template the preview needs something to render, so it picks a real entry
to preview against, with a switcher to change which one. Same preview path, same views, one
extra variable.

**Per-entry override, for the rare exception.** A flagship product with a bespoke landing
page keeps its own block tree, and the renderer uses it when it is not empty and the template
when it is. This is the right place for the idea of hanging a JSON column off your own model:
it is the exception, not the primary storage.

### Variants, concretely

Follow Lunar rather than inventing one. The shape is well worn and every developer coming to
the project will recognise it:

```
products            id, name, description, product_type_id, ...
product_variants    id, product_id, sku, price, stock, ...
product_options     id, name            e.g. Colour, Size
product_option_values  id, option_id, value, meta   e.g. Blue + { "hex": "#1e40af" }
option_value_variant   pivot
```

A product with no options still has one variant, and the panel hides that from the client so
adding a simple product is not a lecture on data modelling.

"A description per variation" is worth naming as a trap. It is usually not wanted. Most
stores want one description, plus a short per-variant note. If a variant genuinely needs a
whole page of its own content, that variant is a product. Lunar's `meta` column is the
pressure valve for the small stuff.

### Who owns the products table

Two answers, and the choice depends on the project rather than on this package.

**Atelier owns it.** Ships migrations and a Filament resource for products, variants and
options. Turnkey for a brochure site with a small catalogue. It also means Atelier is now
partly a shop, and every project inherits a schema that will not fit some of them.

**The app owns it, Atelier reads it.** The client app defines `Product` however it wants,
including using Lunar, and implements a small interface so Atelier can loop it, link it, put
it in a menu and put it in the sitemap. This is already how the package handles menus
(`MenuSource`) and extra sitemap URLs, so it is the established pattern here rather than a
new idea:

```php
interface CollectionSource
{
    public static function collectionKey(): string;      // 'products'
    public static function collectionLabel(): string;
    public static function collectionQuery(): Builder;   // published scope, eager loads
    public function cardData(string $locale): array;     // title, excerpt, image, url, price
    public function entryUrl(string $locale): string;
}
```

The recommendation is the second one, with the first left as something a separate package can
add later. Atelier's whole argument is that it does not own things it does not need to own.

## The loop block

One block, on any page, in any collection's template. Settings: which collection, how to
order, how many, how many columns, which card view. It queries and renders a card per entry.

```
[Collection list]
  collection   Services
  order        Newest first
  limit        6
  columns      3
  card         Standard card
```

This is the piece that answers the original complaint. The services index stops being a page
someone edits every time a service is added.

Three things it needs to get right:

- **One query, eager loaded.** A card showing a slug means loading slugs, and twelve cards
  doing that one at a time is twelve extra queries. This is a rendering path that runs on
  every public page view.
- **Server-side rendered, always.** The non-negotiable about Blade SSR applies here more than
  anywhere. A client-side listing is invisible to search engines, which defeats the point of
  having a services index at all.
- **A card is a Blade view, registered like a layout.** The developer writes the markup, the
  client picks from the registered ones. Not thirty style fields.

Card content came up already: for Atelier-owned entries it can read the title, the meta
description and the social share image, all of which exist on every page today, so a card
works with nothing extra filled in. Worth flagging once and then leaving to the open question
at the bottom: a meta description is written for a search result, and the sentence that works
under a blue link is often the wrong sentence on a card. The middle path is to read the SEO
fields by default and allow a per-entry override, which costs one optional field.

## How a block reads the record

The template needs a heading block that says "this product's name" rather than fixed text.
Three ways to do it, cheapest first.

**Bound blocks.** A handful of small blocks that only work inside a collection template:
entry title, entry image, entry body, product panel. Each reads the current record from view
data. Nothing new to learn, nothing to parse, no new attack surface. The cost is one block per
kind of field, and a template can only use the record in the places those blocks exist.

**Field-level binding.** Any text field in any block schema gains a small toggle: typed
value, or from the record, pick a field. This is Elementor's dynamic tags and WordPress's
block bindings, and it is the best version of this. It is a moderate build: a control on the
field, a stored `{ "field": "price" }` shape next to the existing `{ "token": "color.primary" }`
shape, and a resolver in the renderer that already resolves tokens the same way.

**Free-text placeholders.** Letting the client type `{{ entry.title }}` into any field is the
cheapest to build and the worst of the three. It is invisible until it renders, it has no
autocomplete, and it is one careless line away from the standing rule that user input is never
passed to `Blade::render()`, because that compiles to PHP and turns a textarea into remote
code execution. If placeholders ever happen they get resolved by a string replace against a
whitelist, never by the Blade compiler.

Recommendation: bound blocks first, since they are days of work and cover the real templates.
Field-level binding second, as its own feature, because the token resolver it needs already
exists in `Renderer::resolveTokens()` and the shape is a near copy.

## Categories

Real for products, wanted for blogs, mostly noise for services.

If the app owns the products table, it owns categories too and Atelier just reads them
through the interface. If Atelier owns a content collection, categories mean a taxonomy
table, a picker in the entry form, a filter on the loop block, and eventually archive URLs
like `/blog/category/design` with their own titles and meta. That last part is a feature the
size of the menu manager, and it should not be smuggled into this one.

## What Atelier should not do

Cart, checkout, payment, tax, shipping rates, stock decrementing, orders. Lunar exists, it is
Laravel, and its admin is already built on Filament. Atelier renders the catalogue and the
buy box hands off to whatever cart the app has.

Services with payment are easier than they look, as you said: the panel is a button that goes
to an enquiry page or a booking link. That is a block with a link field, which the package can
already do today.

## A build order that stays shippable

**A. Content collections and the loop block.** The `collection` column, the registry, the
per-collection scoped resource, the block allowlist, the loop block and one card view. This
alone fixes the services index and the blog index. Everything reuses machinery that already
exists, so this is the smallest useful slice by a wide margin.

**B. Templates and bound blocks.** A collection can have a template, the preview picks an
entry to render against, and entry title, image and body blocks exist. This is what makes a
blog with three hundred posts sane.

**C. External records.** The `CollectionSource` interface, so an app's own `Product` or Lunar
model can be looped, linked and listed. Plus the product panel block, written against whatever
model the first real project uses.

**D. Only when a project needs it.** Taxonomy and filtering, pagination on the loop block,
field-level binding.

A and B are the ones worth committing to now. C is worth designing against a real product
model rather than a hypothetical one, and there is no reason to guess at it before then.

## Open questions, to answer before a task file gets written

1. **Products: does Atelier ship the tables, or read yours?** The recommendation is to read
   yours through an interface, so a project can use Lunar or a hand-rolled `Product` without
   Atelier having an opinion. The counter-argument is that a client site with fifteen products
   and no cart then has to hand-write a model, a migration and a Filament resource before it
   can list anything. Which of those two projects is the one you are actually building next?

2. **Is a product page a template, or a page per product?** The recommendation is one template
   per collection, with a per-product override for the rare bespoke one. Confirm that a client
   arranging the layout once for all products, rather than per product, is what you want. It
   is the difference between "add a product" being a two minute form and a twenty minute build.

3. **Card text.** SEO fields as the source is settled. Do you also want an optional per-entry
   card excerpt that overrides the meta description when it is filled, or should the card stay
   strictly whatever SEO says?

4. **Blog post bodies.** A post can be a full block tree like a page, or a single rich text
   field rendered by the template. The second is what people expect from a blog and is much
   faster to write into. Which one, or both with the block tree as an option?

5. **Categories in the first version.** Services do not need them. A blog usually does, at
   least as labels on a card. The suggestion is to ship without them and add a taxonomy as its
   own feature once a real project asks. Does anything you are building in the next month need
   filtering by category?

6. **How many entries, realistically.** Tens means the loop block takes a limit and a "view
   all" link and that is the end of it. Hundreds means pagination, paged URLs, canonical and
   prev/next tags, and a decision about whether page two gets indexed. Worth knowing before
   the block is written, because retrofitting paging changes its URL handling.

## Sources

- [Lunar: products reference](https://docs.lunarphp.com/1.x/reference/products)
- [Lunar](https://lunarphp.com/)
- [WordPress developer blog: introducing block bindings](https://developer.wordpress.org/news/2024/02/introducing-block-bindings-part-1-connecting-custom-fields/)
- [The block bindings API brings dynamic data to blocks, WP Tavern](https://wptavern.com/the-block-bindings-api-brings-dynamic-data-to-blocks)
- [Kinsta: the WordPress block bindings API](https://kinsta.com/blog/wordpress-block-bindings-api/)
