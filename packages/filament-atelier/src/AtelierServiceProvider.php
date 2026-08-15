<?php

declare(strict_types=1);

namespace Safi\Atelier;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AtelierServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-atelier')
            ->hasConfigFile('atelier')
            ->hasViews('atelier');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(BlockRegistry::class);
    }
}
