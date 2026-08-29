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
            ->hasMigrations([
                'create_atelier_tables',
                'create_atelier_page_revisions_table',
                'create_atelier_page_redirects_table',
                'create_atelier_settings_table',
                'add_schema_to_atelier_pages_table',
                'create_atelier_menus_table',
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(BlockRegistry::class);
        $this->app->singleton(SitemapRegistry::class);
        $this->app->singleton(LayoutRegistry::class);

        // Seeded from config('atelier.menus'), the same source `locales`
        // reads from. AtelierPlugin::menuLocations() still works after
        // this: it calls ->locations() again on the same singleton, adding
        // to what config already registered rather than replacing it.
        $this->app->singleton(
            MenuRegistry::class,
            fn () => (new MenuRegistry)->locations(config('atelier.menus', [])),
        );
    }

    public function packageBooted(): void
    {
        // Registered from a booted callback rather than through hasRoute(),
        // and the difference is not cosmetic.
        //
        // A package provider boots before the application's own routes are
        // loaded, so hasRoute() put Atelier's catch-all ahead of everything in
        // routes/web.php. Laravel matches in registration order, so a client's
        // own /blog/{slug} lost to /{locale}/{slug?} and 404'd, which is the
        // exact opposite of what this package promises.
        //
        // booted() runs after every provider, so the app's routes are already
        // in and the catch-all is genuinely last.
        $this->app->booted(fn () => $this->loadRoutesFrom(__DIR__.'/../routes/web.php'));

        // The panel's stylesheet only contains classes Filament itself uses,
        // so the editor's own utilities have to ship compiled with the plugin.
        // Rebuild with `npm run build` in the package after changing a view.
        FilamentAsset::register(
            [Css::make('atelier', __DIR__.'/../resources/dist/atelier.css')],
            package: 'safi/filament-atelier',
        );
    }
}
