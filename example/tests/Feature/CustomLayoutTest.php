<?php

declare(strict_types=1);

use Illuminate\Support\Facades\URL;
use Safi\Atelier\Models\Page;

use function Pest\Laravel\get;

function seoPage(): Page
{
    $page = Page::create(['title' => 'Fallback title', 'draft_content' => [[
        'id' => 'b_one',
        'type' => 'hero',
        'attributes' => ['heading' => ['en' => 'Hero heading']],
        'children' => [],
    ]]]);

    $page->setSlugs(['en' => 'about', 'ar' => 'about-ar']);
    $page->update(['seo' => ['en' => [
        'meta_title' => 'Meta title',
        'meta_description' => 'Meta description',
        'og_image' => 'atelier/og/share.jpg',
    ]]]);
    $page->publish();

    return $page;
}

it('renders the whole head from a host app layout, not just Atelier\'s own', function () {
    config()->set('atelier.layout', 'fixtures.custom-layout');

    seoPage();

    get('/about')
        ->assertOk()
        // The host app's shell really is the one rendering.
        ->assertSee("A host app's own navigation", escape: false)
        ->assertSee('Hero heading')
        // And it loses none of the head, which is the whole point.
        ->assertSee('<title>Meta title</title>', escape: false)
        ->assertSee('name="description" content="Meta description"', escape: false)
        ->assertSee('rel="canonical"', escape: false)
        ->assertSee('hreflang="ar"', escape: false)
        ->assertSee('property="og:image" content="http://localhost:8000/storage/atelier/og/share.jpg"', escape: false)
        ->assertSee('name="twitter:card" content="summary_large_image"', escape: false)
        ->assertSee('--atelier-color-primary:', escape: false);
});

it('emits the same head from the stock layout as from a custom one', function () {
    seoPage();

    $stock = get('/about')->getContent();

    config()->set('atelier.layout', 'fixtures.custom-layout');
    $custom = get('/about')->getContent();

    $head = fn (string $html) => collect(explode("\n", $html))
        ->filter(fn (string $line) => str_contains($line, '<meta ')
            || str_contains($line, '<title>')
            || str_contains($line, 'rel="canonical"')
            || str_contains($line, 'rel="alternate"'))
        // charset and viewport belong to the layout, not to the partial.
        ->reject(fn (string $line) => str_contains($line, 'charset')
            || str_contains($line, 'viewport'))
        ->map(fn (string $line) => trim($line))
        ->values();

    expect($head($custom))->toEqual($head($stock))
        ->and($head($stock))->not->toBeEmpty();
});

it('keeps the preview noindex when a host app supplies the layout', function () {
    config()->set('atelier.layout', 'fixtures.custom-layout');

    $page = seoPage();

    // The route validates a relative signature, so the link has to be one.
    $url = URL::signedRoute(
        'atelier.preview',
        ['page' => $page->getKey(), 'locale' => 'en'],
        absolute: false,
    );

    get($url)
        ->assertOk()
        ->assertSee('name="robots" content="noindex, nofollow"', escape: false);
});
