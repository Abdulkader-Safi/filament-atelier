<?php

declare(strict_types=1);

namespace Safi\Atelier;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AtelierServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-atelier')
            ->hasConfigFile('atelier')
            ->hasViews('atelier')
            ->hasRoute('web')
            ->hasMigration('create_atelier_tables');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(BlockRegistry::class);
    }

    public function packageBooted(): void
    {
        // The panel's stylesheet only contains classes Filament itself uses,
        // so the editor's own utilities have to ship compiled with the plugin.
        // Rebuild with `npm run build` in the package after changing a view.
        FilamentAsset::register(
            [Css::make('atelier', __DIR__.'/../resources/dist/atelier.css')],
            package: 'safi/filament-atelier',
        );
    }
}
