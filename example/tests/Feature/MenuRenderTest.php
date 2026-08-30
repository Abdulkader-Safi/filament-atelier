<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Safi\Atelier\Models\Menu;

// These test atelier::partials.menu directly, the package's own shipped
// view, rather than through a page and a layout. A layout is meant to be
// swappable, example/'s marketing layout writes its own nav rather than
// using this partial, and this file's job is the package's contract, not
// whatever any one layout happens to do with it.

function renderTestItem(string $id, string $en, string $urlEn, array $children = [], ?string $ar = null): array
{
    return [
        'id' => $id,
        'label' => $ar !== null ? ['en' => $en, 'ar' => $ar] : ['en' => $en],
        'url' => ['en' => $urlEn],
        'target' => '_self',
        'children' => $children,
    ];
}

it('renders a menu location with its items, including one level of children', function () {
    Menu::forLocation('primary')->update(['items' => [
        renderTestItem('m_about', 'About', '/about', [renderTestItem('m_team', 'Team', '/about/team')]),
    ]]);

    $html = view('atelier::partials.menu', ['location' => 'primary', 'locale' => 'en'])->render();

    expect($html)->toContain('About')
        ->and($html)->toContain('Team')
        ->and($html)->toContain('href="/about"')
        ->and($html)->toContain('href="/about/team"');
});

it('marks the current page aria-current, and its ancestor bold, without marking an unrelated item', function () {
    Menu::forLocation('primary')->update(['items' => [
        renderTestItem('m_about', 'About', '/about', [renderTestItem('m_team', 'Team', '/about/team')]),
        renderTestItem('m_contact', 'Contact', '/contact'),
    ]]);

    // The partial reads the current path off the request, so fake one
    // rather than needing a real page and route for this.
    app()->instance('request', Request::create('/about/team'));

    // Blade wraps the <a> tag's attributes onto their own lines, so collapse
    // whitespace rather than matching the markup's exact formatting.
    $html = preg_replace('/\s+/', ' ', view('atelier::partials.menu', ['location' => 'primary', 'locale' => 'en'])->render());

    // The current item itself.
    expect($html)->toContain('aria-current="page"')
        // Its parent, bold as an ancestor, but not marked current.
        ->and($html)->toContain('href="/about" target="_self" class="font-semibold" >About')
        // A sibling with nothing to do with the current page gets neither.
        ->and($html)->toContain('href="/contact" target="_self" class="" >Contact');
});

it('renders nothing for a location with no items yet', function () {
    $html = view('atelier::partials.menu', ['location' => 'primary', 'locale' => 'en'])->render();

    expect(trim($html))->toBe('');
});

it('renders nothing for a location nobody registered, and never touches the database for it', function () {
    $html = view('atelier::partials.menu', ['location' => 'nobody-registered-this'])->render();

    expect(trim($html))->toBe('')
        ->and(Menu::where('location', 'nobody-registered-this')->exists())->toBeFalse();
});

it('falls back to # rather than another locale\'s URL when this locale has none', function () {
    Menu::forLocation('primary')->update(['items' => [renderTestItem('m_about', 'About', '/about')]]);

    $html = view('atelier::partials.menu', ['location' => 'primary', 'locale' => 'ar'])->render();

    // No Arabic URL was ever set, so this must not silently link to the
    // English one: that would be a broken link dressed up as a working one.
    expect($html)->toContain('href="#"')
        ->and($html)->not->toContain('href="/about"');
});

it('is RTL-safe: no left/right utility leaks into the menu markup', function () {
    Menu::forLocation('primary')->update(['items' => [
        renderTestItem('m_about', 'About', '/about', ar: 'من نحن'),
    ]]);

    $html = view('atelier::partials.menu', ['location' => 'primary', 'locale' => 'ar'])->render();

    expect($html)->toContain('من نحن')
        ->and($html)->not->toContain('ml-')
        ->and($html)->not->toContain('mr-')
        ->and($html)->not->toContain('text-left')
        ->and($html)->not->toContain('text-right');
});
