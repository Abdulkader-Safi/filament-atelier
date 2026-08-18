<?php

declare(strict_types=1);

namespace Safi\Atelier\Http\Controllers;

use Illuminate\Http\Response;

/**
 * A robots.txt pointing at the sitemap and keeping crawlers out of the panel
 * and the preview route.
 *
 * Laravel ships a static `public/robots.txt`, and a real file is served by the
 * web server before any route runs. Delete that file to use this one, or copy
 * the `Sitemap:` line into it. There is no way for a package to detect which
 * the host app wanted, so it registers the route and says so in the docs.
 */
class RobotsController
{
    public function __invoke(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /atelier/preview/',
        ];

        if ($path = config('atelier.robots.disallow_panel')) {
            $lines[] = 'Disallow: '.rtrim($path, '/').'/';
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.url('sitemap.xml');

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
