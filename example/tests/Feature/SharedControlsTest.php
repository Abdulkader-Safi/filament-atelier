<?php

declare(strict_types=1);

use Safi\Atelier\Models\Page;

use function Pest\Laravel\get;

function styledPage(array $attributes): Page
{
    $page = Page::create(['title' => 'Styled', 'draft_content' => [[
        'id' => 'b_one',
        'type' => 'cta',
        'attributes' => ['heading' => ['en' => 'Styled heading']] + $attributes,
        'children' => [],
    ]]]);

    $page->setSlugs(['en' => 'styled', 'ar' => 'styled-ar']);
    $page->publish();

    return $page;
}

it('renders a background token as a css variable, not a literal colour', function () {
    styledPage(['background' => ['token' => 'color.surface']]);

    get('/styled')
        ->assertOk()
        ->assertSee('background-color:var(--atelier-color-surface)', escape: false);
});

it('renders a padding preset off the spacing token', function () {
    styledPage(['padding' => 'loose']);

    get('/styled')
        ->assertOk()
        ->assertSee('padding-block:var(--atelier-space-section)', escape: false);
});

it('leaves the block styling alone when nothing is set', function () {
    styledPage([]);

    $html = get('/styled')->assertOk()->getContent();

    expect($html)->not->toContain('padding-block')
        ->and($html)->not->toContain('background-color:')
        // The block keeps its own classes and the editor still finds it.
        ->and($html)->toContain('data-atelier-block="b_one"')
        ->and($html)->toContain('px-6 py-16');
});

it('ignores a value that is not one of the presets', function () {
    styledPage(['padding' => 'not-a-preset']);

    expect(get('/styled')->getContent())->not->toContain('padding-block');
});
