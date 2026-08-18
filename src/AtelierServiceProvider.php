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
            ->hasMigrations([
                'create_atelier_tables',
                'create_atelier_page_revisions_table',
                'create_atelier_page_redirects_table',
                'create_atelier_settings_table',
                'add_schema_to_atelier_pages_table',
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(BlockRegistry::class);
        $this->app->singleton(SitemapRegistry::class);
        $this->app->singleton(LayoutRegistry::class);
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
