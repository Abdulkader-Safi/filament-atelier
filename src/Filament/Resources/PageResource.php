<?php

declare(strict_types=1);

namespace Safi\Atelier\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Safi\Atelier\Filament\Pages\PageEditor;
use Safi\Atelier\Filament\Resources\PageResource\Pages\EditPageSettings;
use Safi\Atelier\Filament\Resources\PageResource\Pages\ListPages;
use Safi\Atelier\LayoutRegistry;
use Safi\Atelier\Models\Page;
use Safi\Atelier\Schema\PageTypes;

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
            // Two facts about the page itself, side by side. Both are shared
            // across locales: the title is an internal name, and a layout is
            // structure, which both locales share by design.
            Section::make()
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Internal name, and the fallback for the meta title.'),

                    // Hidden entirely when the app registered no layouts, since
                    // a select with one option is a question with one answer.
                    Select::make('layout')
                        ->label('Layout')
                        ->options(fn () => app(LayoutRegistry::class)->options())
                        ->placeholder('Default')
                        ->helperText('The shell wrapped around this page. Leave as Default to use the site-wide one.')
                        ->native(false)
                        ->visible(fn () => app(LayoutRegistry::class)->options() !== []),
                ])
                ->columns(2)
                ->columnSpanFull(),

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

            // Below the per-locale fields, because it is the least often
            // touched thing on the screen and the answer is Standard page for
            // most of them. Page-level, not per locale: a page that is a
            // Service in English is a Service in Arabic.
            Section::make('Structured data')
                ->description('What this page is, for search engines. It becomes the JSON-LD in the head.')
                ->schema([
                    Select::make('schema.type')
                        ->label('Page type')
                        ->options(PageTypes::options())
                        ->default('WebPage')
                        ->native(false)
                        ->live()
                        ->helperText('Standard page is right for most.')
                        ->columnSpanFull(),

                    // Only the chosen type's fields, and nothing at all for the
                    // types that need none.
                    Group::make()
                        ->schema(fn (callable $get) => PageTypes::fields($get('schema.type') ?? 'WebPage'))
                        ->columns(2)
                        ->columnSpanFull(),

                    // Schema typed here rather than derived from the page's
                    // blocks. A page can carry FAQ or breadcrumb data whatever
                    // it is built from, including nothing.
                    // The safety net for blocks that do not describe
                    // themselves. A block can generate its own schema, but
                    // most blocks are written by whoever installed this, and
                    // nobody should have to edit a PHP class to get an FAQ
                    // into the head.
                    Tabs::make('Schema')
                        ->tabs([
                            Tab::make('FAQ')
                                ->badge(fn (callable $get) => self::countFaq($get('schema.faq')) ?: null)
                                ->schema([self::localeTabs(fn (string $code) => [
                                    Repeater::make("schema.faq.{$code}")
                                        ->label('Questions')
                                        ->schema([
                                            TextInput::make('question')->required(),
                                            Textarea::make('answer')->rows(2)->required(),
                                        ])
                                        ->itemLabel(fn (array $state) => $state['question'] ?? 'Question')
                                        ->collapsed()
                                        ->defaultItems(0)
                                        ->addActionLabel('Add a question')
                                        ->helperText('Type them here when nothing on the page generates them. The answers should still be somewhere a visitor can read, in prose or anywhere else; a question that appears nowhere on the page is against the search engines\' own rules.')
                                        ->columnSpanFull(),
                                ])])
                                ->columns(1),

                            Tab::make('Breadcrumbs')
                                ->schema([
                                    Select::make('schema.breadcrumbs.mode')
                                        ->label('Trail')
                                        ->options([
                                            'auto' => 'From the slug path',
                                            'custom' => 'Typed here',
                                            'none' => 'None',
                                        ])
                                        ->default('auto')
                                        ->native(false)
                                        ->live()
                                        ->helperText('From the slug path builds Home › Services › This page for a page at services/this-page.')
                                        ->columnSpanFull(),

                                    Group::make([self::localeTabs(fn (string $code) => [
                                        Repeater::make("schema.breadcrumbs.items.{$code}")
                                            ->label('Trail')
                                            ->schema([
                                                TextInput::make('name')->required(),
                                                TextInput::make('url')
                                                    ->helperText('Leave the last one empty to use this page.'),
                                            ])
                                            ->itemLabel(fn (array $state) => $state['name'] ?? 'Step')
                                            ->defaultItems(0)
                                            ->addActionLabel('Add a step')
                                            ->columnSpanFull(),
                                    ])])
                                        ->visible(fn (callable $get) => $get('schema.breadcrumbs.mode') === 'custom')
                                        ->columnSpanFull(),
                                ])
                                ->columns(1),
                        ])
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    /**
     * The same fields once per locale.
     *
     * Schema carries text, and text is translated, so anything typed by hand
     * needs the same per-locale treatment the meta fields get. Nested inside
     * the schema tabs rather than moved up to the page's own locale tabs,
     * because the type it belongs to is the thing being edited.
     *
     * @param  \Closure(string): array<int, mixed>  $fields
     */
    protected static function localeTabs(\Closure $fields): Tabs
    {
        return Tabs::make('Locales')
            ->tabs(collect(config('atelier.locales', []))
                ->map(fn (array $locale, string $code) => Tab::make($locale['label'])->schema($fields($code)))
                ->all())
            ->columnSpanFull();
    }

    /** Total questions across every locale, for the tab badge. */
    protected static function countFaq(mixed $faq): int
    {
        return collect(is_array($faq) ? $faq : [])
            ->flatten(1)
            ->filter(fn (mixed $item) => is_array($item) && filled($item['question'] ?? null))
            ->count();
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
