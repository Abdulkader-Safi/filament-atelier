<?php

declare(strict_types=1);

namespace App\PageTypes;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Safi\Atelier\PageTypes\BasePageType;

/**
 * A second type, deliberately thinner than ServiceType: no starter sections,
 * no block restriction, no index route. It is here to prove two types coexist
 * in the sidebar and that everything optional really is optional.
 */
class ProductType extends BasePageType
{
    public static function type(): string
    {
        return 'product';
    }

    public static function label(): string
    {
        return 'Product';
    }

    public static function icon(): string
    {
        return 'heroicon-o-cube';
    }

    public static function fields(): array
    {
        return [
            TextInput::make('sku')->label('SKU'),

            TextInput::make('price')
                ->label('Price')
                ->numeric()
                ->prefix('AED'),

            FileUpload::make('card_image')
                ->label('Card image')
                ->image()
                ->disk(config('atelier.media.disk'))
                ->directory(config('atelier.media.directory').'/products')
                ->visibility('public'),
        ];
    }

    public static function prefix(): array|string|null
    {
        return 'products';
    }

    public static function schemaType(): ?string
    {
        return 'Product';
    }

    public static function navigationSort(): ?int
    {
        return 1;
    }
}
