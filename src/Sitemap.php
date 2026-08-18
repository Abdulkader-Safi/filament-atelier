<?php

declare(strict_types=1);

namespace Safi\Atelier;

use Illuminate\Support\Collection;
use Safi\Atelier\Models\Page;

/**
 * The sitemap, built straight from the pages table.
 *
 * Written rather than pulled in: `spatie/laravel-sitemap` is a good package,
 * but its job is crawling a site to discover URLs, and we already know every
 * URL we have. What is left is about forty lines of XML.
 *
 * Everything is decided per locale, not per page. An English page can be
 * indexable while its Arabic translation is not, so each URL is judged on its
 * own and only lists alternates that are themselves indexable.
 */
class Sitemap
{
    /** @return Collection<int, array{loc: string, lastmod: ?string, alternates: array<string, string>}> */
    public function urls(): Collection
    {
        $locales = array_keys(config('atelier.locales', []));

        $pages = Page::query()
            ->where('status', 'published')
            ->with('slugs')
            ->orderBy('id')
            ->get();

        return $pages->flatMap(function (Page $page) use ($locales) {
            // Judged once per page, because an alternate pointing at a
            // noindexed URL is the same mistake as listing it.
            $indexable = collect($locales)
                ->filter(fn (string $locale) => $page->isIndexable($locale)
                    && $page->url($locale) !== null)
                ->values();

            return $indexable->map(fn (string $locale) => [
                'loc' => $page->url($locale),
                'lastmod' => $page->published_at?->toAtomString(),
                'alternates' => $indexable
                    ->mapWithKeys(fn (string $code) => [$code => $page->url($code)])
                    ->all(),
            ]);
        })->values();
    }

    public function toXml(): string
    {
        $urls = $this->urls()->map(function (array $url): string {
            $lines = ['        <loc>'.e($url['loc']).'</loc>'];

            if ($url['lastmod']) {
                $lines[] = '        <lastmod>'.e($url['lastmod']).'</lastmod>';
            }

            foreach ($url['alternates'] as $code => $href) {
                $lines[] = '        <xhtml:link rel="alternate" hreflang="'
                    .e($code).'" href="'.e($href).'"/>';
            }

            return "    <url>\n".implode("\n", $lines)."\n    </url>";
        })->implode("\n");

        return implode("\n", array_filter([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
                .' xmlns:xhtml="http://www.w3.org/1999/xhtml">',
            $urls ?: null,
            '</urlset>',
        ]))."\n";
    }
}
