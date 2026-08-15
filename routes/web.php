<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Safi\Atelier\Http\Controllers\PageController;
use Safi\Atelier\Http\Controllers\PreviewController;

// Relative signature: the preview is same-origin, and binding the signature
// to the host breaks the moment you browse 127.0.0.1 while APP_URL says
// localhost, or hit the app through a tunnel.
Route::middleware(['web', 'signed:relative'])
    ->get('atelier/preview/{page}/{locale}', PreviewController::class)
    ->name('atelier.preview');

// Public pages. Registered last and matched loosely, so this never shadows an
// app's own routes; Laravel matches in registration order.
Route::middleware('web')->group(function () {
    Route::get('/', PageController::class)->name('atelier.home');
    Route::get('/{locale}/{slug?}', PageController::class)
        ->where('slug', '.*')
        ->name('atelier.page');
});
