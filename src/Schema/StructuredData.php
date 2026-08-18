<?php

declare(strict_types=1);

namespace Safi\Atelier\Schema;

use Illuminate\Support\Str;
use Safi\Atelier\Block;
use Safi\Atelier\BlockRegistry;
use Safi\Atelier\Media;
use Safi\Atelier\Models\Page;
use Safi\Atelier\Models\SiteSettings;
use Safi\Atelier\Renderer;

/**
 * Builds the JSON-LD graph for a page.
 *
 * Three sources feed it and the split is the whole design: site-wide facts a
 * developer sets once on the Site details screen, per-page choices a client
 * makes, and facts derived from blocks that already hold them.
 *
 * Nothing is stored. The tree is the truth and the graph is a projection of
 * it, the same rule the rest of the package follows.
 */
class StructuredData
{
    public function __construct(
        protected BlockRegistry $blocks,
        protected Renderer $renderer,
        protected Graph $graph = new Graph,
    ) {}

    /**
     * `@id`s that came from the settings screen rather than from a block.
     *
     * Blocks skip these, so typing a FAQ by hand replaces the derived one
     * instead of listing every question twice. Tracked rather than inferred
     * from the graph, because two FAQ blocks on one page still have to merge
     * with each other.
     *
     * @var array<int, string>
     */
    protected array $typed = [];

    /** Every `@id` is derived, never stored, so the scheme is fixed here once. */
    public static function id(string $url, string $fragment): string
    {
        return rtrim($url, '/').'#'.$fragment;
    }

    public static function siteId(string $fragment): string
    {
        return rtrim(url('/'), '/').'#'.$fragment;
    }

    /** The whole graph for one page in one locale. */
    public function forPage(Page $page, string $locale): Graph
    {
        $url = $page->seo($locale, 'canonical') ?: $page->url($locale);

        if ($url === null) {
            return $this->graph;
        }

        $this->organization($locale)
            ->website($locale)
            ->webPage($page, $locale, $url)
            ->breadcrumbs($page, $locale, $url)
            ->mainEntity($page, $locale, $url)
            ->faq($page, $locale, $url)
            ->fromBlocks($page, $locale, $url);

        return $this->graph;
    }

    /**
     * FAQ typed on the page settings screen, rather than derived from a block.
     *
     * A page can carry FAQ data whatever it is built from, including a page
     * with no FAQ block at all. Where the questions do appear in a block, the
     * block generates them and nothing needs typing.
     *
     * Google expects FAQ data to correspond to something a visitor can see on
     * the page. Typing questions here that appear nowhere on it is against
     * the structured data guidelines, so this is for content that is on the
     * page in some other form, a rich text section most often.
     */
    public function faq(Page $page, string $locale, string $url): static
    {
        $questions = collect(data_get($page->schema, "faq.{$locale}", []))
            ->map(fn (mixed $item) => Graph::node([
                '@type' => 'Question',
                'name' => is_array($item) ? trim(strip_tags((string) ($item['question'] ?? ''))) : null,
                'acceptedAnswer' => Graph::node([
                    '@type' => 'Answer',
                    'text' => is_array($item) ? trim(strip_tags((string) ($item['answer'] ?? ''))) : null,
                ]),
            ]))
            ->filter()
            ->filter(fn (array $question) => isset($question['name'], $question['acceptedAnswer']))
            ->values()
            ->all();

        if ($questions === []) {
            return $this;
        }

        $id = static::id($url, 'faq');

        $this->graph->add([
            '@type' => 'FAQPage',
            '@id' => $id,
            'inLanguage' => $locale,
            'mainEntity' => $questions,
        ]);

        $this->typed[] = $id;

        return $this;
    }

    /**
     * Whatever the blocks on the page contribute.
     *
     * The published tree, not the draft, and the same localisation the
     * renderer used, so the schema says exactly what the page shows. A block
     * that says nothing costs one method call.
     *
     * Hidden sections are skipped: they are not on the page, so claiming them
     * in the schema would be describing something a visitor cannot see, which
     * is the definition of the thing Google penalises.
     */
    public function fromBlocks(Page $page, string $locale, string $url): static
    {
        foreach ($page->published() as $node) {
            if ($node['hidden'] ?? false) {
                continue;
            }

            $block = $this->blocks->resolve($node['type'] ?? '');

            if (! $block instanceof Block) {
                continue;
            }

            $attributes = $this->renderer->attributesFor($block, $node, $locale);

            foreach ($block::structuredData($attributes, $locale, $url) as $contributed) {
                // Typed wins. Merging the two would list the same question
                // twice whenever somebody typed what a block already says.
                // Only what was typed, though: two FAQ blocks on one page
                // still merge with each other.
                if (in_array($contributed['@id'] ?? null, $this->typed, true)) {
                    continue;
                }

                $this->graph->add($contributed);
            }
        }

        return $this;
    }

    /**
     * Who publishes the site.
     *
     * The node every other one points at, which is why it is emitted on every
     * page rather than only the home page: a crawler arriving on a deep page
     * should not have to find the home page to learn who published it.
     */
    public function organization(string $locale): static
    {
        $type = SiteSettings::get('type', 'Organization');
        $logo = Media::url(SiteSettings::get('logo'));

        $this->graph->add([
            '@type' => $type,
            '@id' => static::siteId('organization'),
            'name' => SiteSettings::translated('name', $locale) ?: config('app.name'),
            'legalName' => SiteSettings::get('legal_name'),
            'description' => SiteSettings::translated('description', $locale),
            'url' => url('/'),
            'logo' => $logo ? ['@id' => static::siteId('logo')] : null,
            'image' => $logo ? ['@id' => static::siteId('logo')] : null,
            'sameAs' => array_values(array_filter((array) SiteSettings::get('same_as', []))),
            'telephone' => SiteSettings::get('telephone'),
            'email' => SiteSettings::get('email'),
            'address' => $this->address(),
            'geo' => $this->geo(),
            'priceRange' => SiteSettings::get('price_range'),
            'areaServed' => $this->areaServed(),
            'openingHoursSpecification' => $this->openingHours(),
            'contactPoint' => $this->contactPoints(),
            'foundingDate' => SiteSettings::get('founding_date'),
            'numberOfEmployees' => SiteSettings::get('employees'),
            'vatID' => SiteSettings::get('vat_id'),
            'taxID' => SiteSettings::get('tax_id'),
        ]);

        if ($logo) {
            $this->graph->add([
                '@type' => 'ImageObject',
                '@id' => static::siteId('logo'),
                'url' => $logo,
                'contentUrl' => $logo,
            ]);
        }

        return $this;
    }

    /** @return array<string, mixed>|null */
    protected function address(): ?array
    {
        return Graph::node([
            '@type' => 'PostalAddress',
            'streetAddress' => SiteSettings::get('address.street'),
            'addressLocality' => SiteSettings::get('address.locality'),
            'addressRegion' => SiteSettings::get('address.region'),
            'postalCode' => SiteSettings::get('address.postal_code'),
            'addressCountry' => SiteSettings::get('address.country'),
        ]);
    }

    /** @return array<string, mixed>|null */
    protected function geo(): ?array
    {
        $latitude = SiteSettings::get('geo.latitude');
        $longitude = SiteSettings::get('geo.longitude');

        // Half a coordinate is not a place.
        if (blank($latitude) || blank($longitude)) {
            return null;
        }

        return [
            '@type' => 'GeoCoordinates',
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
        ];
    }

    /** @return array<int, array<string, string>>|null */
    protected function areaServed(): ?array
    {
        $areas = collect(explode(',', (string) SiteSettings::get('area_served', '')))
            ->map(fn (string $area) => trim($area))
            ->filter()
            ->values();

        return $areas->isEmpty()
            ? null
            : $areas->map(fn (string $area) => ['@type' => 'Place', 'name' => $area])->all();
    }

    /**
     * When the doors are open.
     *
     * One specification per set of hours, listing the days that share them,
     * which is the shape schema.org wants and also the shape a person thinks
     * in: weekdays nine to six, Saturday ten to two.
     *
     * @return array<int, array<string, mixed>>|null
     */
    protected function openingHours(): ?array
    {
        $hours = collect(SiteSettings::get('opening_hours', []))
            ->filter(fn (mixed $row) => is_array($row) && filled($row['days'] ?? null))
            ->map(fn (array $row) => Graph::node([
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => array_values((array) $row['days']),
                'opens' => $row['opens'] ?? null,
                'closes' => $row['closes'] ?? null,
            ]))
            ->filter()
            ->values()
            ->all();

        return $hours ?: null;
    }

    /**
     * Who answers, and in which language.
     *
     * `telephone` on the organisation says there is a number. A contact point
     * says it is answered by sales, in Arabic and English, which is the part
     * a search result can actually use.
     *
     * @return array<int, array<string, mixed>>|null
     */
    protected function contactPoints(): ?array
    {
        $points = collect(SiteSettings::get('contact_points', []))
            ->filter(fn (mixed $row) => is_array($row))
            ->map(fn (array $row) => Graph::node([
                '@type' => 'ContactPoint',
                'contactType' => $row['type'] ?? null,
                'telephone' => $row['telephone'] ?? null,
                'email' => $row['email'] ?? null,
                'availableLanguage' => $this->list($row['languages'] ?? null),
                'areaServed' => $this->list(SiteSettings::get('area_served')),
            ]))
            ->filter()
            ->values()
            ->all();

        return $points ?: null;
    }

    /**
     * A comma separated field into a list.
     *
     * @return array<int, string>|null
     */
    protected function list(?string $value): ?array
    {
        $items = collect(explode(',', (string) $value))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();

        return $items ?: null;
    }

    /** The site itself, so a page can say which site it is part of. */
    public function website(string $locale): static
    {
        $this->graph->add([
            '@type' => 'WebSite',
            '@id' => static::siteId('website'),
            'url' => url('/'),
            'name' => SiteSettings::translated('name', $locale) ?: config('app.name'),
            'inLanguage' => $locale,
            'publisher' => ['@id' => static::siteId('organization')],
        ]);

        return $this;
    }

    /** This page. */
    public function webPage(Page $page, string $locale, string $url): static
    {
        $image = Media::url($page->seo($locale, 'og_image'));

        $type = $page->schemaType();

        $this->graph->add([
            // A page-shaped type refines what the page is, so it replaces the
            // WebPage type rather than sitting beside it. An About page is a
            // WebPage; a page about a product is not a product.
            '@type' => PageTypes::isPageShaped($type) ? $type : 'WebPage',
            '@id' => static::id($url, 'webpage'),
            'url' => $url,
            'name' => $page->metaTitle($locale),
            'description' => $page->seo($locale, 'meta_description'),
            'inLanguage' => $locale,
            'isPartOf' => ['@id' => static::siteId('website')],
            'about' => ['@id' => static::siteId('organization')],
            'datePublished' => $page->published_at?->toAtomString(),
            'dateModified' => ($page->updated_at ?? $page->published_at)?->toAtomString(),
            'primaryImageOfPage' => $image ? ['@id' => static::id($url, 'primaryimage')] : null,
        ]);

        if ($image) {
            $this->graph->add([
                '@type' => 'ImageObject',
                '@id' => static::id($url, 'primaryimage'),
                'url' => $image,
                'contentUrl' => $image,
            ]);
        }

        return $this;
    }

    /**
     * The thing the page is about, when it is about a thing.
     *
     * Linked from the WebPage through `mainEntity` rather than replacing it,
     * because a page describing a service is not itself a service. The name
     * and description come from the meta fields the client already filled in.
     */
    public function mainEntity(Page $page, string $locale, string $url): static
    {
        $type = $page->schemaType();

        if (PageTypes::isPageShaped($type)) {
            return $this;
        }

        $id = static::id($url, 'mainentity');
        $image = Media::url($page->seo($locale, 'og_image'));

        $node = [
            '@type' => $type,
            '@id' => $id,
            'name' => $page->metaTitle($locale),
            'description' => $page->seo($locale, 'meta_description'),
            'url' => $url,
            'image' => $image,
            ...$this->typeFields($page, $locale),
        ];

        $this->graph->add($node);

        // The link only makes sense once the node exists.
        if ($this->graph->has($id)) {
            $this->graph->add([
                '@type' => 'WebPage',
                '@id' => static::id($url, 'webpage'),
                'mainEntity' => ['@id' => $id],
            ]);
        }

        return $this;
    }

    /**
     * The handful of fields belonging to the chosen type.
     *
     * @return array<string, mixed>
     */
    protected function typeFields(Page $page, string $locale): array
    {
        $offer = Graph::node([
            '@type' => 'Offer',
            'price' => $page->schemaValue('price'),
            'priceCurrency' => $page->schemaValue('currency'),
            'availability' => ($availability = $page->schemaValue('availability'))
                ? 'https://schema.org/'.$availability
                : null,
            'itemCondition' => ($condition = $page->schemaValue('condition'))
                ? 'https://schema.org/'.$condition
                : null,
            'priceValidUntil' => $page->schemaValue('price_valid_until'),
            'url' => $page->url($locale),
        ]);

        return match ($page->schemaType()) {
            'Article' => [
                'headline' => $page->metaTitle($locale),
                'author' => ($author = $page->schemaValue('author'))
                    ? ['@type' => 'Person', 'name' => $author]
                    : ['@id' => static::siteId('organization')],
                'publisher' => ['@id' => static::siteId('organization')],
                'datePublished' => $page->schemaValue('published_at')
                    ?? $page->published_at?->toAtomString(),
                'dateModified' => $page->updated_at?->toAtomString(),
            ],

            'Service' => [
                'serviceType' => $page->schemaValue('service_type'),
                'provider' => ['@id' => static::siteId('organization')],
                'areaServed' => $this->places($page->schemaValue('area_served')),
                'offers' => $offer,
            ],

            'Product' => [
                'sku' => $page->schemaValue('sku'),
                'brand' => ($brand = $page->schemaValue('brand'))
                    ? ['@type' => 'Brand', 'name' => $brand]
                    : null,
                'offers' => $offer,
            ],

            'Event' => [
                'startDate' => $page->schemaValue('start'),
                'endDate' => $page->schemaValue('end'),
                // A cancelled event with no status keeps advertising itself.
                'eventStatus' => 'https://schema.org/'.$page->schemaValue('status', 'EventScheduled'),
                'eventAttendanceMode' => 'https://schema.org/'
                    .$page->schemaValue('attendance', 'OfflineEventAttendanceMode'),
                'location' => Graph::node([
                    '@type' => 'Place',
                    'name' => $page->schemaValue('location'),
                    'address' => $page->schemaValue('location_address'),
                ]),
                'organizer' => ['@id' => static::siteId('organization')],
                'offers' => $offer,
            ],

            'Person' => [
                'name' => $page->schemaValue('name') ?? $page->metaTitle($locale),
                'jobTitle' => $page->schemaValue('job_title'),
                'worksFor' => ['@id' => static::siteId('organization')],
                'sameAs' => $page->schemaValue('same_as'),
            ],

            default => [],
        };
    }

    /**
     * A trail typed by hand, for a page whose slug is not its hierarchy.
     *
     * A step with no URL is treated as the page itself, which is the normal
     * shape: the last crumb should point at where the visitor already is.
     */
    protected function customBreadcrumbs(Page $page, string $locale, string $url): static
    {
        $items = collect(data_get($page->schema, "breadcrumbs.items.{$locale}", []))
            ->filter(fn (mixed $item) => is_array($item) && filled($item['name'] ?? null))
            ->values()
            ->map(fn (array $item, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => (string) $item['name'],
                'item' => filled($item['url'] ?? null) ? (string) $item['url'] : $url,
            ])
            ->all();

        if ($items === []) {
            return $this;
        }

        $this->graph->add([
            '@type' => 'BreadcrumbList',
            '@id' => static::id($url, 'breadcrumb'),
            'itemListElement' => $items,
        ]);

        return $this;
    }

    /**
     * A comma separated list into Place nodes.
     *
     * @return array<int, array<string, string>>|null
     */
    protected function places(?string $list): ?array
    {
        $places = collect(explode(',', (string) $list))
            ->map(fn (string $place) => trim($place))
            ->filter()
            ->values();

        return $places->isEmpty()
            ? null
            : $places->map(fn (string $place) => ['@type' => 'Place', 'name' => $place])->all();
    }

    /**
     * Where the page sits, derived from the slug path.
     *
     * `services/web-design` is already a hierarchy, so no parent relationship
     * is needed. A single-segment slug gets no breadcrumb at all: Home → Page
     * is a trail nobody needed.
     */
    public function breadcrumbs(Page $page, string $locale, string $url): static
    {
        $mode = data_get($page->schema, 'breadcrumbs.mode', 'auto');

        if ($mode === 'none') {
            return $this;
        }

        if ($mode === 'custom') {
            return $this->customBreadcrumbs($page, $locale, $url);
        }

        $slug = $page->slug($locale);

        if ($slug === null || ! str_contains($slug, '/')) {
            return $this;
        }

        $default = array_key_first(config('atelier.locales', []));
        $prefix = $locale === $default ? '' : "{$locale}/";

        $items = [[
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => url($prefix),
        ]];

        $path = '';

        foreach (explode('/', $slug) as $segment) {
            $path = ltrim("{$path}/{$segment}", '/');
            $last = $path === $slug;

            $items[] = Graph::node([
                '@type' => 'ListItem',
                'position' => count($items) + 1,
                // The final crumb is this page, so it knows its own name.
                // The rest are path segments that may not be pages at all.
                'name' => $last ? $page->metaTitle($locale) : Str::headline($segment),
                'item' => $last ? $url : url($prefix.$path),
            ]) ?? [];
        }

        $this->graph->add([
            '@type' => 'BreadcrumbList',
            '@id' => static::id($url, 'breadcrumb'),
            'itemListElement' => $items,
        ]);

        return $this;
    }
}
