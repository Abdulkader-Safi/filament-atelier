<?php

declare(strict_types=1);

namespace Safi\Atelier\Http\Controllers;

use Safi\Atelier\Models\Page;
use Safi\Atelier\Models\PageRedirect;
use Safi\Atelier\Models\PageSlug;
use Safi\Atelier\Renderer;
use Symfony\Component\HttpFoundation\Response;

class PageController
{
    public function __construct(protected Renderer $renderer) {}

    /**
     * The public page. Reads published_content and nothing else, so an
     * in-progress draft can never leak.
     */
    public function __invoke(?string $locale = null, ?string $slug = null): Response
    {
        $locales = config('atelier.locales', []);
        $default = array_key_first($locales);

        // /{slug} is the default locale. /{locale}/{slug} is everything else.
        // The first segment is only a locale when it names one, so /services/web-design
        // puts both segments back into the slug rather than dropping the second and
        // serving /services with a 200, which is worse than a 404.
        if ($locale !== null && ! array_key_exists($locale, $locales)) {
            $slug = $slug === null ? $locale : "{$locale}/{$slug}";
            $locale = $default;
        }

        $locale ??= $default;
        $slug = trim((string) ($slug ?: 'home'), '/');

        $record = PageSlug::where('locale', $locale)->where('slug', $slug)->first();

        if ($record === null) {
            return $this->redirectOr404($locale, $slug);
        }

        /** @var Page $page */
        $page = $record->page;

        abort_unless($page->isPublished(), 404);

        app()->setLocale($locale);

        return response()->view($page->layoutView(), [
            'locale' => $locale,
            'page' => $page,
            'title' => $page->metaTitle($locale),
            'preview' => false,
            'blocks' => $this->renderer->render($page->published(), $locale),
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
