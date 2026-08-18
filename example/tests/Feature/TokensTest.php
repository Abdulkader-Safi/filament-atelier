<?php

declare(strict_types=1);

use Safi\Atelier\Models\Page;
use Safi\Atelier\Tokens;

use function Pest\Laravel\get;

function tokenPage(): Page
{
    $page = Page::create(['title' => 'Tokens', 'draft_content' => [[
        'id' => 'b_one',
        'type' => 'hero',
        'attributes' => ['heading' => ['en' => 'Hello']],
        'children' => [],
    ]]]);

    $page->setSlugs(['en' => 'tokens', 'ar' => 'tokens-ar']);
    $page->publish();

    return $page;
}

it('emits tokens as custom properties on the public page', function () {
    tokenPage();

    get('/tokens')
        ->assertOk()
        ->assertSee('--atelier-color-primary:', escape: false)
        ->assertSee('--atelier-width-container:', escape: false);
});

it('swaps the font stack on the RTL side without a second stylesheet', function () {
    tokenPage();

    get('/ar/tokens-ar')
        ->assertOk()
        ->assertSee('[dir="rtl"]{--atelier-font-sans:var(--atelier-font-arabic)}', escape: false);
});

it('lets config override one token without restating the group', function () {
    config()->set('atelier.tokens', ['color' => ['primary' => '#ff0000']]);

    expect(Tokens::css())
        ->toContain('--atelier-color-primary:#ff0000')
        ->toContain('--atelier-color-surface:#ffffff');
});

it('turns a token reference into a css value and leaves anything else alone', function () {
    expect(Tokens::resolve(['token' => 'color.primary']))->toBe('var(--atelier-color-primary)')
        ->and(Tokens::resolve(['token' => 'color.nope']))->toBeNull()
        ->and(Tokens::resolve('#fff'))->toBe('#fff')
        ->and(Tokens::resolve(['en' => 'Hello']))->toBe(['en' => 'Hello']);
});
