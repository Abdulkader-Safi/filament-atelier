<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Safi\Atelier\AtelierPlugin;
use Safi\Atelier\Blocks\DefaultBlocks;
use Safi\Atelier\Models\Page as AtelierPage;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                AtelierPlugin::make()
                    ->blocks(DefaultBlocks::all())
                    // Off by default in config/atelier.php: the menu
                    // manager is still being proven out, and this line is
                    // what turns it on for this one panel. Delete it (or
                    // flip it to false) to pull the Menus page and its
                    // route out of the panel entirely, no separate
                    // uninstall step.
                    ->experimental(['menus' => true])
                    // The locations themselves (primary, footer, sidebar)
                    // live in config/atelier.php now, next to `locales`.
                    // ->menuLocations([...]) still works here too, additive
                    // rather than a replacement, for a location that only
                    // makes sense inside this one panel.
                    ->menuSources([
                        AtelierPage::class,
                    ])
                    ->layouts([
                        'site' => [
                            'label' => 'Navbar and footer',
                            'view' => 'atelier::layouts.site',
                            'description' => 'The marketing shell. Full-width sections.',
                        ],
                        'docs' => [
                            'label' => 'Sidebar',
                            'view' => 'layouts.docs',
                            'description' => 'Documentation shell, with a page list beside the content.',
                        ],
                        'marketing' => [
                            'label' => 'Navbar and footer menus',
                            'view' => 'layouts.marketing',
                            'description' => 'Same shell as "site", with the primary and footer menus wired up.',
                        ],
                    ]),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
