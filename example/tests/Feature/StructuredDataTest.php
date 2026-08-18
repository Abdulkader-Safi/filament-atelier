<?php

declare(strict_types=1);

use Illuminate\Support\Facades\URL;
use Safi\Atelier\Models\Page;
use Safi\Atelier\Models\SiteSettings;
use Safi\Atelier\Schema\Graph;

use function Pest\Laravel\get;

function graphOf(string $url): array
{
    $html = get($url)->assertOk()->getContent();

    expect($html)->toContain('application/ld+json');

    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

    $decoded = json_decode($m[1] ?? '', true);

    expect($decoded)->toBeArray()
        ->and($decoded['@context'])->toBe('https://schema.org');

    return collect($decoded['@graph'])->keyBy('@type')->all();
}

function schemaPage(string $title = 'About', string $slug = 'about'): Page
{
    $page = Page::create(['title' => $title, 'draft_content' => [[
        'id' => 'b_one',
        'type' => 'hero',
        'attributes' => ['heading' => ['en' => "{$title} heading"]],
        'children' => [],
    ]]]);

    $page->setSlugs(['en' => $slug, 'ar' => "{$slug}-ar"]);
    $page->publish();

    return $page;
}

beforeEach(function () {
    SiteSettings::current()->update(['data' => [
        'name' => ['en' => 'dsrpt', 'ar' => 'دسرابت'],
        'description' => ['en' => 'We build websites.'],
        'type' => 'ProfessionalService',
        'telephone' => '+971 4 000 0000',
        'address' => ['locality' => 'Dubai', 'country' => 'AE'],
        'geo' => ['latitude' => '25.2048', 'longitude' => '55.2708'],
        'same_as' => ['https://www.linkedin.com/company/dsrpt'],
        'area_served' => 'Dubai, Abu Dhabi',
    ]]);
});

it('emits an organisation, a website and a page, linked by id', function () {
    schemaPage();

    $graph = graphOf('/about');

    expect($graph['ProfessionalService']['name'])->toBe('dsrpt')
        ->and($graph['ProfessionalService']['telephone'])->toBe('+971 4 000 0000')
        ->and($graph['ProfessionalService']['address']['addressLocality'])->toBe('Dubai')
        ->and($graph['ProfessionalService']['geo']['latitude'])->toBe(25.2048)
        ->and($graph['ProfessionalService']['areaServed'])->toHaveCount(2)
        ->and($graph['WebSite']['publisher']['@id'])->toBe($graph['ProfessionalService']['@id'])
        ->and($graph['WebPage']['isPartOf']['@id'])->toBe($graph['WebSite']['@id'])
        ->and($graph['WebPage']['url'])->toBe('http://localhost:8000/about')
        ->and($graph['WebPage']['inLanguage'])->toBe('en');
});

it('describes the same site in Arabic on the Arabic page', function () {
    schemaPage();

    $graph = graphOf('/ar/about-ar');

    expect($graph['ProfessionalService']['name'])->toBe('دسرابت')
        ->and($graph['WebPage']['inLanguage'])->toBe('ar')
        // The organisation is one node, so the Arabic page points at the same id.
        ->and($graph['ProfessionalService']['@id'])->toBe('http://localhost:8000#organization');
});

it('builds breadcrumbs from a nested slug, and none from a flat one', function () {
    schemaPage('Web design', 'services/web-design');

    $graph = graphOf('/services/web-design');
    $crumbs = $graph['BreadcrumbList']['itemListElement'];

    expect($crumbs)->toHaveCount(3)
        ->and($crumbs[0]['name'])->toBe('Home')
        ->and($crumbs[1]['name'])->toBe('Services')
        ->and($crumbs[1]['item'])->toBe('http://localhost:8000/services')
        ->and($crumbs[2]['name'])->toBe('Web design')
        ->and($crumbs[2]['position'])->toBe(3);

    // Home > Page is a trail nobody needed.
    schemaPage('Flat', 'flat');
    expect(graphOf('/flat'))->not->toHaveKey('BreadcrumbList');
});

it('leaves out what the client never filled in', function () {
    SiteSettings::current()->update(['data' => ['name' => ['en' => 'Bare']]]);

    schemaPage();
    $graph = graphOf('/about');

    expect($graph['Organization'])->not->toHaveKey('telephone')
        ->and($graph['Organization'])->not->toHaveKey('address')
        ->and($graph['Organization'])->not->toHaveKey('geo')
        ->and($graph['Organization']['name'])->toBe('Bare');
});

it('cannot be escaped out of by a client typing markup', function () {
    $page = schemaPage();
    $page->update(['seo' => ['en' => [
        'meta_title' => '</script><img src=x onerror=alert(1)>',
        'meta_description' => 'Tom & Jerry\'s "best" <b>bits</b>',
    ]]]);

    $html = get('/about')->assertOk()->getContent();

    // Every angle bracket inside the payload is hex-escaped, so there is no
    // `</script>` in the document for a parser to end the block on, and the
    // injected tag never becomes markup.
    expect($html)->not->toContain('</script><img')
        ->and($html)->toContain('\u003C/script\u003E')
        // Exactly one script block, the one we opened.
        ->and(substr_count($html, '</script>'))->toBe(1);

    $graph = graphOf('/about');

    // And it still decodes back to exactly what was typed.
    expect($graph['WebPage']['name'])->toBe('</script><img src=x onerror=alert(1)>')
        ->and($graph['WebPage']['description'])->toBe('Tom & Jerry\'s "best" <b>bits</b>');
});

it('emits nothing in the preview', function () {
    $page = schemaPage();

    $url = URL::signedRoute(
        'atelier.preview',
        ['page' => $page->getKey(), 'locale' => 'en'],
        absolute: false,
    );

    expect(get($url)->assertOk()->getContent())->not->toContain('application/ld+json');
});

it('merges two nodes sharing an id instead of duplicating them', function () {
    $graph = new Graph;

    $graph->add(['@type' => 'FAQPage', '@id' => '#faq', 'mainEntity' => [['@type' => 'Question', 'name' => 'One']]]);
    $graph->add(['@type' => 'FAQPage', '@id' => '#faq', 'mainEntity' => [['@type' => 'Question', 'name' => 'Two']]]);

    $nodes = $graph->nodes();

    expect($nodes)->toHaveCount(1)
        // The type stays a string rather than becoming a list of two.
        ->and($nodes[0]['@type'])->toBe('FAQPage')
        ->and($nodes[0]['mainEntity'])->toHaveCount(2);
});

it('keeps a bare id as a reference but drops one as a node', function () {
    $graph = new Graph;

    $graph->add(['@type' => 'WebPage', '@id' => '#page', 'about' => ['@id' => '#org']]);
    $graph->add(['@id' => '#nothing']);
    $graph->add(['@type' => 'Thing']);

    $nodes = $graph->nodes();

    expect($nodes)->toHaveCount(1)
        ->and($nodes[0]['about'])->toBe(['@id' => '#org']);
});

it('keeps a zero, which is a fact, and drops an empty string, which is not', function () {
    expect(Graph::prune(['a' => 0, 'b' => false, 'c' => '', 'd' => null, 'e' => []]))
        ->toBe(['a' => 0, 'b' => false]);
});
