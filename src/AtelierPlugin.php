<?php

declare(strict_types=1);

namespace Safi\Atelier;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Safi\Atelier\Filament\Pages\PageEditor;
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

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                PageResource::class,
            ])
            ->pages([
                PageEditor::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
