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

it('refines the WebPage for a page-shaped type', function () {
    $page = schemaPage();
    $page->update(['schema' => ['type' => 'ContactPage']]);

    $graph = graphOf('/about');

    // An About page is a WebPage, so the type is refined rather than doubled.
    expect($graph)->toHaveKey('ContactPage')
        ->and($graph)->not->toHaveKey('WebPage')
        ->and($graph['ContactPage']['isPartOf']['@id'])->toBe('http://localhost:8000#website');
});

it('gives a thing-shaped type its own node, linked from the page', function () {
    $page = schemaPage();
    $page->update([
        'seo' => ['en' => ['meta_title' => 'Web design', 'meta_description' => 'We design websites.']],
        'schema' => ['type' => 'Service', 'data' => [
            'service_type' => 'Web design',
            'area_served' => 'Dubai, Abu Dhabi',
            'price' => '5000',
            'currency' => 'AED',
        ]],
    ]);

    $graph = graphOf('/about');

    // The page is still a WebPage. It is *about* a service.
    expect($graph)->toHaveKey('WebPage')
        ->and($graph['WebPage']['mainEntity']['@id'])->toBe($graph['Service']['@id'])
        ->and($graph['Service']['name'])->toBe('Web design')
        ->and($graph['Service']['serviceType'])->toBe('Web design')
        ->and($graph['Service']['provider']['@id'])->toBe('http://localhost:8000#organization')
        ->and($graph['Service']['areaServed'])->toHaveCount(2)
        ->and($graph['Service']['offers']['price'])->toBe('5000')
        ->and($graph['Service']['offers']['priceCurrency'])->toBe('AED');
});

it('builds a product offer with a schema.org availability url', function () {
    $page = schemaPage();
    $page->update(['schema' => ['type' => 'Product', 'data' => [
        'sku' => 'ABC-1',
        'brand' => 'dsrpt',
        'price' => '99',
        'currency' => 'AED',
        'availability' => 'InStock',
    ]]]);

    $graph = graphOf('/about');

    expect($graph['Product']['sku'])->toBe('ABC-1')
        ->and($graph['Product']['brand'])->toBe(['@type' => 'Brand', 'name' => 'dsrpt'])
        ->and($graph['Product']['offers']['availability'])->toBe('https://schema.org/InStock');
});

it('credits the organisation when an article names no author', function () {
    $page = schemaPage();
    $page->update(['schema' => ['type' => 'Article']]);

    $graph = graphOf('/about');

    expect($graph['Article']['author']['@id'])->toBe('http://localhost:8000#organization')
        ->and($graph['Article']['publisher']['@id'])->toBe('http://localhost:8000#organization')
        ->and($graph['Article']['datePublished'])->not->toBeNull();

    $page->update(['schema' => ['type' => 'Article', 'data' => ['author' => 'Abdulkader Safi']]]);

    expect(graphOf('/about')['Article']['author'])
        ->toBe(['@type' => 'Person', 'name' => 'Abdulkader Safi']);
});

it('emits a plain WebPage when the client picks nothing', function () {
    schemaPage();

    $graph = graphOf('/about');

    expect($graph)->toHaveKey('WebPage')
        ->and($graph['WebPage'])->not->toHaveKey('mainEntity')
        ->and(array_keys($graph))->not->toContain('Service', 'Product', 'Article');
});

it('keeps the type when the page is translated', function () {
    $page = schemaPage();
    $page->update(['schema' => ['type' => 'Service', 'data' => ['service_type' => 'Web design']]]);

    // The type is one fact about the page, so Arabic says the same thing.
    expect(graphOf('/ar/about-ar'))->toHaveKey('Service');
});

function faqPage(array $items, ?array $second = null): Page
{
    $tree = [[
        'id' => 'b_faq',
        'type' => 'faq',
        'attributes' => ['heading' => ['en' => 'Questions'], 'items' => ['en' => $items]],
        'children' => [],
    ]];

    if ($second !== null) {
        $tree[] = [
            'id' => 'b_faq_two',
            'type' => 'faq',
            'attributes' => ['heading' => ['en' => 'More'], 'items' => ['en' => $second]],
            'children' => [],
        ];
    }

    $page = Page::create(['title' => 'Help', 'draft_content' => $tree]);
    $page->setSlugs(['en' => 'help', 'ar' => 'help-ar']);
    $page->publish();

    return $page;
}

it('generates FAQ schema from what the client typed into the block', function () {
    faqPage([
        ['question' => 'How long does it take?', 'answer' => 'Weeks, not months.'],
        ['question' => 'Do you host it?', 'answer' => 'We can.'],
    ]);

    $graph = graphOf('/help');
    $faq = $graph['FAQPage'];

    expect($faq['mainEntity'])->toHaveCount(2)
        ->and($faq['mainEntity'][0]['@type'])->toBe('Question')
        ->and($faq['mainEntity'][0]['name'])->toBe('How long does it take?')
        ->and($faq['mainEntity'][0]['acceptedAnswer']['text'])->toBe('Weeks, not months.')
        ->and($faq['inLanguage'])->toBe('en');
});

it('merges two FAQ blocks into one FAQPage', function () {
    faqPage(
        [['question' => 'First?', 'answer' => 'Yes.']],
        [['question' => 'Second?', 'answer' => 'Also yes.']],
    );

    $decoded = json_decode(
        preg_replace('#.*<script type="application/ld\+json">(.*?)</script>.*#s', '$1', get('/help')->getContent()),
        true,
    );

    $faqNodes = collect($decoded['@graph'])->where('@type', 'FAQPage');

    // One node, both questions. Two FAQPage nodes on a page is a coin toss
    // over which one gets read.
    expect($faqNodes)->toHaveCount(1)
        ->and($faqNodes->first()['mainEntity'])->toHaveCount(2);
});

it('drops a question with no answer', function () {
    faqPage([
        ['question' => 'Answered?', 'answer' => 'Yes.'],
        ['question' => 'Unanswered?', 'answer' => ''],
        ['question' => '', 'answer' => 'Orphan answer.'],
    ]);

    expect(graphOf('/help')['FAQPage']['mainEntity'])->toHaveCount(1);
});

it('says nothing when the FAQ block is empty', function () {
    faqPage([]);

    expect(graphOf('/help'))->not->toHaveKey('FAQPage');
});

it('leaves a hidden FAQ block out of the schema', function () {
    $page = faqPage([['question' => 'Shown?', 'answer' => 'No.']]);

    $tree = $page->draft();
    $tree[0]['hidden'] = true;
    $page->update(['draft_content' => $tree]);
    $page->publish();

    // The section is not on the page, so claiming it in the schema would
    // describe something a visitor cannot see.
    expect(graphOf('/help'))->not->toHaveKey('FAQPage');
});

it('describes the FAQ in Arabic on the Arabic page', function () {
    $page = faqPage([['question' => 'English?', 'answer' => 'Yes.']]);

    $tree = $page->draft();
    $tree[0]['attributes']['items']['ar'] = [['question' => 'بالعربية؟', 'answer' => 'نعم.']];
    $page->update(['draft_content' => $tree]);
    $page->publish();

    $faq = graphOf('/ar/help-ar')['FAQPage'];

    expect($faq['inLanguage'])->toBe('ar')
        ->and($faq['mainEntity'][0]['name'])->toBe('بالعربية؟')
        ->and($faq['mainEntity'][0]['acceptedAnswer']['text'])->toBe('نعم.');
});
