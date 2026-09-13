<?php

declare(strict_types=1);

use App\Models\Enquiry;
use App\Models\User;
use Safi\Atelier\Models\Page;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

/** A published service with two tiers, one of them the suggested one. */
function servicePage(): Page
{
    $page = Page::create([
        'title' => 'Home deep clean',
        'type' => 'service',
        'data' => [
            'tiers' => [
                ['name' => 'Studio', 'price' => 450, 'unit' => 'visit', 'recommended' => false],
                ['name' => 'Villa', 'price' => 1400, 'unit' => 'visit', 'recommended' => true],
            ],
        ],
    ]);

    $page->setSlugs(['en' => 'home-deep-clean'], ['en' => 'services']);
    $page->publish();

    return $page;
}

function productPage(): Page
{
    $page = Page::create([
        'title' => 'Steam mop',
        'type' => 'product',
        'data' => [
            'price' => 420,
            'variations' => [
                ['name' => 'Standard', 'price' => 420, 'in_stock' => true],
                ['name' => 'With carpet glider', 'price' => 495, 'in_stock' => true],
            ],
        ],
    ]);

    $page->setSlugs(['en' => 'steam-mop'], ['en' => 'products']);
    $page->publish();

    return $page;
}

it('takes a service request and prices it from the page', function () {
    $service = servicePage();

    post('/request', [
        'page_id' => $service->getKey(),
        'option' => 'Villa',
        'name' => 'Hannah',
        'email' => 'hannah@example.test',
        'phone' => '050 000 0000',
        'message' => 'Two floors, end of the month.',
    ])->assertRedirect();

    $enquiry = Enquiry::firstOrFail();

    expect($enquiry->kind)->toBe('service')
        ->and($enquiry->page_title)->toBe('Home deep clean')
        ->and($enquiry->option)->toBe('Villa')
        ->and((float) $enquiry->unit_price)->toBe(1400.0)
        ->and((float) $enquiry->total)->toBe(1400.0)
        ->and($enquiry->quantity)->toBe(1)
        ->and($enquiry->status)->toBe('new');
});

it('multiplies a product order by its quantity', function () {
    $product = productPage();

    post('/request', [
        'page_id' => $product->getKey(),
        'option' => 'With carpet glider',
        'quantity' => 3,
        'name' => 'Omar',
        'email' => 'omar@example.test',
    ])->assertRedirect();

    $enquiry = Enquiry::firstOrFail();

    expect($enquiry->kind)->toBe('product')
        ->and((float) $enquiry->unit_price)->toBe(495.0)
        ->and((float) $enquiry->total)->toBe(1485.0);
});

it('ignores a price sent from the browser', function () {
    $service = servicePage();

    // A hidden input is a suggestion, and this one says the Villa costs 1.
    post('/request', [
        'page_id' => $service->getKey(),
        'option' => 'Villa',
        'unit_price' => 1,
        'total' => 1,
        'name' => 'Chancer',
        'email' => 'chancer@example.test',
    ])->assertRedirect();

    expect((float) Enquiry::firstOrFail()->total)->toBe(1400.0);
});

it('drops an option the page does not offer', function () {
    $service = servicePage();

    post('/request', [
        'page_id' => $service->getKey(),
        'option' => 'Palace',
        'name' => 'Chancer',
        'email' => 'chancer@example.test',
    ])->assertRedirect();

    $enquiry = Enquiry::firstOrFail();

    expect($enquiry->option)->toBeNull()
        ->and($enquiry->total)->toBeNull();
});

it('refuses a request for a draft page', function () {
    $service = servicePage();
    $service->unpublish();

    post('/request', [
        'page_id' => $service->getKey(),
        'name' => 'Hannah',
        'email' => 'hannah@example.test',
    ])->assertNotFound();

    expect(Enquiry::count())->toBe(0);
});

it('refuses a bot that fills the hidden field', function () {
    $service = servicePage();

    post('/request', [
        'page_id' => $service->getKey(),
        'name' => 'Bot',
        'email' => 'bot@example.test',
        'website' => 'http://spam.test',
    ])->assertSessionHasErrors('website');

    expect(Enquiry::count())->toBe(0);
});

it('needs a name and a real email', function () {
    $service = servicePage();

    post('/request', ['page_id' => $service->getKey(), 'email' => 'not-an-email'])
        ->assertSessionHasErrors(['name', 'email']);

    expect(Enquiry::count())->toBe(0);
});

it('shows the request in the panel, under the right tab', function () {
    actingAs(User::factory()->create());

    $service = servicePage();

    post('/request', [
        'page_id' => $service->getKey(),
        'option' => 'Villa',
        'name' => 'Hannah',
        'email' => 'hannah@example.test',
    ]);

    get('/admin/requests')
        ->assertOk()
        ->assertSee('Home deep clean')
        ->assertSee('Hannah')
        ->assertSee('Villa');
});

it('renders the form on a service page with its tiers', function () {
    $service = servicePage();

    $home = Page::create(['title' => 'Home']);
    $home->setSlugs(['en' => 'home']);
    $home->publish();

    // The block reads the page it is rendered on, so the same block on the
    // service shows tiers and on the homepage shows none.
    $service->update(['draft_content' => [[
        'id' => 'b_form',
        'type' => 'request-form',
        'attributes' => ['heading' => ['en' => 'Book it']],
        'children' => [],
    ]]]);
    $service->publish();

    get('/services/home-deep-clean')
        ->assertOk()
        ->assertSee('Book it')
        ->assertSee('Which package')
        ->assertSee('Villa')
        ->assertSee('Studio');
});
