<?php

declare(strict_types=1);

use Safi\Atelier\Models\Page;

use function Pest\Laravel\get;

function publishedPage(string $title, string $slug, array $tree = []): Page
{
    $page = Page::create(['title' => $title, 'draft_content' => $tree ?: [[
        'id' => 'b_one',
        'type' => 'hero',
        'attributes' => ['heading' => ['en' => "{$title} heading", 'ar' => "عنوان"]],
        'children' => [],
    ]]]);

    $page->setSlugs(['en' => $slug, 'ar' => $slug]);
    $page->publish();

    return $page;
}

it('serves a published page at its slug', function () {
    publishedPage('About', 'about');

    get('/about')->assertOk()->assertSee('About heading');
});

it('serves the home page at the root', function () {
    publishedPage('Home', 'home');

    get('/')->assertOk()->assertSee('Home heading');
});

it('404s an unknown slug', function () {
    get('/no-such-page')->assertNotFound();
});

it('404s a draft, so an unfinished page never leaks', function () {
    $page = publishedPage('Secret', 'secret');
    $page->unpublish();

    get('/secret')->assertNotFound();
});

it('renders the other locale with rtl and hreflang', function () {
    publishedPage('Home', 'home');

    get('/ar/home')
        ->assertOk()
        ->assertSee('dir="rtl"', escape: false)
        ->assertSee('hreflang="en"', escape: false)
        ->assertSee('hreflang="ar"', escape: false)
        ->assertSee('عنوان');
});

it('renders published content, never the draft', function () {
    $page = publishedPage('Home', 'home');

    $tree = $page->draft();
    $tree[0]['attributes']['heading']['en'] = 'Edited but not published';
    $page->update(['draft_content' => $tree]);

    get('/')->assertOk()->assertSee('Home heading')->assertDontSee('Edited but not published');
});

it('survives an empty file upload value', function () {
    // Filament's FileUpload stores [] when nothing is chosen, which used to
    // fatal the public page.
    publishedPage('Home', 'home', [[
        'id' => 'b_img',
        'type' => 'image',
        'attributes' => ['image' => [], 'alt' => ['en' => 'nothing']],
        'children' => [],
    ]]);

    get('/')->assertOk();
});

it('outputs per-locale seo into the head', function () {
    $page = publishedPage('Home', 'home');
    $page->update(['seo' => ['en' => ['meta_title' => 'Custom title', 'meta_description' => 'Custom description']]]);

    get('/')
        ->assertSee('<title>Custom title</title>', escape: false)
        ->assertSee('Custom description');
});

it('serves a nested slug rather than the page named by its first segment', function () {
    publishedPage('Services', 'services');
    publishedPage('Web design', 'services/web-design');

    get('/services/web-design')->assertOk()->assertSee('Web design heading');
    get('/services')->assertOk()->assertSee('Services heading');
});

it('404s a nested slug no page claims', function () {
    publishedPage('Services', 'services');

    get('/services/nope')->assertNotFound();
});
