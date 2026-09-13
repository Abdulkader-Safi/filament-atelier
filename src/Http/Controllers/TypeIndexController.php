<?php

declare(strict_types=1);

namespace Safi\Atelier\Http\Controllers;

use Safi\Atelier\Models\Page;
use Safi\Atelier\Models\PageRedirect;
use Safi\Atelier\Models\PageSlug;
use Safi\Atelier\PageTypeRegistry;
use Symfony\Component\HttpFoundation\Response;

/**
 * A page type's landing page: /services, /ar/خدمات.
 *
 * Registered only for a type that declares both a prefix and an indexView(),
 * and registered before the catch-all page route, which is what makes the
 * handoff below necessary.
 */
class TypeIndexController
{
    public function __construct(protected PageController $pages) {}

    public function __invoke(string $type, string $locale): Response
    {
        $registry = app(PageTypeRegistry::class);
        $class = $registry->resolve($type);
        $prefix = $registry->prefix($type, $locale);

        abort_if($class === null || $prefix === null || $class::indexView() === null, 404);

        // A page the client built at this slug always wins, and so does a
        // redirect left behind by one that used to live here. This route sits
        // ahead of the catch-all, so without the handoff a real /services
        // page would be unreachable the moment the type declared a prefix,
        // and an old URL would stop redirecting.
        //
        // Only a published page wins: a draft parked here must not take the
        // listing down while it is being written.
        $existing = PageSlug::where('locale', $locale)->where('slug', $prefix)->first();

        $redirected = PageRedirect::where('locale', $locale)->where('from_slug', $prefix)->exists();

        if ($existing?->page?->isPublished() || $redirected) {
            return ($this->pages)($locale, $prefix);
        }

        app()->setLocale($locale);

        $pages = Page::query()
            ->where('status', 'published')
            ->ofType($type)
            ->with('slugs')
            ->orderBy('title')
            ->get();

        // Rendered into the configured layout rather than standing alone, so
        // the index gets the same shell, stylesheet and tokens as every other
        // page on the site.
        return response()->view(config('atelier.layout'), [
            'locale' => $locale,
            'page' => null,
            'title' => $class::pluralLabel(),
            'preview' => false,
            'blocks' => view($class::indexView(), [
                'pages' => $pages,
                'type' => $class,
                'locale' => $locale,
            ])->render(),
        ]);
    }
}
