<?php

declare(strict_types=1);

namespace Safi\Atelier\Http\Controllers;

use Illuminate\Http\Response;
use Safi\Atelier\Models\Page;
use Safi\Atelier\Models\PageSlug;
use Safi\Atelier\Renderer;

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
        if ($locale !== null && ! array_key_exists($locale, $locales)) {
            [$locale, $slug] = [$default, $locale];
        }

        $locale ??= $default;
        $slug = trim((string) ($slug ?: 'home'), '/');

        $record = PageSlug::where('locale', $locale)->where('slug', $slug)->first();

        abort_if($record === null, 404);

        /** @var Page $page */
        $page = $record->page;

        abort_unless($page->isPublished(), 404);

        app()->setLocale($locale);

        return response()->view(config('atelier.layout'), [
            'locale' => $locale,
            'page' => $page,
            'title' => $page->metaTitle($locale),
            'preview' => false,
            'blocks' => $this->renderer->render($page->published(), $locale),
        ]);
    }
}
