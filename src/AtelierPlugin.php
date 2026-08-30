<?php

declare(strict_types=1);

namespace Safi\Atelier;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Safi\Atelier\Filament\Pages\MenuManager;
use Safi\Atelier\Filament\Pages\PageEditor;
use Safi\Atelier\Filament\Pages\SiteDetails;
use Safi\Atelier\Filament\Resources\PageResource;

class AtelierPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-atelier';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    /** @param class-string<Block>|array<class-string<Block>> $blocks */
    public function blocks(string|array $blocks): static
    {
        app(BlockRegistry::class)->register($blocks);

        return $this;
    }

    /**
     * The layouts a page can be wrapped in.
     *
     * @param  array<string, string|array{label?: string, view: string, description?: string}>  $layouts
     */
    public function layouts(array $layouts): static
    {
        app(LayoutRegistry::class)->register($layouts);

        return $this;
    }

    /**
     * Extra sitemap URLs from outside Atelier: a blog, a services resource,
     * anything with its own model and routes.
     *
     * @param  \Closure|string|array<int, \Closure|string>  $sources
     */
    public function sitemap(\Closure|string|array $sources): static
    {
        app(SitemapRegistry::class)->add($sources);

        return $this;
    }

    /**
     * The named menu locations a client can build (primary, footer, and so on).
     *
     * @param  array<string, string|array{label?: string, depth?: int}>  $locations
     */
    public function menuLocations(array $locations): static
    {
        app(MenuRegistry::class)->locations($locations);

        return $this;
    }

    /**
     * Eloquent models that can be picked as menu items. Each must implement
     * {@see MenuSource}.
     *
     * @param  class-string<MenuSource>|array<int, class-string<MenuSource>>  $sources
     */
    public function menuSources(string|array $sources): static
    {
        app(MenuRegistry::class)->sources($sources);

        return $this;
    }

    /**
     * Feature flags for anything still being proven out, off by default.
     * Overrides `config('atelier.experimental')` rather than only adding to
     * it, so a panel can turn something off that config turned on.
     *
     * @param  array<string, bool>  $flags
     */
    public function experimental(array $flags): static
    {
        app(ExperimentalFeatures::class)->set($flags);

        return $this;
    }

    public function register(Panel $panel): void
    {
        $pages = [
            PageEditor::class,
            SiteDetails::class,
        ];

        // Registers a real page, in a client's own sidebar, so it stays
        // gated behind the flag rather than shipping unconditionally like
        // the other two pages: there is no clean way to un-ship a page a
        // client has already clicked into.
        if (app(ExperimentalFeatures::class)->enabled('menus')) {
            $pages[] = MenuManager::class;
        }

        $panel
            ->resources([
                PageResource::class,
            ])
            ->pages($pages);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
