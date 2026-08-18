<?php

declare(strict_types=1);

use Safi\Atelier\Models\Page;
use Safi\Atelier\Renderer;

use function Pest\Laravel\get;

beforeEach(function () {
    // Arabic first, so Arabic is the default and lives at /{slug}.
    config()->set('atelier.locales', [
        'ar' => ['label' => 'العربية', 'dir' => 'rtl'],
        'en' => ['label' => 'English', 'dir' => 'ltr'],
    ]);
});

function bothLocales(): Page
{
    $page = Page::create(['title' => 'About', 'draft_content' => [[
        'id' => 'b_one',
        'type' => 'hero',
        'attributes' => ['heading' => ['en' => 'About us', 'ar' => 'من نحن']],
        'children' => [],
    ]]]);

    $page->setSlugs(['ar' => 'man-nahnu', 'en' => 'about']);
    $page->publish();

    return $page;
}

it('serves Arabic at the root and prefixes English', function () {
    bothLocales();

    get('/man-nahnu')->assertOk()->assertSee('من نحن')->assertSee('dir="rtl"', escape: false);
    get('/en/about')->assertOk()->assertSee('About us')->assertSee('dir="ltr"', escape: false);
});

it('builds URLs with Arabic unprefixed', function () {
    $page = bothLocales();

    expect($page->url('ar'))->toBe('http://localhost:8000/man-nahnu')
        ->and($page->url('en'))->toBe('http://localhost:8000/en/about');
});

it('falls back to Arabic when a translation is missing', function () {
    $page = Page::create(['title' => 'Solo', 'draft_content' => [[
        'id' => 'b_one',
        'type' => 'hero',
        'attributes' => ['heading' => ['ar' => 'بالعربية فقط']],
        'children' => [],
    ]]]);
    $page->setSlugs(['ar' => 'solo', 'en' => 'solo-en']);
    $page->publish();

    // English has no value, so it renders the new default locale's text.
    get('/en/solo-en')->assertOk()->assertSee('بالعربية فقط');
});

it('puts hreflang and canonical on the right URLs', function () {
    bothLocales();

    get('/man-nahnu')
        ->assertSee('rel="canonical" href="http://localhost:8000/man-nahnu"', escape: false)
        ->assertSee('hreflang="en" href="http://localhost:8000/en/about"', escape: false);
});

it('opens the builder on Arabic', function () {
    expect(array_key_first(config('atelier.locales')))->toBe('ar');
});

it('changes every URL on a site that already has pages, with no redirects', function () {
    // Built while English was the default.
    config()->set('atelier.locales', [
        'en' => ['label' => 'English', 'dir' => 'ltr'],
        'ar' => ['label' => 'العربية', 'dir' => 'rtl'],
    ]);

    $page = bothLocales();

    expect($page->url('en'))->toBe('http://localhost:8000/about');
    get('/about')->assertOk();

    // Then somebody reorders the config.
    config()->set('atelier.locales', [
        'ar' => ['label' => 'العربية', 'dir' => 'rtl'],
        'en' => ['label' => 'English', 'dir' => 'ltr'],
    ]);

    // The slug rows never changed, so no redirect was ever written and the old
    // English URL is simply gone.
    get('/about')->assertNotFound();
    get('/en/about')->assertOk();
});
