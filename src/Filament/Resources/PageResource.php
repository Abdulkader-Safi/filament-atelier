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
use Filament\Resources\ResourceConfiguration;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Safi\Atelier\AtelierPlugin;
use Safi\Atelier\Filament\Pages\PageEditor;
use Safi\Atelier\Filament\Resources\PageResource\Pages\EditPageSettings;
use Safi\Atelier\Filament\Resources\PageResource\Pages\ListPages;
use Safi\Atelier\LayoutRegistry;
use Safi\Atelier\Models\Page;
use Safi\Atelier\PageType;
use Safi\Atelier\PageTypeRegistry;
use Safi\Atelier\Schema\PageTypes;

/**
 * The page's settings: title, per-locale slugs and SEO. Content is edited in
 * the builder, a full-screen page opened from here.
 *
 * This class is registered once per page type as well as once for ordinary
 * pages, using Filament's resource configurations: same class, one key and
 * one slug each, so /admin/services and /admin/pages are the same code
 * looking at a different `type`. {@see AtelierPlugin::register()}
 * does the registering; everything here reads {@see static::typeKey()} to
 * know which hat it is wearing.
 */
class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $slug = 'pages';

    /** Required before Filament will let the class be registered more than once. */
    protected static ?string $configurationClass = ResourceConfiguration::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = -1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(array_values(array_filter([
            static::titleSection(),
            static::typeSection(),
            static::localeSection(),
            static::structuredDataSection(),
        ])));
    }

    /**
     * Which type this registration serves: a registered page type's key, or
     * `page` for the plain registration.
     *
     * Filament sets the configuration key from the route and keeps it across
     * Livewire requests, so this answers correctly on a table sort as well as
     * on the first load.
     */
    public static function typeKey(): string
    {
        return static::getConfiguration()?->getKey() ?? PageTypeRegistry::DEFAULT;
    }

    /** @return class-string<PageType>|null */
    public static function pageType(): ?string
    {
        return app(PageTypeRegistry::class)->resolve(static::typeKey());
    }

    /**
     * Services never appear under Pages, and Pages never appears under
     * Services.
     *
     * Pages also picks up anything carrying a type nobody registered, which
     * is what happens the day a developer deletes a type class. Those pages
     * still serve publicly, and a page that is live but invisible in the
     * panel is the worst of both. Same reasoning as `Page::layoutView()`
     * falling back rather than throwing.
     */
    public static function getEloquentQuery(): Builder
    {
        $key = static::typeKey();

        if ($key !== PageTypeRegistry::DEFAULT) {
            return parent::getEloquentQuery()->where('type', $key);
        }

        $registered = array_keys(app(PageTypeRegistry::class)->all());

        return parent::getEloquentQuery()->where(fn (Builder $query) => $query
            ->where('type', PageTypeRegistry::DEFAULT)
            ->orWhereNotIn('type', [...$registered, PageTypeRegistry::DEFAULT]));
    }

    public static function getLabel(): ?string
    {
        $type = static::pageType();

        return $type ? $type::label() : parent::getLabel();
    }

    public static function getPluralLabel(): ?string
    {
        $type = static::pageType();

        return $type ? $type::pluralLabel() : parent::getPluralLabel();
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        $type = static::pageType();

        return $type ? $type::icon() : parent::getNavigationIcon();
    }

    public static function getNavigationSort(): ?int
    {
        $type = static::pageType();

        return $type ? $type::navigationSort() ?? parent::getNavigationSort() : parent::getNavigationSort();
    }

    // Form sections --------------------------------------------------------
    //
    // Four methods rather than one long one, because a section that exists
    // only for some types has to be skippable, and because the type section
    // is built from a schema this package has never seen.

    protected static function titleSection(): Section
    {
        // Two facts about the page itself, side by side. Both are shared
        // across locales: the title is an internal name, and a layout is
        // structure, which both locales share by design.
        return Section::make()
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->helperText(__('filament-atelier::atelier.resources.page.fields.title_help')),

                // Hidden entirely when the app registered no layouts, since
                // a select with one option is a question with one answer.
                Select::make('layout')
                    ->label(__('filament-atelier::atelier.resources.page.fields.layout'))
                    ->options(fn () => app(LayoutRegistry::class)->options())
                    ->placeholder(__('filament-atelier::atelier.resources.page.fields.layout_placeholder'))
                    ->helperText(__('filament-atelier::atelier.resources.page.fields.layout_help'))
                    ->native(false)
                    ->visible(fn () => app(LayoutRegistry::class)->options() !== []),
            ])
            ->columns(2)
            ->columnSpanFull();
    }

    protected static function localeSection(): Tabs
    {
        $locales = config('atelier.locales');
        $default = array_key_first($locales);

        return Tabs::make('Locales')
            ->tabs(collect($locales)->map(fn (array $locale, string $code) => Tab::make($locale['label'])->schema([
                TextInput::make("slugs.{$code}")
                    ->label(__('filament-atelier::atelier.resources.page.fields.slug'))
                    ->prefix($code === $default ? '/' : "/{$code}/")
                    ->helperText(__('filament-atelier::atelier.resources.page.fields.slug_help'))
                    ->maxLength(255),

                TextInput::make("seo.{$code}.meta_title")
                    ->label(__('filament-atelier::atelier.resources.page.fields.meta_title'))
                    ->maxLength(70)
                    ->helperText(__('filament-atelier::atelier.resources.page.fields.meta_title_help')),

                Textarea::make("seo.{$code}.meta_description")
                    ->label(__('filament-atelier::atelier.resources.page.fields.meta_description'))
                    ->rows(3)
                    ->maxLength(180)
                    ->helperText(__('filament-atelier::atelier.resources.page.fields.meta_description_help')),

                FileUpload::make("seo.{$code}.og_image")
                    ->label(__('filament-atelier::atelier.resources.page.fields.og_image'))
                    ->image()
                    ->disk(config('atelier.media.disk'))
                    ->directory(config('atelier.media.directory').'/og')
                    // A share image a crawler cannot read is not a share
                    // image. Without this it works on a local disk and
                    // 403s on S3, which is the worst way for it to break.
                    ->visibility('public')
                    ->helperText(__('filament-atelier::atelier.resources.page.fields.og_image_help')),

                Toggle::make("seo.{$code}.noindex")
                    ->label(__('filament-atelier::atelier.resources.page.fields.noindex'))
                    ->helperText(__('filament-atelier::atelier.resources.page.fields.noindex_help')),

                Toggle::make("seo.{$code}.nofollow")
                    ->label(__('filament-atelier::atelier.resources.page.fields.nofollow'))
                    ->helperText(__('filament-atelier::atelier.resources.page.fields.nofollow_help')),

                TextInput::make("seo.{$code}.canonical")
                    ->label(__('filament-atelier::atelier.resources.page.fields.canonical'))
                    ->url()
                    ->helperText(__('filament-atelier::atelier.resources.page.fields.canonical_help')),
            ]))->all())
            ->columnSpanFull();
    }

    /**
     * The type's own properties, from its fields().
     *
     * Untranslated fields are stored at `data.{key}`, translated ones at
     * `data.{locale}.{key}`, which is the shape the SEO column already uses.
     * fields() is called once per locale so each tab gets its own component
     * instances; one instance cannot live in two tabs.
     */
    protected static function typeSection(): ?Section
    {
        $type = static::pageType();

        if ($type === null) {
            return null;
        }

        $translatable = $type::translatable();

        $shared = array_values(array_filter(
            $type::fields(),
            fn (object $field) => ! in_array(static::fieldName($field), $translatable, true),
        ));

        $components = [];

        if ($shared !== []) {
            $components[] = Group::make($shared)->statePath('data')->columns(2)->columnSpanFull();
        }

        if ($translatable !== []) {
            $components[] = static::localeTabs(fn (string $code) => [
                Group::make(array_values(array_filter(
                    $type::fields(),
                    fn (object $field) => in_array(static::fieldName($field), $translatable, true),
                )))
                    ->statePath("data.{$code}")
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
        }

        if ($components === []) {
            return null;
        }

        return Section::make(__('filament-atelier::atelier.resources.page.type_section_heading', ['type' => $type::label()]))
            ->schema($components)
            ->columns(2)
            ->columnSpanFull();
    }

    /** A component's field name, or null for one that has none (a layout component). */
    protected static function fieldName(object $field): ?string
    {
        return method_exists($field, 'getName') ? $field->getName() : null;
    }

    protected static function structuredDataSection(): Section
    {
        // Last on the screen, because it is the least often touched thing on
        // it and the answer is Standard page for most pages. Page-level, not
        // per locale: a page that is a Service in English is a Service in
        // Arabic. A page type that names one picks the answer already.
        return Section::make(__('filament-atelier::atelier.resources.page.sections.structured_data.heading'))
            ->description(__('filament-atelier::atelier.resources.page.sections.structured_data.description'))
            ->schema([
                Select::make('schema.type')
                    // Not "page type": that phrase belongs to the sidebar
                    // now, and two different questions under one name is how
                    // a client picks the wrong one.
                    ->label(__('filament-atelier::atelier.resources.page.fields.schema_type'))
                    ->options(PageTypes::options())
                    ->default(fn () => ($type = static::pageType()) ? ($type::schemaType() ?? 'WebPage') : 'WebPage')
                    ->native(false)
                    ->live()
                    ->helperText(__('filament-atelier::atelier.resources.page.fields.schema_type_help'))
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
                        Tab::make(__('filament-atelier::atelier.resources.page.tabs.faq'))
                            ->badge(fn (callable $get) => self::countFaq($get('schema.faq')) ?: null)
                            ->schema([self::localeTabs(fn (string $code) => [
                                Repeater::make("schema.faq.{$code}")
                                    ->label(__('filament-atelier::atelier.resources.page.fields.faq_questions'))
                                    ->schema([
                                        TextInput::make('question')->label(__('filament-atelier::atelier.resources.page.fields.faq_question'))->required(),
                                        Textarea::make('answer')->label(__('filament-atelier::atelier.resources.page.fields.faq_answer'))->rows(2)->required(),
                                    ])
                                    ->itemLabel(fn (array $state) => $state['question'] ?? __('filament-atelier::atelier.resources.page.fields.faq_question'))
                                    ->collapsed()
                                    ->defaultItems(0)
                                    ->addActionLabel(__('filament-atelier::atelier.resources.page.fields.faq_add'))
                                    ->helperText(__('filament-atelier::atelier.resources.page.fields.faq_help'))
                                    ->columnSpanFull(),
                            ])])
                            ->columns(1),

                        Tab::make(__('filament-atelier::atelier.resources.page.tabs.breadcrumbs'))
                            ->schema([
                                Select::make('schema.breadcrumbs.mode')
                                    ->label(__('filament-atelier::atelier.resources.page.fields.breadcrumbs_mode'))
                                    ->options([
                                        'auto' => __('filament-atelier::atelier.resources.page.options.breadcrumbs_mode.auto'),
                                        'custom' => __('filament-atelier::atelier.resources.page.options.breadcrumbs_mode.custom'),
                                        'none' => __('filament-atelier::atelier.resources.page.options.breadcrumbs_mode.none'),
                                    ])
                                    ->default('auto')
                                    ->native(false)
                                    ->live()
                                    ->helperText(__('filament-atelier::atelier.resources.page.fields.breadcrumbs_mode_help'))
                                    ->columnSpanFull(),

                                Group::make([self::localeTabs(fn (string $code) => [
                                    Repeater::make("schema.breadcrumbs.items.{$code}")
                                        ->label(__('filament-atelier::atelier.resources.page.fields.breadcrumbs_trail'))
                                        ->schema([
                                            TextInput::make('name')->label(__('filament-atelier::atelier.resources.page.fields.breadcrumb_name'))->required(),
                                            TextInput::make('url')
                                                ->label(__('filament-atelier::atelier.resources.page.fields.breadcrumb_url'))
                                                ->helperText(__('filament-atelier::atelier.resources.page.fields.breadcrumb_url_help')),
                                        ])
                                        ->itemLabel(fn (array $state) => $state['name'] ?? __('filament-atelier::atelier.resources.page.fields.breadcrumb_step_fallback'))
                                        ->defaultItems(0)
                                        ->addActionLabel(__('filament-atelier::atelier.resources.page.fields.breadcrumbs_add'))
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
            ->columnSpanFull();
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
                        ? __('filament-atelier::atelier.resources.page.table.status_unpublished_changes')
                        : ucfirst($record->status))
                    ->color(fn (Page $record) => match (true) {
                        $record->hasUnpublishedChanges() => 'warning',
                        $record->isPublished() => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('updated_at')
                    ->label(__('filament-atelier::atelier.resources.page.table.last_edited'))
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                Action::make('build')
                    ->label(__('filament-atelier::atelier.resources.page.table.open_builder'))
                    ->icon('heroicon-m-paint-brush')
                    ->url(fn (Page $record) => PageEditor::getUrl(['record' => $record->getKey()]))
                    ->openUrlInNewTab(),

                DeleteAction::make(),
            ])
            ->emptyStateHeading(__('filament-atelier::atelier.resources.page.table.empty_heading'))
            ->emptyStateDescription(__('filament-atelier::atelier.resources.page.table.empty_description'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'edit' => EditPageSettings::route('/{record}/edit'),
        ];
    }
}
