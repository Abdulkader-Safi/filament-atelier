<?php

declare(strict_types=1);

namespace Safi\Atelier\Http\Controllers;

use Safi\Atelier\Models\Page;
use Safi\Atelier\Models\PageRedirect;
use Safi\Atelier\PageResolver;
use Safi\Atelier\Renderer;
use Symfony\Component\HttpFoundation\Response;

class PageController
{
    public function __construct(
        protected Renderer $renderer,
        protected PageResolver $resolver,
    ) {}

    /**
     * The public page. Reads published_content and nothing else, so an
     * in-progress draft can never leak.
     */
    public function __invoke(?string $locale = null, ?string $slug = null): Response
    {
        // /{slug} is the default locale, /{locale}/{slug} is everything else,
        // and the first segment is a locale only when it names one. That rule
        // lives in PageResolver because the editor needs the same answer, and
        // a second copy of it is how /services/web-design once served the
        // /services page with a 200.
        $path = implode('/', array_filter([$locale, $slug], fn (?string $part) => filled($part)));

        ['locale' => $locale, 'slug' => $slug] = $this->resolver->split($path);

        $resolved = $this->resolver->forPath($path);

        if ($resolved === null) {
            return $this->redirectOr404($locale, $slug);
        }

        $page = $resolved['page'];

        abort_unless($page->isPublished(), 404);

        app()->setLocale($locale);

        return response()->view($page->layoutView(), [
            'locale' => $locale,
            'page' => $page,
            'title' => $page->metaTitle($locale),
            'preview' => false,
            'blocks' => $this->renderer->render($page->published(), $locale, page: $page),
        ]);
    }

    /**
     * A slug nobody claims might still be one this site used to answer on.
     *
     * The redirect stores the page rather than a target slug, so a page
     * renamed twice sends both old URLs to wherever it lives now, with no
     * chain to follow. An unpublished target 404s: redirecting to a 404 is
     * worse than the 404 itself.
     */
    protected function redirectOr404(string $locale, string $slug): Response
    {
        $redirect = PageRedirect::with('page')
            ->where('locale', $locale)
            ->where('from_slug', $slug)
            ->first();

        $target = $redirect?->page?->isPublished()
            ? $redirect->page->url($locale)
            : null;

        abort_if($target === null, 404);

        return redirect()->to($target, $redirect->status);
    }
}
