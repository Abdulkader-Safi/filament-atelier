<?php

declare(strict_types=1);

use Safi\Atelier\Models\Menu;
use Safi\Atelier\Models\Page;
use Safi\Atelier\Url;

use function Pest\Laravel\get;

function homePage(): Page
{
    $page = Page::create(['title' => 'Home', 'draft_content' => [[
        'id' => 'b_one',
        'type' => 'hero',
        'attributes' => ['heading' => ['en' => 'Welcome']],
        'children' => [],
    ]]]);

    $page->setSlugs(['en' => 'home', 'ar' => 'home']);
    $page->publish();

    return $page;
}

// The home page has one URL --------------------------------------------------

it('builds the root as the home page URL, not /home', function () {
    $home = homePage();

    expect($home->url('en'))->toBe('http://localhost:8000')
        ->and($home->url('ar'))->toBe('http://localhost:8000/ar');
});

it('sends /home to the root, permanently', function () {
    homePage();

    get('/home')->assertRedirect('http://localhost:8000')->assertStatus(301);
    get('/ar/home')->assertRedirect('http://localhost:8000/ar')->assertStatus(301);

    get('/')->assertOk()->assertSee('Welcome');
    get('/ar')->assertOk();
});

it('points the canonical tag and the sitemap at the root', function () {
    homePage();

    get('/')->assertOk()->assertSee('<link rel="canonical" href="http://localhost:8000">', escape: false);

    // The duplicate that used to be listed alongside it.
    expect(get('/sitemap.xml')->getContent())
        ->toContain('<loc>http://localhost:8000</loc>')
        ->not->toContain('<loc>http://localhost:8000/home</loc>');
});

it('leaves every other page alone', function () {
    $about = Page::create(['title' => 'About']);
    $about->setSlugs(['en' => 'about']);
    $about->publish();

    expect($about->url('en'))->toBe('http://localhost:8000/about');

    get('/about')->assertOk();
});

// A URL typed in the panel ----------------------------------------------------

it('refuses a scheme that is not a link', function () {
    expect(Url::safe('javascript:alert(1)'))->toBe('#')
        ->and(Url::safe('JavaScript:alert(1)'))->toBe('#')
        ->and(Url::safe('data:text/html;base64,PHNjcmlwdD4='))->toBe('#')
        ->and(Url::safe('vbscript:msgbox'))->toBe('#');
});

it('leaves an ordinary link alone', function () {
    expect(Url::safe('https://example.com/a?b=1#c'))->toBe('https://example.com/a?b=1#c')
        ->and(Url::safe('/services/web-design'))->toBe('/services/web-design')
        ->and(Url::safe('#pricing'))->toBe('#pricing')
        ->and(Url::safe('mailto:hello@example.test'))->toBe('mailto:hello@example.test')
        ->and(Url::safe('tel:+97145550100'))->toBe('tel:+97145550100')
        // A colon after a slash is a path, not a scheme.
        ->and(Url::safe('/a/b:c'))->toBe('/a/b:c')
        ->and(Url::safe(null))->toBe('#');
});

it('keeps a hostile link out of a rendered page', function () {
    $page = Page::create(['title' => 'Home', 'draft_content' => [[
        'id' => 'b_one',
        'type' => 'cta',
        'attributes' => [
            'heading' => ['en' => 'Click me'],
            'cta_label' => ['en' => 'Go'],
            'cta_url' => 'javascript:alert(document.cookie)',
        ],
        'children' => [],
    ]]]);
    $page->setSlugs(['en' => 'home']);
    $page->publish();

    get('/')->assertOk()->assertDontSee('javascript:', escape: false)->assertSee('Click me');
});

it('keeps a hostile menu link out of the nav', function () {
    config(['atelier.menus' => ['primary' => ['label' => 'Primary']]]);

    Menu::forLocation('primary')->update(['items' => [[
        'id' => 'm_one',
        'label' => ['en' => 'Bad'],
        'url' => ['en' => 'javascript:alert(1)'],
        'target' => '_self',
        'hidden' => false,
        'children' => [],
    ]]]);

    $page = Page::create(['title' => 'Home', 'layout' => 'site']);
    $page->setSlugs(['en' => 'home']);
    $page->publish();

    $html = view('atelier::partials.menu', ['location' => 'primary', 'locale' => 'en'])->render();

    expect($html)->toContain('Bad')->not->toContain('javascript:');
});
