<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Safi\Atelier\Http\Controllers\PageController;
use Safi\Atelier\Http\Controllers\PreviewController;
use Safi\Atelier\Http\Controllers\RobotsController;
use Safi\Atelier\Http\Controllers\SitemapController;
use Safi\Atelier\Http\Controllers\TypeIndexController;
use Safi\Atelier\PageTypeRegistry;

// Relative signature: the preview is same-origin, and binding the signature
// to the host breaks the moment you browse 127.0.0.1 while APP_URL says
// localhost, or hit the app through a tunnel.
Route::middleware(['web', 'signed:relative'])
    ->get('atelier/preview/{page}/{locale}', PreviewController::class)
    ->name('atelier.preview');

// Both are ordinary routes, so an app that defines its own wins. robots.txt in
// particular is usually a real file in public/, which the web server serves
// before Laravel is reached at all.
Route::middleware('web')->group(function () {
    Route::get('sitemap.xml', SitemapController::class)->name('atelier.sitemap');
    Route::get('sitemap.xsl', [SitemapController::class, 'stylesheet'])->name('atelier.sitemap.style');
    Route::get('robots.txt', RobotsController::class)->name('atelier.robots');
});

// A page type's landing page, one route per locale: /services, /ar/خدمات.
// Only for a type that declares both a prefix and an index view. Registered
// before the catch-all, so the controller hands off to PageController when
// the client has built a real page at the same slug.
Route::middleware('web')->group(function () {
    $registry = app(PageTypeRegistry::class);
    $default = array_key_first(config('atelier.locales', []) ?: []);

    foreach ($registry->all() as $key => $type) {
        if ($type::indexView() === null) {
            continue;
        }

        foreach (array_keys(config('atelier.locales', [])) as $locale) {
            $prefix = $registry->prefix($key, $locale);

            if ($prefix === null) {
                continue;
            }

            Route::get($locale === $default ? $prefix : "{$locale}/{$prefix}", TypeIndexController::class)
                ->defaults('type', $key)
                ->defaults('locale', $locale)
                ->name("atelier.type.{$key}.{$locale}");
        }
    }
});

// Public pages. Registered last and matched loosely, so this never shadows an
// app's own routes; Laravel matches in registration order.
Route::middleware('web')->group(function () {
    Route::get('/', PageController::class)->name('atelier.home');
    Route::get('/{locale}/{slug?}', PageController::class)
        ->where('slug', '.*')
        ->name('atelier.page');
});
