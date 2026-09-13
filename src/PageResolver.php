<?php

declare(strict_types=1);

namespace Safi\Atelier;

use Safi\Atelier\Http\Controllers\PageController;
use Safi\Atelier\Models\Page;
use Safi\Atelier\Models\PageSlug;

/**
 * Which page a URL path names.
 *
 * One implementation, because the rule is subtle and a second copy of it is
 * how `/services/web-design` once returned the `services` page with a 200.
 * The public controller and the editor's link handling both call this.
 */
class PageResolver
{
    /**
     * Split a path into a locale and a slug.
     *
     * The first segment is a locale only when it names one, so
     * `/services/web-design` keeps both segments as the slug rather than
     * reading `services` as a language and dropping the rest. An empty path
     * is the home page, which lives at the slug `home`.
     *
     * @return array{locale: string, slug: string}
     */
    public function split(?string $path): array
    {
        $locales = config('atelier.locales', []);
        $default = (string) array_key_first($locales);

        $segments = array_values(array_filter(explode('/', trim((string) $path, '/')), 'strlen'));

        $locale = $default;

        if ($segments !== [] && array_key_exists($segments[0], $locales)) {
            $locale = array_shift($segments);
        }

        return [
            'locale' => $locale,
            'slug' => implode('/', $segments) ?: Page::HOME,
        ];
    }

    /**
     * The page a path names, published or not.
     *
     * Drafts resolve here: the editor is the one place where an unpublished
     * page is the normal case. Callers serving the public site check
     * `isPublished()` themselves, which is what {@see PageController}
     * does before rendering.
     *
     * @return array{page: Page, locale: string, slug: string}|null
     */
    public function forPath(?string $path): ?array
    {
        ['locale' => $locale, 'slug' => $slug] = $this->split($path);

        $record = PageSlug::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->with('page')
            ->first();

        return $record?->page === null
            ? null
            : ['page' => $record->page, 'locale' => $locale, 'slug' => $slug];
    }

    /**
     * The same, from a full URL rather than a path.
     *
     * Anything pointing at another host is nothing to do with us, and neither
     * is a mailto: or a tel:. Null means "not a page on this site", which the
     * editor turns into a new tab rather than a navigation.
     *
     * @return array{page: Page, locale: string, slug: string}|null
     */
    public function forUrl(?string $url): ?array
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false || isset($parts['scheme']) && ! in_array($parts['scheme'], ['http', 'https'], true)) {
            return null;
        }

        if (isset($parts['host']) && ! in_array($parts['host'], $this->hosts(), true)) {
            return null;
        }

        return $this->forPath($parts['path'] ?? '');
    }

    /**
     * The hosts that count as this site.
     *
     * The request's own host first, because the editor is being used on
     * whatever address the browser is pointed at, and that is regularly not
     * the configured one: `APP_URL` says localhost while somebody browses
     * 127.0.0.1, or the app is reached through a tunnel. Trusting `app.url`
     * alone made every link in the preview look like another website, which
     * is the same mistake the signed preview URL made before it went relative.
     *
     * @return array<int, string>
     */
    protected function hosts(): array
    {
        return array_values(array_unique(array_filter([
            request()?->getHost(),
            parse_url((string) config('app.url'), PHP_URL_HOST),
        ])));
    }
}
