<?php

declare(strict_types=1);

namespace Safi\Atelier\Schema;

use Illuminate\Support\Str;
use Safi\Atelier\Media;
use Safi\Atelier\Models\Page;
use Safi\Atelier\Models\SiteSettings;

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
    public function __construct(protected Graph $graph = new Graph) {}

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
            ->mainEntity($page, $locale, $url);

        return $this->graph;
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
