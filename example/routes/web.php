<?php

use App\Http\Controllers\EnquiryController;

// The app's own routes go here and take precedence. Atelier registers a
// catch-all for CMS pages, so anything defined here wins over a page slug.
// The welcome route was removed so '/' resolves to the Home page.

// A host app's own route, to prove a page Atelier does not own can still share
// the site's structured data. Not part of the package.
Route::get('/blog/{slug}', fn (string $slug) => view('fixtures.post', [
    'title' => Str::headline($slug),
]))->name('blog.show');

// The public forms post here: a quote for a service, an order for a product.
// Throttled because it is the only unauthenticated write the site has.
Route::post('/request', [EnquiryController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('enquiry.store');
