<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Safi\Atelier\Http\Controllers\PreviewController;

Route::middleware(['web', 'signed'])
    ->get('atelier/preview/{page}/{locale}', PreviewController::class)
    ->name('atelier.preview');
