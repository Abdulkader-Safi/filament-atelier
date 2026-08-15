<?php

declare(strict_types=1);

namespace Safi\Atelier;

use Filament\Contracts\Plugin;
use Filament\Panel;

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

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
