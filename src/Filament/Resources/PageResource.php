<?php

declare(strict_types=1);

namespace Safi\Atelier\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Safi\Atelier\Filament\Pages\PageEditor;
use Safi\Atelier\Filament\Resources\PageResource\Pages\EditPageSettings;
use Safi\Atelier\Filament\Resources\PageResource\Pages\ListPages;
use Safi\Atelier\Models\Page;

/**
 * The page's settings: title, per-locale slugs and SEO. Content is edited in
 * the builder, a full-screen page opened from here.
 */
class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $slug = 'pages';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = -1;

    public static function form(Schema $schema): Schema
    {
        $locales = config('atelier.locales');
        $default = array_key_first($locales);

        return $schema->components([
            Section::make()->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Internal name, and the fallback for the meta title.'),
            ]),

            Tabs::make('Locales')
                ->tabs(collect($locales)->map(fn (array $locale, string $code) => Tab::make($locale['label'])->schema([
                    TextInput::make("slugs.{$code}")
                        ->label('Slug')
                        ->prefix($code === $default ? '/' : "/{$code}/")
                        ->helperText('Leave empty to generate one from the title.')
                        ->maxLength(255),

                    TextInput::make("seo.{$code}.meta_title")
                        ->label('Meta title')
                        ->maxLength(70)
                        ->helperText('Around 60 characters. Falls back to the page title.'),

                    Textarea::make("seo.{$code}.meta_description")
                        ->label('Meta description')
                        ->rows(3)
                        ->maxLength(180)
                        ->helperText('Around 155 characters.'),

                    FileUpload::make("seo.{$code}.og_image")
                        ->label('Social share image')
                        ->image()
                        ->disk(config('atelier.media.disk'))
                        ->directory(config('atelier.media.directory').'/og')
                        // A share image a crawler cannot read is not a share
                        // image. Without this it works on a local disk and
                        // 403s on S3, which is the worst way for it to break.
                        ->visibility('public')
                        ->helperText('1200 by 630 is the safe size.'),

                    Toggle::make("seo.{$code}.noindex")
                        ->label('Hide from search engines')
                        ->helperText('Adds a noindex tag and drops the page from the sitemap. The page stays public.'),

                    Toggle::make("seo.{$code}.nofollow")
                        ->label('Tell search engines not to follow its links')
                        ->helperText('Independent of the above. A page can be indexed and still not pass link credit.'),

                    TextInput::make("seo.{$code}.canonical")
                        ->label('Canonical URL')
                        ->url()
                        ->helperText("Leave empty to use this page's own URL."),
                ]))->all())
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $default = array_key_first(config('atelier.locales'));

        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn (Page $record) => ($slug = $record->slug($default)) ? "/{$slug}" : null),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (Page $record) => $record->hasUnpublishedChanges()
                        ? 'Unpublished changes'
                        : ucfirst($record->status))
                    ->color(fn (Page $record) => match (true) {
                        $record->hasUnpublishedChanges() => 'warning',
                        $record->isPublished() => 'success',
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
            ->emptyStateHeading('No pages yet')
            ->emptyStateDescription('Create one, fill in its settings, then build it from sections.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'edit' => EditPageSettings::route('/{record}/edit'),
        ];
    }
}
