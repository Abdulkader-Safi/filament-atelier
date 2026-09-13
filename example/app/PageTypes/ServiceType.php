<?php

declare(strict_types=1);

namespace App\PageTypes;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Safi\Atelier\PageTypes\BasePageType;

/**
 * What a page type looks like in a host app. Nothing here is part of the
 * package: the fields, the starter sections and the card are this site's,
 * and another site's Service would declare different ones.
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
        return 'heroicon-o-wrench-screwdriver';
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

            TextInput::make('starting_price')
                ->label('Starting price')
                ->numeric()
                ->prefix('AED'),

            Toggle::make('featured')
                ->label('Featured')
                ->helperText('Featured services are listed first on the services page.'),
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
            ['type' => 'faq'],
            ['type' => 'cta'],
        ];
    }

    public static function blocks(): ?array
    {
        return ['hero', 'features', 'rich-text', 'image', 'gallery', 'testimonials', 'faq', 'cta'];
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
}
