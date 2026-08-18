<?php

declare(strict_types=1);

namespace Safi\Atelier\Schema;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

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
            'JobPosting' => 'Job vacancy',
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
                Select::make('schema.data.condition')
                    ->label('Condition')
                    ->options([
                        'NewCondition' => 'New',
                        'UsedCondition' => 'Used',
                        'RefurbishedCondition' => 'Refurbished',
                        'DamagedCondition' => 'Damaged',
                    ])
                    ->native(false),
                DatePicker::make('schema.data.price_valid_until')
                    ->label('Price valid until')
                    ->helperText('A price with no end date is treated as stale.'),
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
                Select::make('schema.data.status')
                    ->label('Status')
                    ->options([
                        'EventScheduled' => 'Going ahead',
                        'EventRescheduled' => 'Rescheduled',
                        'EventPostponed' => 'Postponed',
                        'EventCancelled' => 'Cancelled',
                        'EventMovedOnline' => 'Moved online',
                    ])
                    ->default('EventScheduled')
                    ->native(false)
                    ->helperText('Cancelling here is what stops it being advertised.'),
                Select::make('schema.data.attendance')
                    ->label('Attendance')
                    ->options([
                        'OfflineEventAttendanceMode' => 'In person',
                        'OnlineEventAttendanceMode' => 'Online',
                        'MixedEventAttendanceMode' => 'Both',
                    ])
                    ->default('OfflineEventAttendanceMode')
                    ->native(false),
            ],

            'JobPosting' => [
                DatePicker::make('schema.data.posted_at')
                    ->label('Posted')
                    ->helperText('Only if it differs from when the page was published.'),
                DatePicker::make('schema.data.valid_through')
                    ->label('Closes')
                    ->helperText('A vacancy with no closing date stays listed forever.'),
                Select::make('schema.data.employment_type')
                    ->label('Type')
                    ->options([
                        'FULL_TIME' => 'Full time',
                        'PART_TIME' => 'Part time',
                        'CONTRACTOR' => 'Contract',
                        'TEMPORARY' => 'Temporary',
                        'INTERN' => 'Internship',
                    ])
                    ->native(false),
                Toggle::make('schema.data.remote')
                    ->label('Remote')
                    ->helperText('Without this a remote role is filtered out of remote searches.'),
                TextInput::make('schema.data.location')
                    ->label('City')
                    ->helperText('Leave empty to use the site details.'),
                TextInput::make('schema.data.country')
                    ->label('Country code')
                    ->maxLength(2),
                TextInput::make('schema.data.salary')->label('Salary')->numeric(),
                TextInput::make('schema.data.currency')->label('Currency')->maxLength(3),
                Select::make('schema.data.salary_unit')
                    ->label('Per')
                    ->options([
                        'HOUR' => 'Hour',
                        'DAY' => 'Day',
                        'WEEK' => 'Week',
                        'MONTH' => 'Month',
                        'YEAR' => 'Year',
                    ])
                    ->default('MONTH')
                    ->native(false),
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
