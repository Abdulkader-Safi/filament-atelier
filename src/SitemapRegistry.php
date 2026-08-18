<?php

declare(strict_types=1);

namespace Safi\Atelier;

use Closure;
use DateTimeInterface;
use Illuminate\Support\Collection;

/**
 * Extra URLs for the sitemap, from anywhere that is not an Atelier page.
 *
 * A real client site is rarely only Atelier pages. There is usually a Blog or
 * a Services resource with its own panel tab, its own model and its own
 * routes, and those URLs belong in the sitemap just as much. Atelier cannot
 * know about them, so the host app hands them over:
 *
 *     AtelierPlugin::make()
 *         ->blocks(DefaultBlocks::all())
 *         ->sitemap([
 *             fn () => Post::published()->get()->map(fn (Post $post) => [
 *                 'loc' => route('blog.show', $post),
 *                 'lastmod' => $post->updated_at,
 *             ]),
 *         ]);
 *
 * Sources are called when the sitemap is requested, never at boot, so a
 * source is free to query.
 */
class SitemapRegistry
{
    /** @var array<int, Closure|string> */
    protected array $sources = [];

    /**
     * Register one or more sources.
     *
     * A source is a closure, or the name of an invokable class resolved from
     * the container. Either returns an iterable of URLs, where each one is a
     * plain URL string or an array of `loc`, and optionally `lastmod` and
     * `alternates` keyed by locale.
     *
     * @param  Closure|string|array<int, Closure|string>  $sources
     */
    public function add(Closure|string|array $sources): static
    {
        foreach ((array) $sources as $source) {
            $this->sources[] = $source;
        }

        return $this;
    }

    /** @return array<int, Closure|string> */
    public function all(): array
    {
        return $this->sources;
    }

    /**
     * Every registered source, resolved and normalised.
     *
     * A source that throws takes the sitemap down with it, deliberately. A
     * sitemap that quietly drops half a site is worse than one that fails
     * where someone will see it.
     *
     * @return Collection<int, array{loc: string, lastmod: ?string, alternates: array<string, string>}>
     */
    public function urls(): Collection
    {
        return collect($this->sources)
            ->flatMap(fn (Closure|string $source) => collect(
                is_string($source) ? app($source)() : $source()
            ))
            ->map(fn (mixed $entry) => $this->normalise($entry))
            ->filter()
            ->values();
    }

    /**
     * Accepts a URL string or an array, so the easy case stays a one-liner and
     * the full case is still available.
     *
     * @return array{loc: string, lastmod: ?string, alternates: array<string, string>}|null
     */
    protected function normalise(mixed $entry): ?array
    {
        if (is_string($entry)) {
            $entry = ['loc' => $entry];
        }

        if (! is_array($entry) || blank($entry['loc'] ?? null)) {
            return null;
        }

        $lastmod = $entry['lastmod'] ?? null;

        return [
            'loc' => (string) $entry['loc'],
            'lastmod' => $lastmod instanceof DateTimeInterface
                ? $lastmod->format(DateTimeInterface::ATOM)
                : (blank($lastmod) ? null : (string) $lastmod),
            'alternates' => $entry['alternates'] ?? [],
        ];
    }
}
