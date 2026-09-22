<?php

declare(strict_types=1);

namespace Safi\Atelier\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page as FilamentPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Safi\Atelier\Media;
use Safi\Atelier\Models\SiteSettings;

/**
 * Who publishes this site, and how to reach them.
 *
 * Everything here feeds the structured data on every page, which is why it is
 * a screen rather than config: an address or a phone number changes without a
 * deploy, and the person who knows it is the client, not the developer.
 */
class SiteDetails extends FilamentPage
{
    protected string $view = 'atelier::filament.pages.site-details';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 90;

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('filament-atelier::atelier.pages.site_details.nav_label');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('filament-atelier::atelier.navigation_groups.settings');
    }

    public function mount(): void
    {
        $this->form->fill(SiteSettings::current()->data ?? []);
    }

    public function getTitle(): string
    {
        return __('filament-atelier::atelier.pages.site_details.title');
    }

    public function getSubheading(): ?string
    {
        return __('filament-atelier::atelier.pages.site_details.subheading');
    }

    public function form(Schema $schema): Schema
    {
        $locales = config('atelier.locales', []);

        return $schema
            ->components([
                Section::make(__('filament-atelier::atelier.pages.site_details.sections.identity.heading'))
                    ->description(__('filament-atelier::atelier.pages.site_details.sections.identity.description'))
                    ->schema([
                        Tabs::make('Names')
                            ->tabs(collect($locales)->map(fn (array $locale, string $code) => Tab::make($locale['label'])->schema([
                                TextInput::make("name.{$code}")
                                    ->label(__('filament-atelier::atelier.pages.site_details.fields.name'))
                                    ->maxLength(255),

                                Textarea::make("description.{$code}")
                                    ->label(__('filament-atelier::atelier.pages.site_details.fields.description'))
                                    ->rows(2)
                                    ->maxLength(500),
                            ]))->all())
                            ->columnSpanFull(),

                        TextInput::make('legal_name')
                            ->label(__('filament-atelier::atelier.pages.site_details.fields.legal_name'))
                            ->helperText(__('filament-atelier::atelier.pages.site_details.fields.legal_name_help'))
                            ->maxLength(255),

                        Select::make('type')
                            ->label(__('filament-atelier::atelier.pages.site_details.fields.type'))
                            ->options(self::types())
                            ->default('Organization')
                            ->native(false)
                            ->live()
                            ->helperText(__('filament-atelier::atelier.pages.site_details.fields.type_help')),

                        Media::upload('logo', __('filament-atelier::atelier.pages.site_details.fields.logo'))
                            ->helperText(__('filament-atelier::atelier.pages.site_details.fields.logo_help')),
                    ])->columns(2),

                Section::make(__('filament-atelier::atelier.pages.site_details.sections.profiles.heading'))
                    ->description(__('filament-atelier::atelier.pages.site_details.sections.profiles.description'))
                    ->schema([
                        Repeater::make('same_as')
                            ->label(__('filament-atelier::atelier.pages.site_details.fields.same_as'))
                            ->simple(
                                TextInput::make('url')
                                    ->url()
                                    ->placeholder('https://www.linkedin.com/company/...')
                                    ->required(),
                            )
                            ->addActionLabel(__('filament-atelier::atelier.pages.site_details.fields.same_as_add'))
                            ->reorderable(false)
                            ->defaultItems(0),
                    ]),

                Section::make(__('filament-atelier::atelier.pages.site_details.sections.contact.heading'))
                    ->description(__('filament-atelier::atelier.pages.site_details.sections.contact.description'))
                    ->schema([
                        TextInput::make('telephone')->label(__('filament-atelier::atelier.pages.site_details.fields.telephone'))->tel(),
                        TextInput::make('email')->label(__('filament-atelier::atelier.pages.site_details.fields.email'))->email(),

                        TextInput::make('address.street')->label(__('filament-atelier::atelier.pages.site_details.fields.address_street')),
                        TextInput::make('address.locality')->label(__('filament-atelier::atelier.pages.site_details.fields.address_locality')),
                        TextInput::make('address.region')->label(__('filament-atelier::atelier.pages.site_details.fields.address_region')),
                        TextInput::make('address.postal_code')->label(__('filament-atelier::atelier.pages.site_details.fields.address_postal_code')),
                        TextInput::make('address.country')
                            ->label(__('filament-atelier::atelier.pages.site_details.fields.address_country'))
                            ->helperText(__('filament-atelier::atelier.pages.site_details.fields.address_country_help'))
                            ->maxLength(2),

                        TextInput::make('geo.latitude')->label(__('filament-atelier::atelier.pages.site_details.fields.latitude'))->numeric(),
                        TextInput::make('geo.longitude')->label(__('filament-atelier::atelier.pages.site_details.fields.longitude'))->numeric(),

                        TextInput::make('price_range')
                            ->label(__('filament-atelier::atelier.pages.site_details.fields.price_range'))
                            ->helperText(__('filament-atelier::atelier.pages.site_details.fields.price_range_help')),

                        TextInput::make('area_served')
                            ->label(__('filament-atelier::atelier.pages.site_details.fields.area_served'))
                            ->helperText(__('filament-atelier::atelier.pages.site_details.fields.area_served_help')),

                        // The single most looked-at fact in a local search
                        // result, and the one most sites never mark up.
                        Repeater::make('opening_hours')
                            ->label(__('filament-atelier::atelier.pages.site_details.fields.opening_hours'))
                            ->schema([
                                CheckboxList::make('days')
                                    ->label(__('filament-atelier::atelier.pages.site_details.fields.opening_hours_days'))
                                    ->options(self::days())
                                    ->columns(4)
                                    ->required()
                                    ->columnSpanFull(),
                                TimePicker::make('opens')->label(__('filament-atelier::atelier.pages.site_details.fields.opens'))->seconds(false)->required(),
                                TimePicker::make('closes')->label(__('filament-atelier::atelier.pages.site_details.fields.closes'))->seconds(false)->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel(__('filament-atelier::atelier.pages.site_details.fields.opening_hours_add'))
                            ->itemLabel(fn (array $state) => self::hoursLabel($state))
                            ->helperText(__('filament-atelier::atelier.pages.site_details.fields.opening_hours_help'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn (callable $get) => $get('type') !== 'Organization'),

                Section::make(__('filament-atelier::atelier.pages.site_details.sections.contact_points.heading'))
                    ->description(__('filament-atelier::atelier.pages.site_details.sections.contact_points.description'))
                    ->schema([
                        Repeater::make('contact_points')
                            ->label(__('filament-atelier::atelier.pages.site_details.fields.contact_points'))
                            ->schema([
                                Select::make('type')
                                    ->label(__('filament-atelier::atelier.pages.site_details.fields.contact_point_for'))
                                    ->options([
                                        'customer support' => __('filament-atelier::atelier.pages.site_details.options.contact_point_for.customer_support'),
                                        'sales' => __('filament-atelier::atelier.pages.site_details.options.contact_point_for.sales'),
                                        'billing support' => __('filament-atelier::atelier.pages.site_details.options.contact_point_for.billing_support'),
                                        'technical support' => __('filament-atelier::atelier.pages.site_details.options.contact_point_for.technical_support'),
                                        'reservations' => __('filament-atelier::atelier.pages.site_details.options.contact_point_for.reservations'),
                                    ])
                                    ->native(false)
                                    ->required(),
                                TextInput::make('telephone')->label(__('filament-atelier::atelier.pages.site_details.fields.telephone'))->tel(),
                                TextInput::make('email')->label(__('filament-atelier::atelier.pages.site_details.fields.email'))->email(),
                                TextInput::make('languages')
                                    ->label(__('filament-atelier::atelier.pages.site_details.fields.contact_point_languages'))
                                    ->helperText(__('filament-atelier::atelier.pages.site_details.fields.contact_point_languages_help')),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel(__('filament-atelier::atelier.pages.site_details.fields.contact_points_add'))
                            ->itemLabel(fn (array $state) => $state['type'] ?? __('filament-atelier::atelier.pages.site_details.fields.contact_point_fallback'))
                            ->columnSpanFull(),
                    ]),

                Section::make(__('filament-atelier::atelier.pages.site_details.sections.legal.heading'))
                    ->description(__('filament-atelier::atelier.pages.site_details.sections.legal.description'))
                    ->schema([
                        DatePicker::make('founding_date')->label(__('filament-atelier::atelier.pages.site_details.fields.founding_date')),
                        TextInput::make('employees')->label(__('filament-atelier::atelier.pages.site_details.fields.employees'))->numeric(),
                        TextInput::make('vat_id')->label(__('filament-atelier::atelier.pages.site_details.fields.vat_id')),
                        TextInput::make('tax_id')->label(__('filament-atelier::atelier.pages.site_details.fields.tax_id')),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-atelier::atelier.pages.site_details.save_action'))
                ->action('save'),
        ];
    }

    public function save(): void
    {
        SiteSettings::current()->update(['data' => $this->form->getState()]);

        Notification::make()->title(__('filament-atelier::atelier.pages.site_details.saved_notification'))->success()->send();
    }

    /** @return array<string, string> */
    public static function days(): array
    {
        return [
            'Monday' => __('filament-atelier::atelier.pages.site_details.days.monday'),
            'Tuesday' => __('filament-atelier::atelier.pages.site_details.days.tuesday'),
            'Wednesday' => __('filament-atelier::atelier.pages.site_details.days.wednesday'),
            'Thursday' => __('filament-atelier::atelier.pages.site_details.days.thursday'),
            'Friday' => __('filament-atelier::atelier.pages.site_details.days.friday'),
            'Saturday' => __('filament-atelier::atelier.pages.site_details.days.saturday'),
            'Sunday' => __('filament-atelier::atelier.pages.site_details.days.sunday'),
        ];
    }

    /** "Mon, Tue, Wed 09:00 to 18:00", for the collapsed repeater row. */
    protected static function hoursLabel(array $state): string
    {
        $days = collect($state['days'] ?? [])
            ->map(fn (string $day) => self::days()[$day] ?? $day)
            ->implode(', ');

        $hours = trim(($state['opens'] ?? '').' to '.($state['closes'] ?? ''), ' to');

        return trim($days.' '.$hours) ?: __('filament-atelier::atelier.pages.site_details.fields.opening_hours_fallback');
    }

    /**
     * The subset of schema.org types worth offering.
     *
     * Not the full list, which runs to hundreds and would be a worse question
     * than no question. LocalBusiness and the subtypes a client site actually
     * is.
     *
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            'Organization' => __('filament-atelier::atelier.pages.site_details.types.organization'),
            'LocalBusiness' => __('filament-atelier::atelier.pages.site_details.types.local_business'),
            'ProfessionalService' => __('filament-atelier::atelier.pages.site_details.types.professional_service'),
            'Store' => __('filament-atelier::atelier.pages.site_details.types.store'),
            'Restaurant' => __('filament-atelier::atelier.pages.site_details.types.restaurant'),
            'MedicalBusiness' => __('filament-atelier::atelier.pages.site_details.types.medical_business'),
            'RealEstateAgent' => __('filament-atelier::atelier.pages.site_details.types.real_estate_agent'),
            'AutoRepair' => __('filament-atelier::atelier.pages.site_details.types.auto_repair'),
            'HomeAndConstructionBusiness' => __('filament-atelier::atelier.pages.site_details.types.home_and_construction_business'),
            'EducationalOrganization' => __('filament-atelier::atelier.pages.site_details.types.educational_organization'),
        ];
    }
}
