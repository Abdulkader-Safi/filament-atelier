<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\EnquiryResource\Pages\EditEnquiry;
use App\Filament\Resources\EnquiryResource\Pages\ListEnquiries;
use App\Models\Enquiry;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * What was asked for, and what came of it. No payment gateway: a request is
 * a lead, and the client works it by hand.
 */
class EnquiryResource extends Resource
{
    protected static ?string $model = Enquiry::class;

    protected static ?string $slug = 'requests';

    protected static ?string $label = 'request';

    protected static ?string $pluralLabel = 'Requests';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?int $navigationSort = 5;

    /** A count on the sidebar, so nobody has to remember to look. */
    public static function getNavigationBadge(): ?string
    {
        return (string) Enquiry::query()->where('status', 'new')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('What they asked for')
                ->schema([
                    TextInput::make('page_title')->label('Page')->disabled(),
                    TextInput::make('option')->label('Tier or variation')->disabled(),
                    TextInput::make('quantity')->disabled(),
                    TextInput::make('total')->prefix('AED')->disabled(),
                ])
                ->columns(2),

            Section::make('Who asked')
                ->schema([
                    TextInput::make('name')->disabled(),
                    TextInput::make('email')->disabled(),
                    TextInput::make('phone')->disabled(),
                    Textarea::make('message')->rows(4)->disabled()->columnSpanFull(),
                ])
                ->columns(2),

            // The only editable thing on the screen. A request is a record of
            // what a stranger typed; rewriting it would make it evidence of
            // nothing.
            Section::make('Where it got to')
                ->schema([
                    Select::make('status')
                        ->options(Enquiry::STATUSES)
                        ->required()
                        ->native(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('When')->since()->sortable(),

                TextColumn::make('kind')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Enquiry::KINDS[$state] ?? $state)
                    ->color(fn (string $state) => $state === 'product' ? 'info' : 'primary'),

                TextColumn::make('page_title')
                    ->label('What')
                    ->searchable()
                    ->description(fn (Enquiry $record) => $record->option),

                TextColumn::make('quantity')->label('Qty')->alignEnd(),

                TextColumn::make('total')
                    ->money('AED')
                    ->alignEnd()
                    ->sortable()
                    ->summarize(Sum::make()->money('AED')),

                TextColumn::make('name')->searchable()->description(fn (Enquiry $record) => $record->email),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Enquiry::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'won' => 'success',
                        'lost' => 'danger',
                        'contacted' => 'info',
                        default => 'warning',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(Enquiry::STATUSES),
            ])
            ->recordActions([EditAction::make()])
            ->emptyStateHeading('No requests yet')
            ->emptyStateDescription('They arrive from the forms on the services and products pages.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEnquiries::route('/'),
            'edit' => EditEnquiry::route('/{record}/edit'),
        ];
    }

    /** Nothing in the panel creates one. They arrive from the public site. */
    public static function canCreate(): bool
    {
        return false;
    }
}
