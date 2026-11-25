<?php

declare(strict_types=1);

use Safi\Atelier\Models\Page;

use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;

/** The WebPage node's dates and the sitemap's lastmod, as the public sees them. */
function publishedDates(): array
{
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', get('/dated')->getContent(), $m);
    $webPage = collect(json_decode($m[1], true)['@graph'])->firstWhere('@type', 'WebPage');

    preg_match('#<loc>http://localhost:8000/dated</loc>\s*<lastmod>(.*?)</lastmod>#', get('/sitemap.xml')->getContent(), $lastmod);

    return [
        'published' => $webPage['datePublished'],
        'modified' => $webPage['dateModified'],
        'lastmod' => $lastmod[1] ?? null,
    ];
}

it('keeps the first publish date, and moves lastmod and dateModified together', function () {
    travelTo('2026-01-01 10:00:00');
    $page = Page::create(['title' => 'Dated', 'draft_content' => [[
        'id' => 'b_one', 'type' => 'hero', 'attributes' => ['heading' => ['en' => 'One']], 'children' => [],
    ]]]);
    $page->setSlugs(['en' => 'dated', 'ar' => 'dated-ar']);
    $page->publish();

    $first = publishedDates();

    expect($first['published'])->toStartWith('2026-01-01')
        ->and($first['lastmod'])->toBe($first['modified']);

    // A draft save is invisible to visitors, so no date moves.
    travelTo('2026-02-01 10:00:00');
    $page->update(['draft_content' => [[
        'id' => 'b_one', 'type' => 'hero', 'attributes' => ['heading' => ['en' => 'Two']], 'children' => [],
    ]]]);

    expect(publishedDates())->toBe($first);

    // A republish moves the modified date in both places, and only that.
    travelTo('2026-03-01 10:00:00');
    $page->publish();

    $second = publishedDates();

    expect($second['published'])->toBe($first['published'])
        ->and($second['modified'])->toStartWith('2026-03-01')
        ->and($second['lastmod'])->toBe($second['modified']);

    // Unpublishing and publishing again keeps the original date too.
    $page->unpublish();
    $page->publish();

    expect(publishedDates()['published'])->toBe($first['published']);
});
