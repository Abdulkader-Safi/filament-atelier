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

    public static function icon(): string
    {
        return 'heroicon-o-squares-2x2';
    }

    public static function category(): string
    {
        return 'Media';
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
            TextInput::make('heading')->label('Heading')->live(debounce: 400),
            Select::make('columns')
                ->label('Columns')
                ->options(['2' => 'Two', '3' => 'Three', '4' => 'Four'])
                ->default('3')
                ->live(),
            Repeater::make('images')
                ->label('Images')
                ->schema([
                    Media::upload('image', 'Image'),
                    TextInput::make('alt')->label('Alt text'),
                ])
                ->reorderable()
                ->collapsed()
                ->itemLabel(fn (array $state) => $state['alt'] ?? 'Image')
                ->live(),
        ];
    }
}
