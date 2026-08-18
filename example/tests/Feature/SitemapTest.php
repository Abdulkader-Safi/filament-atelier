<?php

declare(strict_types=1);

use Safi\Atelier\Models\Page;

use function Pest\Laravel\get;

function sitemapPage(string $title, string $slug, array $seo = []): Page
{
    $page = Page::create(['title' => $title, 'seo' => $seo, 'draft_content' => [[
        'id' => 'b_one',
        'type' => 'hero',
        'attributes' => ['heading' => ['en' => "{$title} heading"]],
        'children' => [],
    ]]]);

    $page->setSlugs(['en' => $slug, 'ar' => "{$slug}-ar"]);
    $page->publish();

    return $page;
}

it('lists every published page in both locales, with alternates', function () {
    sitemapPage('About', 'about');

    $xml = get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->getContent();

    expect($xml)
        ->toContain('<loc>http://localhost:8000/about</loc>')
        ->toContain('<loc>http://localhost:8000/ar/about-ar</loc>')
        ->toContain('hreflang="ar" href="http://localhost:8000/ar/about-ar"')
        ->toContain('<lastmod>');

    // Valid XML, not just a string that looks like it.
    expect(simplexml_load_string($xml))->not->toBeFalse();
});

it('leaves out drafts', function () {
    $page = sitemapPage('Secret', 'secret');
    $page->unpublish();

    expect(get('/sitemap.xml')->getContent())->not->toContain('secret');
});

it('leaves out a locale marked noindex, and stops pointing at it', function () {
    sitemapPage('About', 'about', ['ar' => ['noindex' => true]]);

    $xml = get('/sitemap.xml')->getContent();

    expect($xml)
        ->toContain('<loc>http://localhost:8000/about</loc>')
        // The Arabic URL is gone, and so is the alternate that pointed at it.
        ->not->toContain('/ar/about-ar');
});

it('stays valid XML with no pages at all', function () {
    $xml = get('/sitemap.xml')->assertOk()->getContent();

    expect(simplexml_load_string($xml))->not->toBeFalse()
        ->and($xml)->not->toContain('<url>');
});

it('serves a robots.txt pointing at the sitemap', function () {
    get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Sitemap: http://localhost:8000/sitemap.xml')
        ->assertSee('Disallow: /atelier/preview/')
        ->assertSee('Disallow: /admin/');
});

it('emits a robots meta only when it has something to say', function () {
    sitemapPage('Plain', 'plain');
    expect(get('/plain')->getContent())->not->toContain('name="robots"');

    sitemapPage('Hidden', 'hidden', ['en' => ['noindex' => true, 'nofollow' => true]]);
    expect(get('/hidden')->getContent())
        ->toContain('<meta name="robots" content="noindex, nofollow">');
});
