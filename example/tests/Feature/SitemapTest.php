<?php

declare(strict_types=1);

use Safi\Atelier\Models\Page;
use Safi\Atelier\SitemapRegistry;

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

it('includes URLs a host app registers, in every shape', function () {
    sitemapPage('About', 'about');

    app(SitemapRegistry::class)->add([
        // The easy case: a bare URL.
        fn () => ['https://example.test/blog'],
        // The full case, with a real date object and per-locale alternates.
        fn () => [[
            'loc' => 'https://example.test/blog/hello',
            'lastmod' => new DateTimeImmutable('2026-08-01 10:00:00'),
            'alternates' => ['ar' => 'https://example.test/ar/blog/hello'],
        ]],
    ]);

    $xml = get('/sitemap.xml')->assertOk()->getContent();

    expect($xml)
        ->toContain('<loc>https://example.test/blog</loc>')
        ->toContain('<loc>https://example.test/blog/hello</loc>')
        ->toContain('<lastmod>2026-08-01T10:00:00+00:00</lastmod>')
        ->toContain('hreflang="ar" href="https://example.test/ar/blog/hello"')
        // Atelier's own pages are still there.
        ->toContain('<loc>http://localhost:8000/about</loc>');

    expect(simplexml_load_string($xml))->not->toBeFalse();
});

it('resolves an invokable class source from the container', function () {
    app(SitemapRegistry::class)->add(ServiceUrls::class);

    expect(get('/sitemap.xml')->getContent())
        ->toContain('<loc>https://example.test/services/web-design</loc>');
});

it('lists a URL once even if a source repeats one Atelier already has', function () {
    sitemapPage('About', 'about');

    app(SitemapRegistry::class)->add(fn () => ['http://localhost:8000/about']);

    expect(substr_count(get('/sitemap.xml')->getContent(), '<loc>http://localhost:8000/about</loc>'))
        ->toBe(1);
});

it('skips an entry with no url rather than emitting an empty loc', function () {
    app(SitemapRegistry::class)->add(fn () => [
        ['lastmod' => '2026-08-01'],
        ['loc' => ''],
        ['loc' => 'https://example.test/real'],
    ]);

    $xml = get('/sitemap.xml')->getContent();

    expect(substr_count($xml, '<url>'))->toBe(1)
        ->and($xml)->toContain('https://example.test/real');
});

class ServiceUrls
{
    public function __invoke(): array
    {
        return [['loc' => 'https://example.test/services/web-design']];
    }
}

it('points at a stylesheet so a browser shows a table, not a node tree', function () {
    sitemapPage('About', 'about');

    $xml = get('/sitemap.xml')->getContent();

    // The instruction sits between the declaration and the root, which is the
    // only place it is allowed.
    expect($xml)->toContain('<?xml-stylesheet type="text/xsl" href="http://localhost:8000/sitemap.xsl"?>')
        ->and(simplexml_load_string($xml))->not->toBeFalse();

    get('/sitemap.xsl')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/xsl; charset=UTF-8')
        ->assertSee('xsl:stylesheet', escape: false);
});
