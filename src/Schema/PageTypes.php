<?php

declare(strict_types=1);

namespace Safi\Atelier\Schema;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

/**
 * The page types a client can pick, and the few fields each one needs.
 *
 * Deliberately not all of schema.org. A select with three hundred entries is
 * a worse question than no question, and most of them describe things a page
 * builder will never render.
 *
 * Two shapes live here, and the difference decides how the graph is built:
 *
 * - **Page-shaped** types refine what the page *is*, so they replace the
 *   `WebPage` node's own type. An About page is a WebPage.
 * - **Thing-shaped** types describe what the page is *about*, so they become
 *   their own node and the WebPage points at them through `mainEntity`. A
 *   page about a product is not a product.
 */
class PageTypes
{
    /** Types that refine the WebPage node itself. */
    public const PAGE_SHAPED = ['WebPage', 'AboutPage', 'ContactPage', 'CollectionPage'];

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            'WebPage' => 'Standard page',
            'AboutPage' => 'About page',
            'ContactPage' => 'Contact page',
            'CollectionPage' => 'Listing page',
            'Article' => 'Article or blog post',
            'Service' => 'Service',
            'Product' => 'Product',
            'Event' => 'Event',
            'Person' => 'Person or profile',
        ];
    }

    public static function isPageShaped(string $type): bool
    {
        return in_array($type, self::PAGE_SHAPED, true);
    }

    /**
     * The fields for one type.
     *
     * Nothing here duplicates a field the page already has. The name and the
     * description come from the meta title and meta description, and the image
     * from the social share image, because a client should not describe the
     * same page twice.
     *
     * @return array<int, Field>
     */
    public static function fields(string $type): array
    {
        return match ($type) {
            'Article' => [
                TextInput::make('schema.data.author')
                    ->label('Author')
                    ->helperText('A person. Leave empty and the organisation is credited instead.'),
                DateTimePicker::make('schema.data.published_at')
                    ->label('Published')
                    ->helperText('Only if it differs from when the page was published.'),
            ],

            'Service' => [
                TextInput::make('schema.data.service_type')
                    ->label('Service type')
                    ->helperText('What it is, plainly: Web design, Tax advice.'),
                TextInput::make('schema.data.area_served')
                    ->label('Areas served')
                    ->helperText('Comma separated. Leave empty to use the site details.'),
                TextInput::make('schema.data.price')->label('Price')->numeric(),
                TextInput::make('schema.data.currency')
                    ->label('Currency')
                    ->helperText('Three letters, e.g. AED.')
                    ->maxLength(3),
            ],

            'Product' => [
                TextInput::make('schema.data.sku')->label('SKU'),
                TextInput::make('schema.data.brand')->label('Brand'),
                TextInput::make('schema.data.price')->label('Price')->numeric(),
                TextInput::make('schema.data.currency')
                    ->label('Currency')
                    ->helperText('Three letters, e.g. AED.')
                    ->maxLength(3),
                Select::make('schema.data.availability')
                    ->label('Availability')
                    ->options([
                        'InStock' => 'In stock',
                        'OutOfStock' => 'Out of stock',
                        'PreOrder' => 'Pre-order',
                    ])
                    ->native(false),
            ],

            'Event' => [
                DateTimePicker::make('schema.data.start')->label('Starts')->required(),
                DateTimePicker::make('schema.data.end')->label('Ends'),
                TextInput::make('schema.data.location')
                    ->label('Location')
                    ->helperText('Venue name, or Online.'),
                TextInput::make('schema.data.location_address')->label('Address'),
                TextInput::make('schema.data.price')->label('Ticket price')->numeric(),
                TextInput::make('schema.data.currency')->label('Currency')->maxLength(3),
            ],

            'Person' => [
                TextInput::make('schema.data.name')
                    ->label('Full name')
                    ->helperText('Leave empty to use the meta title.'),
                TextInput::make('schema.data.job_title')->label('Job title'),
                TextInput::make('schema.data.same_as')
                    ->label('Profile URL')
                    ->url()
                    ->helperText('LinkedIn, or wherever this person is already known.'),
            ],

            default => [],
        };
    }
}
