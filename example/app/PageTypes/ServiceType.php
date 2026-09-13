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
 * A cleaning service: deep clean, move-out, office contract.
 *
 * Nothing here is part of the package. The fields, the tiers, the starter
 * sections and the card are this site's, and another site's Service would
 * declare different ones.
 */
class ServiceType extends BasePageType
{
    public static function type(): string
    {
        return 'service';
    }

    public static function label(): string
    {
        return 'Service';
    }

    public static function icon(): string
    {
        return 'heroicon-o-sparkles';
    }

    public static function fields(): array
    {
        return [
            Textarea::make('excerpt')
                ->label('Short description')
                ->rows(2)
                ->maxLength(180)
                ->helperText('One sentence, shown on the card in a listing.')
                ->columnSpanFull(),

            FileUpload::make('card_image')
                ->label('Card image')
                ->image()
                ->disk(config('atelier.media.disk'))
                ->directory(config('atelier.media.directory').'/services')
                ->visibility('public'),

            TextInput::make('icon')
                ->label('Card icon')
                ->placeholder('heroicon-o-sparkles')
                ->helperText('Any Heroicon name. Used when there is no image.'),

            TextInput::make('duration')
                ->label('Typical duration')
                ->placeholder('2 to 3 hours'),

            Toggle::make('featured')
                ->label('Featured')
                ->helperText('Featured services are the ones the homepage lists.'),

            // The pricing table on the service's own page, and the answer to
            // "which one do you recommend". Three rows is the shape the site
            // is designed around, but nothing enforces three.
            Repeater::make('tiers')
                ->label('Pricing')
                ->schema([
                    TextInput::make('name')->label('Tier')->required()->placeholder('Standard'),
                    TextInput::make('price')->label('Price')->numeric()->required()->prefix('AED'),
                    TextInput::make('unit')->label('Per')->placeholder('visit'),
                    Textarea::make('features')
                        ->label('What is included')
                        ->rows(3)
                        ->helperText('One per line.')
                        ->columnSpanFull(),
                    Toggle::make('recommended')
                        ->label('Suggested')
                        ->helperText('Highlighted on the page. Only one should carry it.')
                        ->columnSpanFull(),
                ])
                ->columns(3)
                ->collapsed()
                ->reorderable()
                ->defaultItems(0)
                ->itemLabel(fn (array $state) => $state['name'] ?? 'Tier')
                ->addActionLabel('Add a tier')
                ->columnSpanFull(),
        ];
    }

    public static function translatable(): array
    {
        return ['excerpt'];
    }

    /** A new service opens on this, rather than on an empty canvas. */
    public static function template(): array
    {
        return [
            ['type' => 'hero'],
            ['type' => 'features'],
            ['type' => 'pricing'],
            ['type' => 'request-form'],
            ['type' => 'faq'],
        ];
    }

    public static function blocks(): ?array
    {
        // No 'collection': a service lists nothing, it is what gets listed.
        return ['hero', 'features', 'rich-text', 'testimonials', 'pricing', 'request-form', 'faq', 'cta'];
    }

    public static function cardView(): string
    {
        return 'services.card';
    }

    public static function prefix(): array|string|null
    {
        return ['en' => 'services', 'ar' => 'خدمات'];
    }

    public static function schemaType(): ?string
    {
        return 'Service';
    }

    public static function indexView(): ?string
    {
        return 'services.index';
    }

    public static function navigationSort(): ?int
    {
        return 0;
    }

    /**
     * The cheapest tier, for the "from" line on a card.
     *
     * A helper on the type rather than logic in a Blade view, because two
     * views want the same number.
     */
    public static function fromPrice(Page $page): ?float
    {
        $prices = collect($page->data('tiers') ?? [])
            ->pluck('price')
            ->filter(fn (mixed $price) => is_numeric($price))
            ->map(fn (mixed $price) => (float) $price);

        return $prices->isEmpty() ? null : $prices->min();
    }
}
