<?php

declare(strict_types=1);

namespace Safi\Atelier\Blocks;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Safi\Atelier\Media;

class ImageBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'image';
    }

    public static function supports(): array
    {
        return ['background', 'padding'];
    }

    public static function label(): string
    {
        return __('filament-atelier::atelier.blocks.image.label');
    }

    public static function icon(): string
    {
        return 'heroicon-o-photo';
    }

    public static function category(): string
    {
        return __('filament-atelier::atelier.block_categories.media');
    }

    public static function translatable(): array
    {
        return ['alt', 'caption'];
    }

    public static function defaults(): array
    {
        return ['width' => 'container'];
    }

    public function schema(): array
    {
        return [
            Media::upload('image', __('filament-atelier::atelier.blocks.image.fields.image'))->live(),
            TextInput::make('alt')
                ->label(__('filament-atelier::atelier.blocks.image.fields.alt'))
                ->helperText(__('filament-atelier::atelier.blocks.image.fields.alt_help'))
                ->live(debounce: 400),
            TextInput::make('caption')->label(__('filament-atelier::atelier.blocks.image.fields.caption'))->live(debounce: 400),
            Select::make('width')
                ->label(__('filament-atelier::atelier.blocks.image.fields.width'))
                ->options([
                    'container' => __('filament-atelier::atelier.blocks.image.options.width.container'),
                    'wide' => __('filament-atelier::atelier.blocks.image.options.width.wide'),
                    'full' => __('filament-atelier::atelier.blocks.image.options.width.full'),
                ])
                ->default('container')
                ->live(),
        ];
    }
}
