<?php

declare(strict_types=1);

use Safi\Atelier\Models\Page;
use Safi\Atelier\Models\PageRedirect;

use function Pest\Laravel\get;

function slugPage(string $title, string $slug): Page
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

it('301s the old URL after a slug changes', function () {
    $page = slugPage('Services', 'services');

    $page->setSlugs(['en' => 'what-we-do', 'ar' => 'services-ar']);

    get('/services')->assertRedirect('http://localhost:8000/what-we-do')->assertStatus(301);
    get('/what-we-do')->assertOk()->assertSee('Services heading');
});

it('sends every old URL to where the page lives now, with no chain', function () {
    $page = slugPage('Services', 'one');

    $page->setSlugs(['en' => 'two', 'ar' => 'one-ar']);
    $page->setSlugs(['en' => 'three', 'ar' => 'one-ar']);

    // Both hops land on the current URL directly, not on each other.
    get('/one')->assertRedirect('http://localhost:8000/three');
    get('/two')->assertRedirect('http://localhost:8000/three');
});

it('redirects per locale, not across them', function () {
    $page = slugPage('Services', 'services');

    $page->setSlugs(['en' => 'what-we-do', 'ar' => 'khadamat']);

    get('/services')->assertRedirect('http://localhost:8000/what-we-do');
    get('/ar/services-ar')->assertRedirect('http://localhost:8000/ar/khadamat');
});

it('404s rather than redirecting to an unpublished page', function () {
    $page = slugPage('Services', 'services');
    $page->setSlugs(['en' => 'what-we-do', 'ar' => 'services-ar']);
    $page->unpublish();

    get('/services')->assertNotFound();
});

it('gives up the redirect when another page claims the slug', function () {
    $old = slugPage('Services', 'services');
    $old->setSlugs(['en' => 'what-we-do', 'ar' => 'services-ar']);

    expect(PageRedirect::where('from_slug', 'services')->exists())->toBeTrue();

    // A new page takes the freed slug. Whoever claims it owns it.
    $new = Page::create(['title' => 'Services rewritten', 'draft_content' => [[
        'id' => 'b_two',
        'type' => 'hero',
        'attributes' => ['heading' => ['en' => 'Services rewritten heading']],
        'children' => [],
    ]]]);
    $new->setSlugs(['en' => 'services', 'ar' => 'khadamat-jadida']);
    $new->publish();

    expect(PageRedirect::where('from_slug', 'services')->exists())->toBeFalse();

    get('/services')->assertOk()->assertSee('Services rewritten heading');
});

it('writes no redirect when the slug does not change', function () {
    $page = slugPage('Services', 'services');

    $page->setSlugs(['en' => 'services', 'ar' => 'services-ar']);

    expect(PageRedirect::count())->toBe(0);
});
