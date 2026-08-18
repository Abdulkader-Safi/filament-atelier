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
}
