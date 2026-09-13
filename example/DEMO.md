# Sparkle Clean, the demo site

A small cleaning company built entirely on Atelier, so the pieces can be seen working
together rather than one at a time. Everything in this folder is example-app code: none of
it ships in the package, and all of it is the kind of thing a client project would write.

## Running it

```bash
composer install
php artisan migrate:fresh --seed     # builds the whole site
npm install && npm run build         # or npm run dev while you edit views
php artisan serve
```

Panel at `/admin`, `test@example.com` with the password Laravel's factory sets (`password`).

> If the public pages come up unstyled, delete `public/hot`. It is left behind by a
> `npm run dev` that was killed, and it points Vite at a server that is no longer running.

## What is in it

**Five ordinary pages**, built from blocks and listed under Pages in the panel: Home, About
us, Services, Products, Contact us. Nothing distinguishes them from a page on any other
Atelier site.

**Three services**, under their own Services entry in the sidebar. Each carries the fields
`App\PageTypes\ServiceType` declares: a short description, an icon, a typical duration, and
three pricing tiers with one marked **Suggested**. Their pages are at `/services/{slug}`.

**Three products**, under Products, with variations that price themselves: sizes for the
cleaner, pack counts for the cloths, a carpet glider option on the mop.

**Requests**, the inbox. Every form on the site posts here, split into tabs for services,
products, what is unanswered and what was won. The dashboard carries three stats: how many
of each came in, and what the won ones add up to. No payment gateway: a request is a lead,
and the client works it by hand.

## The trail worth following

1. `/services/home-deep-clean` on the public site. The pricing table, the highlighted tier,
   and a form that already has that tier selected.
2. Send the form. It lands in **Requests** in the panel within the second.
3. Open **Services → Home deep clean** in the panel, change a tier's price, save.
4. Reload the public page. The pricing table, the card on the homepage and the form's
   options all followed, because all three read the same `tiers` field.
5. Open the builder on that page. The section picker offers only the blocks `ServiceType`
   allows, and the preview renders through the same views the public page uses.

## Which file does what

| File | What it is |
| --- | --- |
| `app/PageTypes/ServiceType.php` | The Service type: its fields, its tiers, its starter sections, its card |
| `app/PageTypes/ProductType.php` | The Product type, thinner on purpose |
| `app/Blocks/SiteBlocks.php` | The nine sections this site can be built from. The only list the panel reads |
| `app/Blocks/HeroBlock.php` and friends | One class per section. Each has a matching view in `resources/views/blocks` |
| `app/Blocks/PricingBlock.php` | A block with no pricing fields of its own. It reads the page's tiers |
| `app/Blocks/RequestFormBlock.php` | One form block that adapts to the page it is on |
| `app/Blocks/CollectionBlock.php` | The listing block. Queries services or products, renders each through its card |
| `app/Http/Controllers/EnquiryController.php` | The only public write. Prices come from the page, never from the form |
| `app/Filament/Resources/EnquiryResource.php` | Requests, with the tabs |
| `app/Filament/Widgets/RequestsOverview.php` | The three dashboard stats |
| `resources/views/services/card.blade.php` | One service in a listing. The only file that knows the field names |
| `resources/views/products/card.blade.php` | The same for a product |
| `resources/views/blocks/*.blade.php` | One view per block. The markup of every section |
| `database/seeders/SparkleCleanSeeder.php` | Every page, service, product and menu above |
| `config/atelier.php` | The palette, as design tokens the blocks and the preview both read |

## Things it is deliberately demonstrating

- **Every block on this site is defined in this app.** `SiteBlocks::all()` is what the
  panel registers, and the package's `DefaultBlocks::all()` is deliberately not used, so
  nothing on the site sends you into the package to find out what a section does. A real
  project would usually start from the shipped set and add to it; this one starts from
  nothing to keep the example readable.
- **A page type is one plain class.** Two of them here, and neither is a Filament resource.
- **A block can read the page it is on.** The pricing table and the request form both do,
  which is what lets one form block serve services, products and a plain contact page.
- **Prices are never trusted from the browser.** The form posts an option name; the
  controller looks the price up on the page. `tests/Feature/EnquiryTest.php` proves it.
- **A real page beats a generated one.** `ServiceType` declares an index view, so `/services`
  would list the services on its own. The seeded Services page claims that slug and takes
  over. Unpublish it and the generated listing comes back.
- **The palette lives in tokens.** `config/atelier.php` sets teal once and the hero button,
  the call-to-action panel and the section backgrounds all follow, in the editor preview as
  well as on the public page.

## The Arabic side

The content is English. Arabic pages resolve (`/ar/about`, `/ar/خدمات`) and fall back to the
English text, which is what a half-translated site looks like in this system: readable, not
broken. Translate `excerpt` on a service to see a card change language.
