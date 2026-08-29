<?php

declare(strict_types=1);

use Safi\Atelier\Models\Menu;
use Safi\Atelier\Models\Page;

use function Pest\Laravel\get;

function marketingPage(): Page
{
    $page = Page::create(['title' => 'Home', 'layout' => 'marketing', 'draft_content' => [[
        'id' => 'b_one',
        'type' => 'hero',
        'attributes' => ['heading' => ['en' => 'Home heading']],
        'children' => [],
    ]]]);

    $page->setSlugs(['en' => 'home', 'ar' => 'home-ar']);
    $page->publish();

    return $page;
}

it('renders a menu location with its items, including one level of children', function () {
    Menu::forLocation('primary')->update(['items' => [
        ['id' => 'm_about', 'label' => ['en' => 'About'], 'url' => '/about', 'target' => '_self', 'children' => [
            ['id' => 'm_team', 'label' => ['en' => 'Team'], 'url' => '/about/team', 'target' => '_self', 'children' => []],
        ]],
    ]]);

    marketingPage();

    get('/home')
        ->assertOk()
        ->assertSee('About')
        ->assertSee('Team')
        ->assertSee('href="/about"', escape: false)
        ->assertSee('href="/about/team"', escape: false);
});

it('marks the current page aria-current, and its ancestor bold, without marking an unrelated item', function () {
    Menu::forLocation('primary')->update(['items' => [
        ['id' => 'm_about', 'label' => ['en' => 'About'], 'url' => '/about', 'target' => '_self', 'children' => [
            ['id' => 'm_team', 'label' => ['en' => 'Team'], 'url' => '/about/team', 'target' => '_self', 'children' => []],
        ]],
        ['id' => 'm_contact', 'label' => ['en' => 'Contact'], 'url' => '/contact', 'target' => '_self', 'children' => []],
    ]]);

    $team = Page::create(['title' => 'Team', 'layout' => 'marketing', 'draft_content' => []]);
    $team->setSlugs(['en' => 'about/team', 'ar' => 'about/team-ar']);
    $team->publish();

    // Blade wraps the <a> tag's attributes onto their own lines, so collapse
    // whitespace rather than matching the markup's exact formatting.
    $html = preg_replace('/\s+/', ' ', get('/about/team')->assertOk()->getContent());

    // The current item itself.
    expect($html)->toContain('aria-current="page"')
        // Its parent, bold as an ancestor, but not marked current.
        ->and($html)->toContain('href="/about" target="_self" class="font-semibold" >About')
        // A sibling with nothing to do with the current page gets neither.
        ->and($html)->toContain('href="/contact" target="_self" class="" >Contact');
});

it('renders nothing for a location with no items yet', function () {
    marketingPage();

    $html = get('/home')->assertOk()->getContent();

    expect(substr_count($html, '<nav>'))->toBe(0);
});

it('renders nothing for a location nobody registered, and never touches the database for it', function () {
    $html = view('atelier::partials.menu', ['location' => 'nobody-registered-this'])->render();

    expect(trim($html))->toBe('')
        ->and(Menu::where('location', 'nobody-registered-this')->exists())->toBeFalse();
});

it('is RTL-safe: no left/right utility leaks into the menu markup', function () {
    Menu::forLocation('primary')->update(['items' => [
        ['id' => 'm_about', 'label' => ['ar' => 'من نحن', 'en' => 'About'], 'url' => '/about', 'target' => '_self', 'children' => []],
    ]]);

    $page = Page::create(['title' => 'Home', 'layout' => 'marketing', 'draft_content' => []]);
    $page->setSlugs(['en' => 'ar-home', 'ar' => 'ar-home-ar']);
    $page->publish();

    $html = get('/ar/ar-home-ar')->assertOk()->getContent();

    expect($html)->toContain('من نحن')
        ->and($html)->not->toContain('ml-')
        ->and($html)->not->toContain('mr-')
        ->and($html)->not->toContain('text-left')
        ->and($html)->not->toContain('text-right');
});
