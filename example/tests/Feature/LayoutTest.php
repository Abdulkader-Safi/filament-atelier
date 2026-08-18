<?php

declare(strict_types=1);

use Illuminate\Support\Facades\URL;
use Safi\Atelier\LayoutRegistry;
use Safi\Atelier\Models\Page;

use function Pest\Laravel\get;

function layoutPage(?string $layout = null): Page
{
    $page = Page::create(['title' => 'About', 'layout' => $layout, 'draft_content' => [[
        'id' => 'b_one',
        'type' => 'hero',
        'attributes' => ['heading' => ['en' => 'About heading']],
        'children' => [],
    ]]]);

    $page->setSlugs(['en' => 'about', 'ar' => 'about-ar']);
    $page->publish();

    return $page;
}

it('wraps the page in the layout it names', function () {
    layoutPage('docs');

    get('/about')
        ->assertOk()
        ->assertSee('About heading')
        // The docs shell, which the marketing one does not have.
        ->assertSee('<aside', escape: false);
});

it('falls back to the configured layout when the page names none', function () {
    layoutPage();

    $html = get('/about')->assertOk()->getContent();

    expect($html)->toContain('About heading')
        ->and($html)->not->toContain('<aside');
});

it('falls back rather than 500ing when the layout was removed from the panel', function () {
    // A page keeps its key after a developer deletes that layout. Every public
    // page erroring is a bad way to find that out.
    layoutPage('a-layout-nobody-registered');

    get('/about')->assertOk()->assertSee('About heading');
});

it('previews through the same layout the public page uses', function () {
    $page = layoutPage('docs');

    $url = URL::signedRoute(
        'atelier.preview',
        ['page' => $page->getKey(), 'locale' => 'en'],
        absolute: false,
    );

    // A preview rendered through a different shell is a preview that lies.
    get($url)->assertOk()->assertSee('<aside', escape: false);
});

it('takes a view name on its own and labels it from the key', function () {
    $registry = new LayoutRegistry;

    $registry->register([
        'landing' => 'layouts.landing',
        'docs' => ['label' => 'Sidebar', 'view' => 'layouts.docs'],
    ]);

    expect($registry->options())->toBe(['landing' => 'Landing', 'docs' => 'Sidebar'])
        ->and($registry->view('landing'))->toBe('layouts.landing')
        ->and($registry->view('nope'))->toBeNull()
        ->and($registry->view(null))->toBeNull()
        ->and((new LayoutRegistry)->options())->toBe([]);
});
