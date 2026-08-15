<?php

declare(strict_types=1);

namespace Safi\Atelier\Blocks;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Safi\Atelier\Media;

class LogoWallBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'logo-wall';
    }

    public static function label(): string
    {
        return 'Logo wall';
    }

    public static function icon(): string
    {
        return 'heroicon-o-building-office-2';
    }

    public static function translatable(): array
    {
        return ['heading'];
    }

    public static function defaults(): array
    {
        return ['heading' => ['en' => 'Trusted by'], 'logos' => []];
    }

    public function schema(): array
    {
        return [
            TextInput::make('heading')->label('Heading')->live(debounce: 400),
            Repeater::make('logos')
                ->label('Logos')
                ->schema([
                    Media::upload('image', 'Logo'),
                    TextInput::make('name')->label('Company'),
                    TextInput::make('url')->label('Link')->url(),
                ])
                ->reorderable()
                ->collapsed()
                ->itemLabel(fn (array $state) => $state['name'] ?? 'Logo')
                ->live(),
        ];
    }
}
