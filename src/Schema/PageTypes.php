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
            'WebPage' => __('filament-atelier::atelier.schema.page_types.options.web_page'),
            'AboutPage' => __('filament-atelier::atelier.schema.page_types.options.about_page'),
            'ContactPage' => __('filament-atelier::atelier.schema.page_types.options.contact_page'),
            'CollectionPage' => __('filament-atelier::atelier.schema.page_types.options.collection_page'),
            'Article' => __('filament-atelier::atelier.schema.page_types.options.article'),
            'Service' => __('filament-atelier::atelier.schema.page_types.options.service'),
            'Product' => __('filament-atelier::atelier.schema.page_types.options.product'),
            'Event' => __('filament-atelier::atelier.schema.page_types.options.event'),
            'Person' => __('filament-atelier::atelier.schema.page_types.options.person'),
            'JobPosting' => __('filament-atelier::atelier.schema.page_types.options.job_posting'),
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
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.article.author'))
                    ->helperText(__('filament-atelier::atelier.schema.page_types.fields.article.author_help')),
                DateTimePicker::make('schema.data.published_at')
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.article.published_at'))
                    ->helperText(__('filament-atelier::atelier.schema.page_types.fields.article.published_at_help')),
            ],

            'Service' => [
                TextInput::make('schema.data.service_type')
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.service.service_type'))
                    ->helperText(__('filament-atelier::atelier.schema.page_types.fields.service.service_type_help')),
                TextInput::make('schema.data.area_served')
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.service.area_served'))
                    ->helperText(__('filament-atelier::atelier.schema.page_types.fields.service.area_served_help')),
                TextInput::make('schema.data.price')->label(__('filament-atelier::atelier.schema.page_types.fields.service.price'))->numeric(),
                TextInput::make('schema.data.currency')
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.service.currency'))
                    ->helperText(__('filament-atelier::atelier.schema.page_types.fields.service.currency_help'))
                    ->maxLength(3),
            ],

            'Product' => [
                TextInput::make('schema.data.sku')->label(__('filament-atelier::atelier.schema.page_types.fields.product.sku')),
                TextInput::make('schema.data.brand')->label(__('filament-atelier::atelier.schema.page_types.fields.product.brand')),
                TextInput::make('schema.data.price')->label(__('filament-atelier::atelier.schema.page_types.fields.product.price'))->numeric(),
                TextInput::make('schema.data.currency')
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.product.currency'))
                    ->helperText(__('filament-atelier::atelier.schema.page_types.fields.product.currency_help'))
                    ->maxLength(3),
                Select::make('schema.data.availability')
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.product.availability'))
                    ->options([
                        'InStock' => __('filament-atelier::atelier.schema.page_types.options.availability.in_stock'),
                        'OutOfStock' => __('filament-atelier::atelier.schema.page_types.options.availability.out_of_stock'),
                        'PreOrder' => __('filament-atelier::atelier.schema.page_types.options.availability.pre_order'),
                    ])
                    ->native(false),
                Select::make('schema.data.condition')
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.product.condition'))
                    ->options([
                        'NewCondition' => __('filament-atelier::atelier.schema.page_types.options.condition.new'),
                        'UsedCondition' => __('filament-atelier::atelier.schema.page_types.options.condition.used'),
                        'RefurbishedCondition' => __('filament-atelier::atelier.schema.page_types.options.condition.refurbished'),
                        'DamagedCondition' => __('filament-atelier::atelier.schema.page_types.options.condition.damaged'),
                    ])
                    ->native(false),
                DatePicker::make('schema.data.price_valid_until')
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.product.price_valid_until'))
                    ->helperText(__('filament-atelier::atelier.schema.page_types.fields.product.price_valid_until_help')),
            ],

            'Event' => [
                DateTimePicker::make('schema.data.start')->label(__('filament-atelier::atelier.schema.page_types.fields.event.start'))->required(),
                DateTimePicker::make('schema.data.end')->label(__('filament-atelier::atelier.schema.page_types.fields.event.end')),
                TextInput::make('schema.data.location')
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.event.location'))
                    ->helperText(__('filament-atelier::atelier.schema.page_types.fields.event.location_help')),
                TextInput::make('schema.data.location_address')->label(__('filament-atelier::atelier.schema.page_types.fields.event.location_address')),
                TextInput::make('schema.data.price')->label(__('filament-atelier::atelier.schema.page_types.fields.event.price'))->numeric(),
                TextInput::make('schema.data.currency')->label(__('filament-atelier::atelier.schema.page_types.fields.event.currency'))->maxLength(3),
                Select::make('schema.data.status')
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.event.status'))
                    ->options([
                        'EventScheduled' => __('filament-atelier::atelier.schema.page_types.options.event_status.scheduled'),
                        'EventRescheduled' => __('filament-atelier::atelier.schema.page_types.options.event_status.rescheduled'),
                        'EventPostponed' => __('filament-atelier::atelier.schema.page_types.options.event_status.postponed'),
                        'EventCancelled' => __('filament-atelier::atelier.schema.page_types.options.event_status.cancelled'),
                        'EventMovedOnline' => __('filament-atelier::atelier.schema.page_types.options.event_status.moved_online'),
                    ])
                    ->default('EventScheduled')
                    ->native(false)
                    ->helperText(__('filament-atelier::atelier.schema.page_types.fields.event.status_help')),
                Select::make('schema.data.attendance')
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.event.attendance'))
                    ->options([
                        'OfflineEventAttendanceMode' => __('filament-atelier::atelier.schema.page_types.options.attendance.offline'),
                        'OnlineEventAttendanceMode' => __('filament-atelier::atelier.schema.page_types.options.attendance.online'),
                        'MixedEventAttendanceMode' => __('filament-atelier::atelier.schema.page_types.options.attendance.mixed'),
                    ])
                    ->default('OfflineEventAttendanceMode')
                    ->native(false),
            ],

            'JobPosting' => [
                DatePicker::make('schema.data.posted_at')
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.job_posting.posted_at'))
                    ->helperText(__('filament-atelier::atelier.schema.page_types.fields.job_posting.posted_at_help')),
                DatePicker::make('schema.data.valid_through')
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.job_posting.valid_through'))
                    ->helperText(__('filament-atelier::atelier.schema.page_types.fields.job_posting.valid_through_help')),
                Select::make('schema.data.employment_type')
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.job_posting.employment_type'))
                    ->options([
                        'FULL_TIME' => __('filament-atelier::atelier.schema.page_types.options.employment_type.full_time'),
                        'PART_TIME' => __('filament-atelier::atelier.schema.page_types.options.employment_type.part_time'),
                        'CONTRACTOR' => __('filament-atelier::atelier.schema.page_types.options.employment_type.contractor'),
                        'TEMPORARY' => __('filament-atelier::atelier.schema.page_types.options.employment_type.temporary'),
                        'INTERN' => __('filament-atelier::atelier.schema.page_types.options.employment_type.intern'),
                    ])
                    ->native(false),
                Toggle::make('schema.data.remote')
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.job_posting.remote'))
                    ->helperText(__('filament-atelier::atelier.schema.page_types.fields.job_posting.remote_help')),
                TextInput::make('schema.data.location')
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.job_posting.location'))
                    ->helperText(__('filament-atelier::atelier.schema.page_types.fields.job_posting.location_help')),
                TextInput::make('schema.data.country')
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.job_posting.country'))
                    ->maxLength(2),
                TextInput::make('schema.data.salary')->label(__('filament-atelier::atelier.schema.page_types.fields.job_posting.salary'))->numeric(),
                TextInput::make('schema.data.currency')->label(__('filament-atelier::atelier.schema.page_types.fields.job_posting.currency'))->maxLength(3),
                Select::make('schema.data.salary_unit')
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.job_posting.salary_unit'))
                    ->options([
                        'HOUR' => __('filament-atelier::atelier.schema.page_types.options.salary_unit.hour'),
                        'DAY' => __('filament-atelier::atelier.schema.page_types.options.salary_unit.day'),
                        'WEEK' => __('filament-atelier::atelier.schema.page_types.options.salary_unit.week'),
                        'MONTH' => __('filament-atelier::atelier.schema.page_types.options.salary_unit.month'),
                        'YEAR' => __('filament-atelier::atelier.schema.page_types.options.salary_unit.year'),
                    ])
                    ->default('MONTH')
                    ->native(false),
            ],

            'Person' => [
                TextInput::make('schema.data.name')
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.person.name'))
                    ->helperText(__('filament-atelier::atelier.schema.page_types.fields.person.name_help')),
                TextInput::make('schema.data.job_title')->label(__('filament-atelier::atelier.schema.page_types.fields.person.job_title')),
                TextInput::make('schema.data.same_as')
                    ->label(__('filament-atelier::atelier.schema.page_types.fields.person.same_as'))
                    ->url()
                    ->helperText(__('filament-atelier::atelier.schema.page_types.fields.person.same_as_help')),
            ],

            default => [],
        };
    }
}
