# 12. Structured data (JSON-LD)

> Written 18 Aug 2026. This is the full schema list, before any of it is built. Feature 11
> listed `FAQPage` and `Organization` and stopped there, which was never the whole picture.

## What it is

Every public page emits one `<script type="application/ld+json">` containing a single
`@graph` of linked nodes: who publishes the site, what the site is, what this page is, where
it sits in the hierarchy, and whatever the page's own type adds on top.

Three sources feed it, and the split matters more than the schema list:

1. **Site-wide facts** a developer sets once: the organisation, its logo, its social
   profiles, its address.
2. **Per-page choices** a client makes: this page is a Service, that one is an Article.
3. **Block-derived facts** nobody types twice: the FAQ block already holds questions and
   answers, so the FAQ schema is a transform of data that exists.

## Why we're building it

Meta tags tell a crawler what a page says. Structured data tells it what the page *is*, and
it is the only part of the head an answer engine can consume without guessing. A client site
selling services in the GCC that never says it is a `LocalBusiness` with an address and
opening hours is invisible to the queries that convert.

The second reason is narrower and is about this product: the content is already structured.
A page is a typed tree, an FAQ block is already a list of question and answer pairs, a
testimonial already has an author and a body. Every other CMS asks the client to type that
twice, once for humans and once for Google. We can generate it.

## How it should feel

Invisible when it is right. A client who picks nothing gets a valid `WebPage` inside a
correct graph, because everything derivable is derived. Someone who does care picks a type
from a select and fills in the four fields that type needs, not forty.

Nothing here is a scorecard, and there is no "SEO health" widget. The failure mode to avoid
is a plugin that nags about completeness while emitting schema that says nothing true.

## In the dashboard

Two surfaces, and only one of them is per page.

### 1. Site details, a new panel screen

Organisation facts belong to the site, not to a page, and they are **client-owned data that
changes without a deploy**: a new phone number, a new Instagram account, moving office. That
rules out config, which is where tokens, locales and layouts live.

A Filament page under a **Settings** nav group, saving to a single-row `atelier_settings`
table:

```
Settings
└── Site details
    ├── Identity      name, legal name, logo, description, type
    ├── Profiles      the social URLs that become sameAs
    └── Contact       telephone, email, address, geo, opening hours,
                      price range, areas served        (LocalBusiness only)
```

Some of it is translatable (name, description), most is not (a phone number is a phone
number). It does not exist today in any form, and every tier 1 node depends on it.

### 2. Page type, on the page settings screen

The existing screen, with one addition. Where it goes matters, so concretely:

```
Pages › Edit                    [Edit page content] [View live] [Publish] …

┌─ Section ──────────────────────────────────────────────────┐
│  Title              Internal name, and the meta fallback   │
│  Layout             The shell wrapped around this page     │
│  Page type          What this page is: Service, Article…   │  ← new
│     └ fields for the chosen type appear here               │  ← new
└────────────────────────────────────────────────────────────┘

┌─ [ English ] [ العربية ] ──────────────────────────────────┐
│  Slug                                                      │
│  Meta title            ─┐                                  │
│  Meta description      ─┴ already the schema's name and    │
│  Social share image       description, per locale          │
│  Hide from search engines                                  │
│  Tell search engines not to follow its links               │
│  Canonical URL                                             │
│  Exclude from structured data                              │  ← new
└────────────────────────────────────────────────────────────┘
```

**The type is page-level, not per locale**, alongside Layout, for the same reason: a page
that is a Service in English is a Service in Arabic. It is one fact about the page, not two.

**Type-specific fields sit with it and are mostly shared too.** A price, a start date, a
latitude and an availability are not translated. The one thing that does vary by language is
the name and the description, and those already exist as the per-locale meta title and meta
description, so the schema reads them rather than asking again. A type needing a genuinely
translatable extra field puts that field in the locale tabs.

That keeps the addition to roughly four fields for a client who picks a type, and zero for a
client who does not.

### Not in the editor

Nothing structured-data goes in the three-pane builder. The builder is for arranging and
filling sections, and a schema type is a property of the page, not of a section. It belongs
with the slug and the meta description, one screen back.

The exception is the part with no UI at all: anything derived from a block, like the FAQ
schema, is generated from what the client already typed into that block and never appears as
a field anywhere.

---

## The schema list

### Tier 1: the graph on every page

The foundation. None of it is optional and none of it is a client decision.

| Schema | Where it comes from | Notes |
|---|---|---|
| `Organization` | Site details | The publisher identity every other node points at. Subtype to `LocalBusiness` and below when the client has premises. |
| `WebSite` | Site details plus config | `name`, `url`, `inLanguage`, `publisher` → Organization. |
| `WebPage` | The page | `@id`, `url`, `name`, `description`, `isPartOf` → WebSite, `inLanguage`, `datePublished`, `dateModified`, `primaryImageOfPage`. |
| `BreadcrumbList` | The slug path | `services/web-design` already gives Home → Services → Web design. Nested slugs shipped in v0.1.2, so this needs no new hierarchy. |
| `ImageObject` | Logo, share image, hero image | Referenced by `@id` rather than repeated inline. |

`LocalBusiness` deserves its own row because of who this is for: `address` (PostalAddress),
`geo`, `telephone`, `openingHoursSpecification`, `priceRange`, `areaServed`, `currenciesAccepted`.
Subtypes worth supporting by name: `ProfessionalService`, `Store`, `Restaurant`, `MedicalBusiness`,
`RealEstateAgent`, `AutoRepair`.

### Tier 2: the page type select

One choice per page, driving which node the `WebPage` sits alongside.

**Page-shaped types** (they replace or refine WebPage):

| Type | Fields it needs | Still earns rich results |
|---|---|---|
| `WebPage` | Nothing beyond tier 1 | Not a rich result, but the correct default |
| `AboutPage` | Nothing | No |
| `ContactPage` | Nothing | No |
| `CollectionPage` | Nothing | No |
| `FAQPage` | Generated from the FAQ block | **Restricted**, see the note below |
| `QAPage` | A single question with answers | Yes, for genuine Q&A pages |
| `ProfilePage` | About a Person | No, supports Person results |

**Thing-shaped types** (the page is about a thing):

| Type | Fields it needs | Still earns rich results |
|---|---|---|
| `Article` / `BlogPosting` / `NewsArticle` | headline, author, datePublished, dateModified, image | Yes, limited |
| `Product` | name, image, description, sku, brand, `Offer` (price, currency, availability), `AggregateRating` | **Yes, the highest value one** |
| `Service` | name, provider, areaServed, serviceType, `Offer` | Indirect, feeds knowledge panels |
| `Event` | name, startDate, endDate, location, `Offer`, performer | Yes |
| `Person` | name, jobTitle, image, sameAs, worksFor | Supports knowledge panels |
| `LocalBusiness` | As tier 1, on the page rather than site-wide | Yes |
| `JobPosting` | title, datePosted, hiringOrganization, jobLocation, baseSalary | Yes |
| `Course` | name, provider, offers, hasCourseInstance | Yes |
| `Recipe` | name, image, ingredients, instructions, cookTime | Yes |
| `SoftwareApplication` | name, operatingSystem, applicationCategory, offers | Yes |
| `VideoObject` | name, description, thumbnailUrl, uploadDate, duration | **Yes, and undervalued** |
| `HowTo` | steps, supplies, tools | **No longer**, see below |

Not every one of these belongs in v1 of this feature. The tiering below says which.

### Tier 3: generated from blocks, never typed twice

This is the part no other builder does well, and it is where the leverage is.

| Block | Schema it produces | Confidence |
|---|---|---|
| `FaqBlock` | `FAQPage` with `mainEntity` of `Question` and `acceptedAnswer` | Direct. The repeater is already the right shape. |
| `GalleryBlock` | `ImageGallery` with `ImageObject` entries, alt text as `caption` | Direct |
| `ImageBlock` | `ImageObject`, and `primaryImageOfPage` when it is the first | Direct |
| `HeroBlock` | `headline`, `description` and `primaryImageOfPage` on the WebPage | Direct |
| `TestimonialsBlock` | `Review` with `author` → Person | **Needs care**, see below |
| `FeaturesBlock` | `ItemList` of the feature titles | Low value, probably skip |
| `LogoWallBlock` | Nothing worth emitting | Skip |
| A future video block | `VideoObject` | Direct, and worth building the block for |

**Testimonials need care.** Google does not show review rich results for reviews a business
publishes about itself, so emitting `Review` nodes pointing at the site's own Organization
is at best ignored and at worst a manual action. If it is emitted at all it should be
`Review` attached to a `Product` or `Service` the page is actually about, and the
testimonials block currently has no rating field, so there is no `AggregateRating` to give.
The honest options are to skip it, or to add a rating field and only emit when the page type
is Product or Service. Decide before building.

### Tier 4: cross-cutting decisions

These are not schemas, they are the decisions that make the schemas correct.

- **One `@graph`, one script tag.** Nodes reference each other by `@id` instead of repeating
  the organisation on every node. Multiple script tags are valid and are harder to keep
  consistent.
- **`@id` scheme**, fixed once and never changed: `{url}#webpage`, `{site}#organization`,
  `{site}#website`, `{url}#breadcrumb`, `{url}#faq`, `{url}#primaryimage`.
- **`inLanguage` on every node**, and the Arabic page describes the same thing in Arabic.
  This is where a bilingual builder gets an easy win most sites get wrong.
- **Encoding is a security boundary.** JSON-LD goes inside a `<script>` block, so the encode
  must escape `<`, `>`, `&`, `'` and `"` or a client typing `</script>` into a heading
  changes the page. `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`, plus
  `JSON_UNESCAPED_UNICODE` so Arabic stays readable rather than becoming `\uXXXX` soup.
- **Never emitted in the preview.** The preview is `noindex` and structured data there is
  noise.
- **A registry, like the sitemap has.** A blog or services resource on its own panel tab
  needs to contribute `Article` or `Service` nodes for its own pages, and Atelier cannot
  know about them. Same shape as `->sitemap([...])`.
- **Everything is derived at render, nothing is stored.** No `structured_data` JSON column.
  The tree is the truth and the graph is a projection of it, the same rule the rest of the
  package follows.

### What Google actually rewards, as of writing

Worth stating plainly, because half the schema advice online is out of date and this list
decides the build order:

- **`FAQPage` rich results were restricted in August 2023** to well-known government and
  health sites. Emitting it is still right, for Bing and for answer engines that read it,
  but it will not put an accordion under the client's listing. My earlier note calling it
  "the highest value item" was wrong on that basis; it is the cheapest item, not the highest
  value.
- **`HowTo` rich results were dropped in September 2023.**
- **The sitelinks search box was deprecated in November 2024**, so `WebSite` `SearchAction`
  is no longer worth emitting.
- **Still earning rich results:** Breadcrumb, Product with Offer, Event, Video, JobPosting,
  Course, Recipe, Q&A, and Article in a limited way.

Verify each against Google's current documentation before building it. This list is accurate
to the best of what is known now, and it is exactly the kind of thing that changes quietly.

---

## Tasks

### Foundation, and nothing works without it

- [ ] A **Site details** settings screen, panel-level, holding the organisation. Every tier 1
      node depends on it, and the package currently has nowhere to put a site-wide setting.
- [ ] `Schema` builder: an object that assembles `@graph`, deduplicates by `@id`, drops empty
      values, and encodes safely.
- [ ] Emit from `atelier::partials.meta` so a custom layout gets it with the head it already
      includes.
- [ ] `Organization`, with the `LocalBusiness` subtype and its address, hours and geo.
- [ ] `WebSite`, linked to Organization by `@id`.
- [ ] `WebPage` per page and locale, with dates from `published_at` and `updated_at`.
- [ ] `BreadcrumbList` derived from the slug path.
- [ ] `ImageObject` for the logo and the page's primary image.
- [ ] Never emit in the preview.
- [ ] Validate the graph against the Rich Results Test and the Schema.org validator, both.

### The page type select

- [ ] Type select in the page-level section next to Layout, defaulting to WebPage. Page-level rather than per locale: a page that is a Service in English is a Service in Arabic.
- [ ] Conditional fields per type, so choosing Event asks for a start date and nothing else.
- [ ] First set of types: `WebPage`, `AboutPage`, `ContactPage`, `Article`, `Service`,
      `Product`, `Event`, `LocalBusiness`, `Person`.
- [ ] Second set, once the first is proven: `CollectionPage`, `QAPage`, `ProfilePage`,
      `JobPosting`, `Course`, `SoftwareApplication`, `VideoObject`.
- [ ] An **exclude from structured data** toggle for the page that should say nothing.

### Generated from blocks

- [ ] A way for a block class to contribute nodes, declared on the block the way `supports()`
      and `translatable()` are. Adding a block that contributes schema must not mean editing
      a file inside the plugin.
- [ ] `FaqBlock` → `FAQPage`.
- [ ] `HeroBlock` → headline, description, `primaryImageOfPage`.
- [ ] `ImageBlock` and `GalleryBlock` → `ImageObject` and `ImageGallery`.
- [ ] Decide the testimonials question above before writing any of it.
- [ ] Two FAQ blocks on one page merge into one `FAQPage`, rather than emitting two.

### For pages Atelier does not own

- [ ] A registry taking nodes from the host app, the same shape as `->sitemap([...])`, so a
      blog resource can emit `Article` for its own routes.
- [ ] Document it with a worked example, like the sitemap sources are documented.

## Done when

- Every public page emits one valid `@graph` that passes both validators, with no page
  needing anything filled in to be valid.
- A page with an FAQ block emits `FAQPage` whose questions match the rendered accordion
  exactly, in both locales, with nothing typed twice.
- A client marking a page as a Service and filling four fields gets a correct `Service` node
  linked to the site's Organization.
- A `</script>` typed into a heading changes nothing about the page.
- The Arabic page emits the same graph shape with `inLanguage: ar` and Arabic values.
- PRD criterion 9 is fully met, which needs JSON-LD and currently has only meta and sitemap.

## Note

The order above is deliberate. The foundation is worth more than any single rich result,
because a correct Organization and WebPage graph is what every other node hangs off, and it
is what answer engines read first. Building `Product` before there is an `Organization` to
attach it to produces a node that validates and says nothing.

The one prerequisite worth calling out: **there is no site-level settings screen anywhere in
the package.** Tokens, locales and layouts are all config or code. The organisation is
client-owned data, it changes without a deploy, and it needs a database-backed screen. That
is the first thing to build, and it is bigger than it sounds.
