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

it('emits FAQ typed on the settings screen, with no FAQ block on the page', function () {
    $page = schemaPage();

    // The questions are answered in the page's prose, just not in an FAQ
    // block, so nothing derives them.
    $page->update(['schema' => ['faq' => ['en' => [
        ['question' => 'Where do you work?', 'answer' => 'Across the GCC, from Dubai.'],
        ['question' => 'Do you take retainers?', 'answer' => 'Yes.'],
    ]]]]);

    $faq = graphOf('/about')['FAQPage'];

    expect($faq['mainEntity'])->toHaveCount(2)
        ->and($faq['mainEntity'][0]['name'])->toBe('Where do you work?')
        ->and($faq['mainEntity'][1]['acceptedAnswer']['text'])->toBe('Yes.');
});

it('lets typed questions replace what a block would have derived', function () {
    $page = faqPage([['question' => 'From the block', 'answer' => 'Derived.']]);

    $page->update(['schema' => ['faq' => ['en' => [
        ['question' => 'Typed instead', 'answer' => 'Authored.'],
    ]]]]);

    $faq = graphOf('/help')['FAQPage'];

    // One node, and the typed one, rather than both sets listed together.
    expect($faq['mainEntity'])->toHaveCount(1)
        ->and($faq['mainEntity'][0]['name'])->toBe('Typed instead');
});

it('keeps typed FAQ per locale', function () {
    $page = schemaPage();

    $page->update(['schema' => ['faq' => [
        'en' => [['question' => 'In English?', 'answer' => 'Yes.']],
        'ar' => [['question' => 'بالعربية؟', 'answer' => 'نعم.']],
    ]]]);

    expect(graphOf('/about')['FAQPage']['mainEntity'][0]['name'])->toBe('In English?')
        ->and(graphOf('/ar/about-ar')['FAQPage']['mainEntity'][0]['name'])->toBe('بالعربية؟');
});

it('takes a breadcrumb trail typed by hand', function () {
    $page = schemaPage();

    $page->update(['schema' => ['breadcrumbs' => [
        'mode' => 'custom',
        'items' => ['en' => [
            ['name' => 'Home', 'url' => 'http://localhost:8000'],
            ['name' => 'Company', 'url' => 'http://localhost:8000/company'],
            // No URL on the last step, so it points at this page.
            ['name' => 'About us', 'url' => ''],
        ]],
    ]]]);

    $crumbs = graphOf('/about')['BreadcrumbList']['itemListElement'];

    expect($crumbs)->toHaveCount(3)
        ->and($crumbs[1]['name'])->toBe('Company')
        ->and($crumbs[2]['item'])->toBe('http://localhost:8000/about')
        ->and($crumbs[2]['position'])->toBe(3);
});

it('emits no trail when breadcrumbs are turned off', function () {
    $page = schemaPage('Web design', 'services/web-design');

    // The slug would otherwise produce one.
    $page->update(['schema' => ['breadcrumbs' => ['mode' => 'none']]]);

    expect(graphOf('/services/web-design'))->not->toHaveKey('BreadcrumbList');
});

it('still builds a trail from the slug when nothing is chosen', function () {
    schemaPage('Web design', 'services/web-design');

    expect(graphOf('/services/web-design')['BreadcrumbList']['itemListElement'])->toHaveCount(3);
});

it('emits opening hours, grouped the way a person thinks about them', function () {
    SiteSettings::current()->update(['data' => [
        'name' => ['en' => 'dsrpt'],
        'type' => 'Store',
        'opening_hours' => [
            ['days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday'], 'opens' => '09:00', 'closes' => '18:00'],
            ['days' => ['Saturday'], 'opens' => '10:00', 'closes' => '14:00'],
        ],
    ]]);

    schemaPage();
    $hours = graphOf('/about')['Store']['openingHoursSpecification'];

    expect($hours)->toHaveCount(2)
        ->and($hours[0]['@type'])->toBe('OpeningHoursSpecification')
        ->and($hours[0]['dayOfWeek'])->toBe(['Monday', 'Tuesday', 'Wednesday', 'Thursday'])
        ->and($hours[0]['opens'])->toBe('09:00')
        ->and($hours[1]['dayOfWeek'])->toBe(['Saturday']);
});

it('says who answers and in which language', function () {
    SiteSettings::current()->update(['data' => [
        'name' => ['en' => 'dsrpt'],
        'area_served' => 'Dubai, Abu Dhabi',
        'contact_points' => [
            ['type' => 'sales', 'telephone' => '+971 4 000 0000', 'languages' => 'Arabic, English'],
        ],
    ]]);

    schemaPage();
    $points = graphOf('/about')['Organization']['contactPoint'];

    expect($points)->toHaveCount(1)
        ->and($points[0]['contactType'])->toBe('sales')
        ->and($points[0]['availableLanguage'])->toBe(['Arabic', 'English'])
        ->and($points[0]['areaServed'])->toBe(['Dubai', 'Abu Dhabi']);
});

it('carries the legal details when they are filled in', function () {
    SiteSettings::current()->update(['data' => [
        'name' => ['en' => 'dsrpt'],
        'founding_date' => '2019-01-01',
        'employees' => '12',
        'vat_id' => '100123456700003',
    ]]);

    schemaPage();
    $org = graphOf('/about')['Organization'];

    expect($org['foundingDate'])->toBe('2019-01-01')
        ->and($org['numberOfEmployees'])->toBe('12')
        ->and($org['vatID'])->toBe('100123456700003')
        ->and($org)->not->toHaveKey('taxID');
});

it('completes a product offer with condition and a price end date', function () {
    $page = schemaPage();
    $page->update(['schema' => ['type' => 'Product', 'data' => [
        'price' => '99',
        'currency' => 'AED',
        'availability' => 'InStock',
        'condition' => 'NewCondition',
        'price_valid_until' => '2027-01-01',
    ]]]);

    $offer = graphOf('/about')['Product']['offers'];

    expect($offer['itemCondition'])->toBe('https://schema.org/NewCondition')
        ->and($offer['priceValidUntil'])->toBe('2027-01-01')
        ->and($offer['availability'])->toBe('https://schema.org/InStock');
});

it('defaults an event to going ahead, in person, and says so when it is not', function () {
    $page = schemaPage();
    $page->update(['schema' => ['type' => 'Event', 'data' => ['start' => '2026-09-01 18:00']]]);

    $event = graphOf('/about')['Event'];

    expect($event['eventStatus'])->toBe('https://schema.org/EventScheduled')
        ->and($event['eventAttendanceMode'])->toBe('https://schema.org/OfflineEventAttendanceMode');

    // A cancelled event with no status keeps advertising itself.
    $page->update(['schema' => ['type' => 'Event', 'data' => [
        'start' => '2026-09-01 18:00',
        'status' => 'EventCancelled',
        'attendance' => 'OnlineEventAttendanceMode',
    ]]]);

    expect(graphOf('/about')['Event']['eventStatus'])->toBe('https://schema.org/EventCancelled')
        ->and(graphOf('/about')['Event']['eventAttendanceMode'])->toBe('https://schema.org/OnlineEventAttendanceMode');
});

it('leaves hours and contact points out when nobody filled them in', function () {
    SiteSettings::current()->update(['data' => ['name' => ['en' => 'Bare']]]);

    schemaPage();
    $org = graphOf('/about')['Organization'];

    expect($org)->not->toHaveKey('openingHoursSpecification')
        ->and($org)->not->toHaveKey('contactPoint');
});

it('lists a collection page\'s children, in order, from the slug path', function () {
    $index = schemaPage('Services', 'services');
    $index->update(['schema' => ['type' => 'CollectionPage']]);

    schemaPage('Web design', 'services/web-design');
    schemaPage('Branding', 'services/branding');
    // A grandchild: a listing lists its own children, not its whole subtree.
    schemaPage('An audit', 'services/branding/audit');
    // And something that is not under it at all.
    schemaPage('Careers', 'careers');

    $graph = graphOf('/services');
    $items = $graph['ItemList']['itemListElement'];

    expect($items)->toHaveCount(2)
        // Ordered by title, so the list is stable rather than by insertion.
        ->and($items[0]['name'])->toBe('Branding')
        ->and($items[1]['name'])->toBe('Web design')
        ->and($items[1]['url'])->toBe('http://localhost:8000/services/web-design')
        ->and($graph['CollectionPage']['mainEntity']['@id'])->toBe($graph['ItemList']['@id']);
});

it('leaves a hidden child out of the list', function () {
    $index = schemaPage('Services', 'services');
    $index->update(['schema' => ['type' => 'CollectionPage']]);

    $child = schemaPage('Web design', 'services/web-design');
    $secret = schemaPage('Internal', 'services/internal');
    $secret->update(['seo' => ['en' => ['noindex' => true]]]);

    $items = graphOf('/services')['ItemList']['itemListElement'];

    expect($items)->toHaveCount(1)
        ->and($items[0]['name'])->toBe('Web design');
});

it('emits no list for a collection page with nothing under it', function () {
    $index = schemaPage('Services', 'services');
    $index->update(['schema' => ['type' => 'CollectionPage']]);

    expect(graphOf('/services'))->not->toHaveKey('ItemList');
});

it('describes a job vacancy', function () {
    $page = schemaPage('Senior Laravel developer', 'careers/laravel');
    $page->update(['schema' => ['type' => 'JobPosting', 'data' => [
        'valid_through' => '2026-12-31',
        'employment_type' => 'FULL_TIME',
        'salary' => '25000',
        'currency' => 'AED',
        'salary_unit' => 'MONTH',
        'location' => 'Dubai',
        'country' => 'AE',
    ]]]);

    $job = graphOf('/careers/laravel')['JobPosting'];

    expect($job['title'])->toBe('Senior Laravel developer')
        ->and($job['validThrough'])->toBe('2026-12-31')
        ->and($job['employmentType'])->toBe('FULL_TIME')
        ->and($job['hiringOrganization']['@id'])->toBe('http://localhost:8000#organization')
        ->and($job['jobLocation']['address']['addressLocality'])->toBe('Dubai')
        ->and($job['baseSalary']['currency'])->toBe('AED')
        ->and($job['baseSalary']['value']['value'])->toBe('25000')
        ->and($job['baseSalary']['value']['unitText'])->toBe('MONTH')
        // Dated from the publish date without anybody typing it.
        ->and($job['datePosted'])->not->toBeNull()
        ->and($job)->not->toHaveKey('jobLocationType');
});

it('marks a remote vacancy so it survives a remote search', function () {
    $page = schemaPage('Remote role', 'careers/remote');
    $page->update(['schema' => ['type' => 'JobPosting', 'data' => ['remote' => true]]]);

    expect(graphOf('/careers/remote')['JobPosting']['jobLocationType'])->toBe('TELECOMMUTE');
});

it('falls back to the site address for a vacancy with no location', function () {
    $page = schemaPage('Local role', 'careers/local');
    $page->update(['schema' => ['type' => 'JobPosting']]);

    $address = graphOf('/careers/local')['JobPosting']['jobLocation']['address'];

    expect($address['addressLocality'])->toBe('Dubai')
        ->and($address['addressCountry'])->toBe('AE');
});

it('gives a page Atelier does not own the same organisation graph', function () {
    // A host app route, its own view, no Atelier page behind it.
    $html = get('/blog/hello-world')->assertOk()->getContent();

    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
    $graph = collect(json_decode($m[1], true)['@graph'])->keyBy('@type');

    expect($graph['Article']['headline'])->toBe('Hello World')
        ->and($graph['Article']['author']['name'])->toBe('Abdulkader Safi')
        // The publisher is the same node the rest of the site points at, not a
        // second copy that drifts when a phone number changes.
        ->and($graph['Article']['publisher']['@id'])->toBe($graph['ProfessionalService']['@id'])
        ->and($graph['ProfessionalService']['telephone'])->toBe('+971 4 000 0000')
        ->and($graph['WebSite']['publisher']['@id'])->toBe($graph['ProfessionalService']['@id']);
});

it('escapes a host app node the same way it escapes its own', function () {
    $graph = \Safi\Atelier\Schema\StructuredData::for([[
        '@type' => 'Article',
        '@id' => 'https://example.com/post#article',
        'headline' => '</script><img src=x>',
    ]]);

    expect($graph->toJson())->not->toContain('</script>')
        ->and($graph->toJson())->toContain('\u003C/script\u003E');
});

it('drops the empties from a host app node too', function () {
    $graph = \Safi\Atelier\Schema\StructuredData::for([[
        '@type' => 'Article',
        '@id' => 'https://example.com/post#article',
        'headline' => 'Real',
        'description' => null,
        'author' => ['@type' => 'Person', 'name' => ''],
    ]]);

    $article = collect($graph->nodes())->firstWhere('@type', 'Article');

    expect($article)->toHaveKey('headline')
        ->and($article)->not->toHaveKey('description')
        // A Person with no name says nothing, so it goes rather than sitting
        // there as an empty node.
        ->and($article)->not->toHaveKey('author');
});
