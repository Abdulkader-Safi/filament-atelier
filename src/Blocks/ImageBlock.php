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

    public static function icon(): string
    {
        return 'heroicon-o-photo';
    }

    public static function category(): string
    {
        return 'Media';
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
            Media::upload('image', 'Image')->live(),
            TextInput::make('alt')
                ->label('Alt text')
                ->helperText('What the image shows. Leave empty only if it is decorative.')
                ->live(debounce: 400),
            TextInput::make('caption')->label('Caption')->live(debounce: 400),
            Select::make('width')
                ->label('Width')
                ->options(['container' => 'Container', 'wide' => 'Wide', 'full' => 'Full bleed'])
                ->default('container')
                ->live(),
        ];
    }
}
