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

    protected static ?string $navigationLabel = 'Site details';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 90;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(SiteSettings::current()->data ?? []);
    }

    public function getTitle(): string
    {
        return 'Site details';
    }

    public function getSubheading(): ?string
    {
        return 'The organisation behind the site. Used in the structured data on every page.';
    }

    public function form(Schema $schema): Schema
    {
        $locales = config('atelier.locales', []);

        return $schema
            ->components([
                Section::make('Identity')
                    ->description('Who the site belongs to. Leave the type as Organization unless the client has premises a customer can visit.')
                    ->schema([
                        Tabs::make('Names')
                            ->tabs(collect($locales)->map(fn (array $locale, string $code) => Tab::make($locale['label'])->schema([
                                TextInput::make("name.{$code}")
                                    ->label('Name')
                                    ->maxLength(255),

                                Textarea::make("description.{$code}")
                                    ->label('Description')
                                    ->rows(2)
                                    ->maxLength(500),
                            ]))->all())
                            ->columnSpanFull(),

                        TextInput::make('legal_name')
                            ->label('Legal name')
                            ->helperText('Only if it differs from the trading name.')
                            ->maxLength(255),

                        Select::make('type')
                            ->label('Type')
                            ->options(self::types())
                            ->default('Organization')
                            ->native(false)
                            ->live()
                            ->helperText('A LocalBusiness type unlocks the contact details below, and is what puts a client on the map.'),

                        Media::upload('logo', 'Logo')
                            ->helperText('Square or wide, at least 112px tall. Used as the organisation logo in search results.'),
                    ])->columns(2),

                Section::make('Profiles')
                    ->description('Social and directory URLs. They become sameAs, which is how a search engine ties this site to accounts it already knows.')
                    ->schema([
                        Repeater::make('same_as')
                            ->label('Profile URLs')
                            ->simple(
                                TextInput::make('url')
                                    ->url()
                                    ->placeholder('https://www.linkedin.com/company/...')
                                    ->required(),
                            )
                            ->addActionLabel('Add a profile')
                            ->reorderable(false)
                            ->defaultItems(0),
                    ]),

                Section::make('Contact')
                    ->description('Shown to search engines, not on the site. Nothing here is rendered into a page.')
                    ->schema([
                        TextInput::make('telephone')->label('Telephone')->tel(),
                        TextInput::make('email')->label('Email')->email(),

                        TextInput::make('address.street')->label('Street address'),
                        TextInput::make('address.locality')->label('City'),
                        TextInput::make('address.region')->label('Region or emirate'),
                        TextInput::make('address.postal_code')->label('Postal code'),
                        TextInput::make('address.country')
                            ->label('Country code')
                            ->helperText('Two letters, e.g. AE, SA, LB.')
                            ->maxLength(2),

                        TextInput::make('geo.latitude')->label('Latitude')->numeric(),
                        TextInput::make('geo.longitude')->label('Longitude')->numeric(),

                        TextInput::make('price_range')
                            ->label('Price range')
                            ->helperText('Free text, e.g. $$ or 500 to 5000 AED.'),

                        TextInput::make('area_served')
                            ->label('Areas served')
                            ->helperText('Comma separated, e.g. Dubai, Abu Dhabi.'),

                        // The single most looked-at fact in a local search
                        // result, and the one most sites never mark up.
                        Repeater::make('opening_hours')
                            ->label('Opening hours')
                            ->schema([
                                CheckboxList::make('days')
                                    ->label('Days')
                                    ->options(self::days())
                                    ->columns(4)
                                    ->required()
                                    ->columnSpanFull(),
                                TimePicker::make('opens')->label('Opens')->seconds(false)->required(),
                                TimePicker::make('closes')->label('Closes')->seconds(false)->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Add hours')
                            ->itemLabel(fn (array $state) => self::hoursLabel($state))
                            ->helperText('One row per set of hours. Group the days that share them, and add a second row for the days that differ.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn (callable $get) => $get('type') !== 'Organization'),

                Section::make('Contact points')
                    ->description('Who answers, and in which language. A bare telephone number says none of that.')
                    ->schema([
                        Repeater::make('contact_points')
                            ->label('Contact points')
                            ->schema([
                                Select::make('type')
                                    ->label('For')
                                    ->options([
                                        'customer support' => 'Customer support',
                                        'sales' => 'Sales',
                                        'billing support' => 'Billing',
                                        'technical support' => 'Technical support',
                                        'reservations' => 'Reservations',
                                    ])
                                    ->native(false)
                                    ->required(),
                                TextInput::make('telephone')->label('Telephone')->tel(),
                                TextInput::make('email')->label('Email')->email(),
                                TextInput::make('languages')
                                    ->label('Languages')
                                    ->helperText('Comma separated, e.g. Arabic, English.'),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Add a contact point')
                            ->itemLabel(fn (array $state) => $state['type'] ?? 'Contact point')
                            ->columnSpanFull(),
                    ]),

                Section::make('Legal')
                    ->description('Optional, and a trust signal for a business search.')
                    ->schema([
                        DatePicker::make('founding_date')->label('Founded'),
                        TextInput::make('employees')->label('Employees')->numeric(),
                        TextInput::make('vat_id')->label('VAT number'),
                        TextInput::make('tax_id')->label('Tax number'),
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
                ->label('Save changes')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        SiteSettings::current()->update(['data' => $this->form->getState()]);

        Notification::make()->title('Saved')->success()->send();
    }

    /** @return array<string, string> */
    public static function days(): array
    {
        return [
            'Monday' => 'Mon',
            'Tuesday' => 'Tue',
            'Wednesday' => 'Wed',
            'Thursday' => 'Thu',
            'Friday' => 'Fri',
            'Saturday' => 'Sat',
            'Sunday' => 'Sun',
        ];
    }

    /** "Mon, Tue, Wed 09:00 to 18:00", for the collapsed repeater row. */
    protected static function hoursLabel(array $state): string
    {
        $days = collect($state['days'] ?? [])
            ->map(fn (string $day) => self::days()[$day] ?? $day)
            ->implode(', ');

        $hours = trim(($state['opens'] ?? '').' to '.($state['closes'] ?? ''), ' to');

        return trim($days.' '.$hours) ?: 'Hours';
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
            'Organization' => 'Organization (no physical premises)',
            'LocalBusiness' => 'Local business',
            'ProfessionalService' => 'Professional service',
            'Store' => 'Shop',
            'Restaurant' => 'Restaurant or cafe',
            'MedicalBusiness' => 'Clinic or medical practice',
            'RealEstateAgent' => 'Real estate agency',
            'AutoRepair' => 'Garage or auto repair',
            'HomeAndConstructionBusiness' => 'Construction or trades',
            'EducationalOrganization' => 'School or training provider',
        ];
    }
}
