<?php

declare(strict_types=1);

namespace Safi\Atelier\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Safi\Atelier\Filament\Pages\PageEditor;
use Safi\Atelier\Filament\Resources\PageResource\Pages\ListPages;
use Safi\Atelier\Models\Page;

/**
 * List, create and delete, plus the page's own settings. Building the page
 * happens in the builder, which is a full-screen page outside the panel
 * chrome, opened from here.
 */
class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $slug = 'pages';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = -1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->required()
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->weight('medium'),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (Page $record) => $record->hasUnpublishedChanges()
                        ? 'Unpublished changes'
                        : ucfirst($record->status))
                    ->color(fn (Page $record) => match (true) {
                        $record->hasUnpublishedChanges() => 'warning',
                        $record->status === 'published' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('updated_at')
                    ->label('Last edited')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                Action::make('build')
                    ->label('Open builder')
                    ->icon('heroicon-m-paint-brush')
                    ->url(fn (Page $record) => PageEditor::getUrl(['record' => $record->getKey()]))
                    ->openUrlInNewTab(),

                DeleteAction::make(),
            ])
            ->recordUrl(fn (Page $record) => PageEditor::getUrl(['record' => $record->getKey()]))
            ->emptyStateHeading('No pages yet')
            ->emptyStateDescription('Create one, then build it from sections.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
        ];
    }
}
