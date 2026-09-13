<?php

declare(strict_types=1);

namespace App\PageTypes;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Safi\Atelier\Models\Page;
use Safi\Atelier\PageTypes\BasePageType;

/**
 * Something the client sells rather than does: the cleaning products they
 * use on site, in the sizes they stock.
 *
 * Same machinery as ServiceType with a different field list, which is the
 * whole difference between the two sidebar entries.
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
            Textarea::make('excerpt')
                ->label('Short description')
                ->rows(2)
                ->maxLength(180)
                ->columnSpanFull(),

            FileUpload::make('card_image')
                ->label('Card image')
                ->image()
                ->disk(config('atelier.media.disk'))
                ->directory(config('atelier.media.directory').'/products')
                ->visibility('public'),

            TextInput::make('sku')->label('SKU'),

            TextInput::make('price')
                ->label('Price')
                ->numeric()
                ->prefix('AED')
                ->helperText('Used when the product has no variations.'),

            Toggle::make('featured')->label('Featured'),

            // Sizes, scents, pack counts. Each one prices itself, and the
            // order form offers exactly these.
            Repeater::make('variations')
                ->label('Variations')
                ->schema([
                    TextInput::make('name')->label('Variation')->required()->placeholder('5 litre'),
                    TextInput::make('price')->label('Price')->numeric()->required()->prefix('AED'),
                    TextInput::make('sku')->label('SKU'),
                    Toggle::make('in_stock')->label('In stock')->default(true),
                ])
                ->columns(3)
                ->collapsed()
                ->reorderable()
                ->defaultItems(0)
                ->itemLabel(fn (array $state) => $state['name'] ?? 'Variation')
                ->addActionLabel('Add a variation')
                ->columnSpanFull(),
        ];
    }

    public static function translatable(): array
    {
        return ['excerpt'];
    }

    public static function template(): array
    {
        return [
            ['type' => 'hero'],
            ['type' => 'rich-text'],
            ['type' => 'request-form'],
        ];
    }

    public static function cardView(): string
    {
        return 'products.card';
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

    /** The cheapest variation, or the base price when there are none. */
    public static function fromPrice(Page $page): ?float
    {
        $prices = collect($page->data('variations') ?? [])
            ->pluck('price')
            ->filter(fn (mixed $price) => is_numeric($price))
            ->map(fn (mixed $price) => (float) $price);

        if ($prices->isNotEmpty()) {
            return $prices->min();
        }

        $base = $page->data('price');

        return is_numeric($base) ? (float) $base : null;
    }
}
