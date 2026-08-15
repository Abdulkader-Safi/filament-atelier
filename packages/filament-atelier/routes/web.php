<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Safi\Atelier\Http\Controllers\PreviewController;

// Relative signature: the preview is same-origin, and binding the signature
// to the host breaks the moment you browse 127.0.0.1 while APP_URL says
// localhost, or hit the app through a tunnel.
Route::middleware(['web', 'signed:relative'])
    ->get('atelier/preview/{page}/{locale}', PreviewController::class)
    ->name('atelier.preview');
