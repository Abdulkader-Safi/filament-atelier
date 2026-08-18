<?php

declare(strict_types=1);

namespace Safi\Atelier\Http\Controllers;

use Illuminate\Http\Response;
use Safi\Atelier\Sitemap;

class SitemapController
{
    public function __invoke(Sitemap $sitemap): Response
    {
        return response($sitemap->toXml(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * The stylesheet the sitemap points at, so a person opening it in a
     * browser sees a table instead of a node tree.
     *
     * Served rather than published to `public/`, so it cannot drift from the
     * XML it renders and needs nothing from the host app.
     */
    public function stylesheet(): Response
    {
        return response(file_get_contents(__DIR__.'/../../../resources/sitemap.xsl'), 200, [
            'Content-Type' => 'text/xsl; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
