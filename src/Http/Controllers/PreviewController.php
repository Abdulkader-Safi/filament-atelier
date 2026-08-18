<?php

declare(strict_types=1);

namespace Safi\Atelier\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Safi\Atelier\Models\Page;
use Safi\Atelier\Renderer;

class PreviewController
{
    public function __construct(protected Renderer $renderer) {}

    /**
     * Renders the draft tree through the public layout. Same views, same
     * stylesheet, different data source. The signature is applied by the
     * route's `signed` middleware.
     */
    public function __invoke(Request $request, Page $page, string $locale): Response
    {
        abort_unless(array_key_exists($locale, config('atelier.locales', [])), 404);

        app()->setLocale($locale);

        // The same layout the public page will use, or the preview lies.
        $html = view($page->layoutView(), [
            'locale' => $locale,
            // The same variables the public render gets. A layout that reads
            // $page works on the live site and 500s in the preview otherwise,
            // which is the exact failure the shared render path exists to
            // prevent.
            'page' => $page,
            'title' => $page->title,
            'preview' => true,
            'blocks' => $this->renderer->render($page->draft(), $locale, editing: true),
        ])->render();

        return response($html)->withHeaders([
            'X-Robots-Tag' => 'noindex, nofollow',
            'Cache-Control' => 'no-store, max-age=0',
        ]);
    }
}
