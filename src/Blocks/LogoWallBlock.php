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

    public static function supports(): array
    {
        return ['background', 'padding'];
    }

    public static function label(): string
    {
        return __('filament-atelier::atelier.blocks.logo_wall.label');
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
            TextInput::make('heading')->label(__('filament-atelier::atelier.blocks.logo_wall.fields.heading'))->live(debounce: 400),
            Repeater::make('logos')
                ->label(__('filament-atelier::atelier.blocks.logo_wall.fields.logos'))
                ->schema([
                    Media::upload('image', __('filament-atelier::atelier.blocks.logo_wall.fields.logo')),
                    TextInput::make('name')->label(__('filament-atelier::atelier.blocks.logo_wall.fields.name')),
                    TextInput::make('url')->label(__('filament-atelier::atelier.blocks.logo_wall.fields.url'))->url(),
                ])
                ->reorderable()
                ->collapsed()
                ->itemLabel(fn (array $state) => $state['name'] ?? __('filament-atelier::atelier.blocks.logo_wall.fields.logo'))
                ->live(),
        ];
    }
}
