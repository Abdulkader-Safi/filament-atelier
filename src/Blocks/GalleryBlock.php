<?php

declare(strict_types=1);

namespace Safi\Atelier\Blocks;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Safi\Atelier\Media;

class GalleryBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'gallery';
    }

    public static function supports(): array
    {
        return ['background', 'padding'];
    }

    public static function label(): string
    {
        return __('filament-atelier::atelier.blocks.gallery.label');
    }

    public static function icon(): string
    {
        return 'heroicon-o-squares-2x2';
    }

    public static function category(): string
    {
        return __('filament-atelier::atelier.block_categories.media');
    }

    public static function translatable(): array
    {
        return ['heading'];
    }

    public static function defaults(): array
    {
        return ['columns' => '3', 'images' => []];
    }

    public function schema(): array
    {
        return [
            TextInput::make('heading')->label(__('filament-atelier::atelier.blocks.gallery.fields.heading'))->live(debounce: 400),
            Select::make('columns')
                ->label(__('filament-atelier::atelier.blocks.gallery.fields.columns'))
                ->options([
                    '2' => __('filament-atelier::atelier.blocks.gallery.options.columns.two'),
                    '3' => __('filament-atelier::atelier.blocks.gallery.options.columns.three'),
                    '4' => __('filament-atelier::atelier.blocks.gallery.options.columns.four'),
                ])
                ->default('3')
                ->live(),
            Repeater::make('images')
                ->label(__('filament-atelier::atelier.blocks.gallery.fields.images'))
                ->schema([
                    Media::upload('image', __('filament-atelier::atelier.blocks.gallery.fields.image')),
                    TextInput::make('alt')->label(__('filament-atelier::atelier.blocks.gallery.fields.alt')),
                ])
                ->reorderable()
                ->collapsed()
                ->itemLabel(fn (array $state) => $state['alt'] ?? __('filament-atelier::atelier.blocks.gallery.fields.image'))
                ->live(),
        ];
    }
}
