<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Safi\Atelier\Filament\Pages\PageEditor;
use Safi\Atelier\Models\Page;
use Safi\Atelier\PageResolver;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(fn () => actingAs(User::factory()->create()));

function editorPage(string $title, string $slug, array $tree = [], bool $publish = true): Page
{
    $page = Page::create(['title' => $title, 'draft_content' => $tree]);
    $page->setSlugs(['en' => $slug, 'ar' => "{$slug}-ar"]);

    if ($publish) {
        $page->publish();
    }

    return $page;
}

// Resolving a URL ------------------------------------------------------------

it('resolves a path to the page it names', function () {
    $about = editorPage('About', 'about');

    $resolved = app(PageResolver::class)->forPath('/about');

    expect($resolved['page']->is($about))->toBeTrue()
        ->and($resolved['locale'])->toBe('en');
});

it('keeps every segment of a nested path in the slug', function () {
    $service = Page::create(['title' => 'Web design', 'type' => 'service']);
    $service->setSlugs(['en' => 'web-design'], ['en' => 'services']);
    $service->publish();

    // The rule that once broke: `services` is not a locale, so it stays part
    // of the slug rather than being read as a language.
    expect(app(PageResolver::class)->forPath('/services/web-design')['page']->is($service))->toBeTrue();
});

it('reads the first segment as a locale only when it names one', function () {
    $about = editorPage('About', 'about');

    $resolved = app(PageResolver::class)->forPath('/ar/about-ar');

    expect($resolved['page']->is($about))->toBeTrue()
        ->and($resolved['locale'])->toBe('ar');
});

it('treats an empty path as the home page', function () {
    $home = editorPage('Home', 'home');

    expect(app(PageResolver::class)->forPath('/')['page']->is($home))->toBeTrue();
});

it('resolves a draft, which the public route would refuse', function () {
    $draft = editorPage('Unfinished', 'unfinished', publish: false);

    expect(app(PageResolver::class)->forPath('/unfinished')['page']->is($draft))->toBeTrue();

    get('/unfinished')->assertNotFound();
});

it('refuses a URL on another host, and anything that is not http', function () {
    editorPage('About', 'about');

    $resolver = app(PageResolver::class);

    expect($resolver->forUrl('https://example.com/about'))->toBeNull()
        ->and($resolver->forUrl('mailto:hello@example.test'))->toBeNull()
        ->and($resolver->forUrl(config('app.url').'/about'))->not->toBeNull();
});

// The Pages panel -------------------------------------------------------------

it('lists every page in the editor, grouped by type, with the current one flagged', function () {
    $home = editorPage('Home', 'home');
    editorPage('About us', 'about');

    $service = Page::create(['title' => 'Deep clean', 'type' => 'service']);
    $service->setSlugs(['en' => 'deep-clean'], ['en' => 'services']);
    $service->publish();

    $pages = Livewire::test(PageEditor::class, ['record' => $home->getKey()])->instance()->pages;

    // No products exist, so no Products heading: an empty group is a heading
    // over nothing.
    expect(array_keys($pages))->toBe(['Pages', 'Services'])
        ->and(collect($pages['Pages'])->pluck('title')->all())->toBe(['About us', 'Home'])
        ->and(collect($pages['Pages'])->firstWhere('title', 'Home')['current'])->toBeTrue()
        ->and(collect($pages['Pages'])->firstWhere('title', 'About us')['current'])->toBeFalse()
        ->and($pages['Services'][0]['title'])->toBe('Deep clean');
});

it('links each page to its own builder', function () {
    $home = editorPage('Home', 'home');
    $about = editorPage('About us', 'about');

    get("/admin/atelier/{$home->getKey()}")
        ->assertOk()
        ->assertSee('About us')
        ->assertSee("/admin/atelier/{$about->getKey()}");
});

// Following a link in the preview ----------------------------------------------

it('opens the page a preview link points at', function () {
    $home = editorPage('Home', 'home');
    $about = editorPage('About us', 'about');

    Livewire::test(PageEditor::class, ['record' => $home->getKey()])
        ->call('openPath', config('app.url').'/about')
        ->assertRedirect("/admin/atelier/{$about->getKey()}");
});

it('hands a link it cannot edit back to the browser instead', function () {
    $home = editorPage('Home', 'home');

    Livewire::test(PageEditor::class, ['record' => $home->getKey()])
        ->call('openPath', 'https://example.com/pricing')
        ->assertDispatched('atelier-open-tab', href: 'https://example.com/pricing')
        ->assertNoRedirect();
});

it('injects the editor script into the preview, whatever layout the page uses', function () {
    $page = editorPage('Docs', 'docs', [[
        'id' => 'b_one',
        'type' => 'hero',
        'attributes' => ['heading' => ['en' => 'A heading']],
        'children' => [],
    ]]);

    // A layout in the host app that knows nothing about the editor.
    $page->update(['layout' => 'docs']);

    get(URL::signedRoute('atelier.preview', ['page' => $page->getKey(), 'locale' => 'en'], absolute: false))
        ->assertOk()
        ->assertSee('data-atelier-preview', escape: false)
        ->assertSee("type: 'navigate'", escape: false);
});

it('keeps the editor script off the public page', function () {
    editorPage('Home', 'home');

    get('/')->assertOk()->assertDontSee('data-atelier-preview', escape: false);
});

// Reordering --------------------------------------------------------------------

it('reorders sections from a single drag', function () {
    $page = editorPage('Home', 'home', [
        ['id' => 'b_one', 'type' => 'hero', 'attributes' => [], 'children' => []],
        ['id' => 'b_two', 'type' => 'features', 'attributes' => [], 'children' => []],
        ['id' => 'b_three', 'type' => 'cta', 'attributes' => [], 'children' => []],
    ]);

    Livewire::test(PageEditor::class, ['record' => $page->getKey()])
        ->call('reorder', ['b_three', 'b_one', 'b_two']);

    expect(collect($page->fresh()->draft())->pluck('id')->all())->toBe(['b_three', 'b_one', 'b_two']);
});

it('ignores an order that is not the sections it already has', function () {
    $tree = [
        ['id' => 'b_one', 'type' => 'hero', 'attributes' => [], 'children' => []],
        ['id' => 'b_two', 'type' => 'features', 'attributes' => [], 'children' => []],
    ];

    $page = editorPage('Home', 'home', $tree);

    $component = Livewire::test(PageEditor::class, ['record' => $page->getKey()]);

    // A drag resolved against a stale list: one id dropped, one invented,
    // one duplicated. None of them may write.
    $component->call('reorder', ['b_one']);
    $component->call('reorder', ['b_one', 'b_nine']);
    $component->call('reorder', ['b_one', 'b_one']);

    expect(collect($page->fresh()->draft())->pluck('id')->all())->toBe(['b_one', 'b_two']);
});
