<?php

declare(strict_types=1);

use App\Models\User;
use App\PageTypes\ServiceType;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Safi\Atelier\Blocks\CollectionBlock;
use Safi\Atelier\Filament\Pages\PageEditor;
use Safi\Atelier\Filament\Resources\PageResource\Pages\EditPageSettings;
use Safi\Atelier\Filament\Resources\PageResource\Pages\ListPages;
use Safi\Atelier\Models\Page;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(fn () => actingAs(User::factory()->create()));

/**
 * A published service, built the way the panel builds one rather than the way
 * a test would like to.
 */
function service(string $title, string $slug, array $data = []): Page
{
    $page = Page::create([
        'title' => $title,
        'type' => 'service',
        'data' => $data,
    ]);

    $page->setSlugs(['en' => $slug, 'ar' => "{$slug}-ar"], ['en' => 'services', 'ar' => 'خدمات']);
    $page->publish();

    return $page;
}

/**
 * Pretend the request arrived on that type's route.
 *
 * A real request gets both of these from middleware: SetUpPanel names the
 * panel, IdentifyResourceConfiguration names the type. A bare Livewire test
 * goes through no route, so it has to say both itself.
 */
function asType(?string $key): void
{
    Filament::setCurrentPanel('admin');
    Filament::setCurrentResourceConfigurationKey($key);
}

// The panel ----------------------------------------------------------------

it('lists a type under its own sidebar entry and nowhere else', function () {
    service('Web design', 'web-design');
    Page::create(['title' => 'About us'])->setSlugs(['en' => 'about']);

    get('/admin/services')->assertOk()->assertSee('Web design')->assertDontSee('About us');

    // Filament keeps the configuration key on its manager and a real request
    // arrives with a fresh container. Two requests in one test share one, so
    // the key from /admin/services has to be cleared by hand here.
    asType(null);

    get('/admin/pages')->assertOk()->assertSee('About us')->assertDontSee('Web design');
});

it('keeps a page whose type nobody registered visible under Pages', function () {
    // What a client is left with the day a developer deletes a type class.
    // The page still serves publicly, so it has to be editable somewhere.
    Page::create(['title' => 'Orphaned', 'type' => 'case-study'])->setSlugs(['en' => 'orphaned']);

    get('/admin/pages')->assertOk()->assertSee('Orphaned');
});

it('shows every registered type in the sidebar', function () {
    get('/admin/pages')
        ->assertOk()
        ->assertSee('Services')
        ->assertSee('Products')
        ->assertSee('Pages');
});

it('shows the type fields on the settings screen, and only that type', function () {
    $service = service('Web design', 'web-design');

    get("/admin/services/{$service->getKey()}/edit")
        ->assertOk()
        ->assertSee('Service details')
        ->assertSee('Short description')
        ->assertSee('Typical duration')
        ->assertSee('Pricing')
        // ProductType's fields belong to products.
        ->assertDontSee('SKU');
});

it('shows no type section on an ordinary page', function () {
    $page = Page::create(['title' => 'About']);
    $page->setSlugs(['en' => 'about']);

    get("/admin/pages/{$page->getKey()}/edit")
        ->assertOk()
        ->assertSee('Meta title')
        ->assertDontSee('Service details');
});

it('creates a page carrying the type, its template and its prefixed slug', function () {
    asType('service');

    Livewire::test(ListPages::class)
        ->callAction('create', data: ['title' => 'Brand strategy'])
        ->assertHasNoActionErrors();

    asType(null);

    $page = Page::where('title', 'Brand strategy')->firstOrFail();

    expect($page->type)->toBe('service')
        ->and($page->slug('en'))->toBe('services/brand-strategy')
        ->and($page->slug('ar'))->toBe('خدمات/brand-strategy')
        // The type's four starter sections, each with the block's own defaults
        // and an id the editor can track.
        ->and(collect($page->draft())->pluck('type')->all())->toBe(ServiceType::template() ? array_column(ServiceType::template(), 'type') : [])
        ->and($page->draft()[0]['id'])->toStartWith('b_')
        ->and($page->draft()[0]['attributes'])->not->toBe([]);
});

it('leaves a slug that already carries the prefix alone', function () {
    asType('service');

    Livewire::test(ListPages::class)
        ->callAction('create', data: ['title' => 'Services', 'slugs' => ['en' => 'services', 'ar' => 'خدمات']])
        ->assertHasNoActionErrors();

    asType(null);

    expect(Page::where('title', 'Services')->firstOrFail()->slug('en'))->toBe('services');
});

it('creates an ordinary page with no type and no prefix', function () {
    Livewire::test(ListPages::class)
        ->callAction('create', data: ['title' => 'Plain page'])
        ->assertHasNoActionErrors();

    $page = Page::where('title', 'Plain page')->firstOrFail();

    expect($page->type)->toBe('page')
        ->and($page->slug('en'))->toBe('plain-page')
        ->and($page->draft())->toBe([]);
});

it('offers only the blocks the type allows in the section picker', function () {
    $service = service('Web design', 'web-design');

    $picker = collect(Livewire::test(PageEditor::class, ['record' => $service->getKey()])
        ->instance()
        ->picker)
        ->flatten(1)
        ->pluck('type');

    expect($picker)->toContain('hero')
        // A service is what gets listed, so ServiceType leaves the listing
        // block out of its picker.
        ->not->toContain('collection');
});

it('offers every block on a page with no type', function () {
    $page = Page::create(['title' => 'Plain']);

    $picker = collect(Livewire::test(PageEditor::class, ['record' => $page->getKey()])
        ->instance()
        ->picker)
        ->flatten(1)
        ->pluck('type');

    expect($picker)->toContain('collection')->toContain('cta');
});

// Custom properties ---------------------------------------------------------

it('reads an untranslated property once and a translated one per locale', function () {
    $service = service('Web design', 'web-design', [
        'duration' => '2 hours',
        'en' => ['excerpt' => 'Sites that load fast.'],
        'ar' => ['excerpt' => 'مواقع سريعة.'],
    ]);

    expect($service->data('duration'))->toBe('2 hours')
        ->and($service->data('excerpt', 'en'))->toBe('Sites that load fast.')
        ->and($service->data('excerpt', 'ar'))->toBe('مواقع سريعة.');
});

it('falls back to the first locale for a property that was never translated', function () {
    $service = service('Web design', 'web-design', ['en' => ['excerpt' => 'Only English.']]);

    expect($service->data('excerpt', 'ar'))->toBe('Only English.')
        ->and($service->data('nothing_here', 'ar', 'fallback'))->toBe('fallback');
});

it('does not unwrap a locale map on a page with no type', function () {
    $page = Page::create(['title' => 'Plain', 'data' => ['en' => ['excerpt' => 'Ignored']]]);

    // Nothing declares `excerpt` translatable here, so the key is looked for
    // at the top level, where it is not. Reading it as English would be
    // guessing that any two-letter key is a locale.
    expect($page->data('excerpt'))->toBeNull()
        ->and($page->data('en'))->toBe(['excerpt' => 'Ignored']);
});

it('saves the type fields from the settings screen in the shape data() reads', function () {
    $service = service('Web design', 'web-design');

    asType('service');

    Livewire::test(EditPageSettings::class, [
        'record' => $service->getKey(),
    ])
        ->fillForm([
            'data' => [
                'duration' => '4 to 6 hours',
                'en' => ['excerpt' => 'English excerpt'],
                'ar' => ['excerpt' => 'ملخص عربي'],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    asType(null);

    $service->refresh();

    expect($service->data('duration'))->toBe('4 to 6 hours')
        ->and($service->data('excerpt', 'ar'))->toBe('ملخص عربي');
});

// The Collection block -------------------------------------------------------

it('lists a type on a public page, linking each card to its own URL', function () {
    service('Web design', 'web-design', ['en' => ['excerpt' => 'Sites that load fast.']]);
    service('Branding', 'branding');

    // Draft, so it must not appear.
    Page::create(['title' => 'Unfinished', 'type' => 'service'])->setSlugs(['en' => 'unfinished']);

    $home = Page::create(['title' => 'Home', 'draft_content' => [[
        'id' => 'b_list',
        'type' => 'collection',
        'attributes' => ['page_type' => 'service', 'heading' => ['en' => 'What we do']],
        'children' => [],
    ]]]);
    $home->setSlugs(['en' => 'home']);
    $home->publish();

    get('/')
        ->assertOk()
        ->assertSee('What we do')
        ->assertSee('Web design')
        ->assertSee('Sites that load fast.')
        ->assertSee('/services/web-design')
        ->assertDontSee('Unfinished');
});

it('lists the Arabic side with Arabic URLs', function () {
    service('Web design', 'web-design', ['ar' => ['excerpt' => 'مواقع سريعة.']]);

    $home = Page::create(['title' => 'Home', 'draft_content' => [[
        'id' => 'b_list',
        'type' => 'collection',
        'attributes' => ['page_type' => 'service'],
        'children' => [],
    ]]]);
    $home->setSlugs(['en' => 'home', 'ar' => 'home-ar']);
    $home->publish();

    get('/ar/home-ar')
        ->assertOk()
        ->assertSee('مواقع سريعة.')
        ->assertSee('/ar/خدمات/web-design-ar');
});

it('lists only the hand-picked ones, in the order they were dragged', function () {
    $design = service('Web design', 'web-design');
    $branding = service('Brand identity', 'brand-identity');
    service('Content strategy', 'content-strategy');

    $home = Page::create(['title' => 'Home', 'draft_content' => [[
        'id' => 'b_list',
        'type' => 'collection',
        'attributes' => [
            'page_type' => 'service',
            'source' => 'picked',
            // Branding first, though it sorts second by title.
            'pages' => [(string) $branding->getKey(), (string) $design->getKey()],
        ],
        'children' => [],
    ]]]);
    $home->setSlugs(['en' => 'home']);
    $home->publish();

    $html = get('/')->assertOk()->assertDontSee('Content strategy')->getContent();

    expect(strpos($html, 'Brand identity'))->toBeLessThan(strpos($html, 'Web design'));
});

it('drops a pick that is unpublished, deleted or of another type', function () {
    $design = service('Web design', 'web-design');
    $draft = Page::create(['title' => 'Unfinished', 'type' => 'service']);
    $product = Page::create(['title' => 'A product', 'type' => 'product', 'status' => 'published']);

    $home = Page::create(['title' => 'Home', 'draft_content' => [[
        'id' => 'b_list',
        'type' => 'collection',
        'attributes' => [
            'page_type' => 'service',
            'source' => 'picked',
            'pages' => [$design->getKey(), $draft->getKey(), $product->getKey(), 9999],
        ],
        'children' => [],
    ]]]);
    $home->setSlugs(['en' => 'home']);
    $home->publish();

    get('/')
        ->assertOk()
        ->assertSee('Web design')
        ->assertDontSee('Unfinished')
        ->assertDontSee('A product');
});

it('offers published pages of the chosen type to pick from', function () {
    service('Web design', 'web-design');
    Page::create(['title' => 'Unfinished', 'type' => 'service']);
    Page::create(['title' => 'A product', 'type' => 'product', 'status' => 'published']);

    expect(CollectionBlock::choices('service'))
        ->toBe([1 => 'Web design'])
        // With no type resolved, everything typed, labelled by its type.
        ->and(array_values(CollectionBlock::choices(null)))
        ->toBe(['A product (Product)', 'Web design (Service)']);
});

it('narrows the picker in the editor to the chosen type', function () {
    $design = service('Web design', 'web-design');
    Page::create(['title' => 'A product', 'type' => 'product', 'status' => 'published'])
        ->setSlugs(['en' => 'products/a']);

    $home = Page::create(['title' => 'Home', 'draft_content' => [[
        'id' => 'b_list',
        'type' => 'collection',
        'attributes' => [
            'page_type' => 'service',
            'source' => 'picked',
            'pages' => [(string) $design->getKey()],
        ],
        'children' => [],
    ]]]);
    $home->setSlugs(['en' => 'home']);

    $html = Livewire::test(PageEditor::class, ['record' => $home->getKey()])
        ->call('selectBlock', 'b_list')
        ->html();

    // The picker sits inside a repeater item and reads the block's own type
    // select through a relative path. Getting that path wrong is silent: the
    // options fall back to every typed page, labelled with its type. So the
    // absence of that label is the assertion.
    // The type suffix is the tell: choices() only adds it when it could not
    // resolve which type the block is listing. The editor's Pages panel names
    // every page on the site, so the bare titles prove nothing on their own.
    expect($html)->toContain('Web design')
        ->not->toContain('Web design (Service)')
        ->not->toContain('A product (Product)')
        // Hand-picked has its own order, so the order select is not offered.
        ->not->toContain('Title, A to Z');
});

it('says so rather than rendering an empty grid', function () {
    $home = Page::create(['title' => 'Home', 'draft_content' => [[
        'id' => 'b_list',
        'type' => 'collection',
        'attributes' => ['page_type' => 'service', 'empty' => ['en' => 'No services yet.']],
        'children' => [],
    ]]]);
    $home->setSlugs(['en' => 'home']);
    $home->publish();

    get('/')->assertOk()->assertSee('No services yet.');
});

it('limits and orders what it lists', function () {
    service('Branding', 'branding');
    service('Analytics', 'analytics');
    service('Web design', 'web-design');

    $home = Page::create(['title' => 'Home', 'draft_content' => [[
        'id' => 'b_list',
        'type' => 'collection',
        'attributes' => ['page_type' => 'service', 'order' => 'title', 'limit' => 2],
        'children' => [],
    ]]]);
    $home->setSlugs(['en' => 'home']);
    $home->publish();

    get('/')->assertOk()->assertSee('Analytics')->assertSee('Branding')->assertDontSee('Web design');
});

// The index route ------------------------------------------------------------

it('serves the type index at its prefix, in both locales', function () {
    service('Web design', 'web-design');

    get('/services')->assertOk()->assertSee('Services')->assertSee('Web design');

    // Percent-encoded, the way a browser sends it. The test client rejects a
    // raw multi-byte path with a 400 before the router ever sees it.
    get('/ar/'.rawurlencode('خدمات'))->assertOk()->assertSee('Web design');
});

it('hands over to a real page built at the same slug', function () {
    service('Web design', 'web-design');

    $landing = Page::create(['title' => 'Our services', 'draft_content' => [[
        'id' => 'b_one',
        'type' => 'hero',
        'attributes' => ['heading' => ['en' => 'Handmade services page']],
        'children' => [],
    ]]]);
    $landing->setSlugs(['en' => 'services']);
    $landing->publish();

    get('/services')->assertOk()->assertSee('Handmade services page');
});

it('keeps serving the index while a page at the same slug is still a draft', function () {
    service('Web design', 'web-design');

    Page::create(['title' => 'Half-written'])->setSlugs(['en' => 'services']);

    get('/services')->assertOk()->assertSee('Web design')->assertDontSee('Half-written');
});

it('registers no index for a type that declares none', function () {
    // ProductType has a prefix but no index view.
    get('/products')->assertNotFound();
});

it('lists the index in the sitemap once the type has something published', function () {
    expect(get('/sitemap.xml')->getContent())->not->toContain('<loc>http://localhost:8000/services</loc>');

    service('Web design', 'web-design');

    expect(get('/sitemap.xml')->getContent())
        ->toContain('<loc>http://localhost:8000/services</loc>')
        ->toContain('<loc>http://localhost:8000/services/web-design</loc>');
});

it('describes itself the way the panel labels it', function () {
    expect(ServiceType::pluralLabel())->toBe('Services')
        ->and(ServiceType::slug())->toBe('services');
});
