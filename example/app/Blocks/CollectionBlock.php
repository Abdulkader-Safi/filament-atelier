<?php

declare(strict_types=1);

namespace App\Blocks;

use App\PageTypes\ProductType;
use App\PageTypes\ServiceType;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Collection;
use Safi\Atelier\Blocks\BaseBlock;
use Safi\Atelier\Models\Page;

/**
 * Lists services or products, each rendered through its own card view.
 *
 * The one block that reaches outside its own attributes: it queries pages of
 * a type and hands each one to `resources/views/{services,products}/card`.
 * That is why a service's price only exists in one place, and why adding a
 * service makes it appear on the homepage with nobody editing the homepage.
 *
 * The package ships an equivalent. This one is here so the whole site can be
 * read without opening the package, and because a real project usually wants
 * its own query rules eventually.
 */
class CollectionBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'collection';
    }

    public static function label(): string
    {
        return 'Services or products';
    }

    public static function icon(): string
    {
        return 'heroicon-o-rectangle-stack';
    }

    public static function category(): string
    {
        return 'Sections';
    }

    public static function supports(): array
    {
        return ['background', 'padding'];
    }

    public static function translatable(): array
    {
        return ['heading', 'empty'];
    }

    public static function defaults(): array
    {
        return [
            'page_type' => 'service',
            'source' => 'all',
            'order' => 'title',
            'columns' => '3',
            'heading' => ['en' => 'What we clean'],
        ];
    }

    public static function view(): string
    {
        return 'blocks.collection';
    }

    /** The two types this site has. A third would be one line. */
    public static function types(): array
    {
        return [
            ServiceType::type() => ServiceType::pluralLabel(),
            ProductType::type() => ProductType::pluralLabel(),
        ];
    }

    public function schema(): array
    {
        return [
            Select::make('page_type')
                ->label('What to list')
                ->options(static::types())
                ->default(ServiceType::type())
                ->required()
                ->native(false)
                ->live(),

            TextInput::make('heading')->label('Heading')->live(debounce: 400),

            Select::make('source')
                ->label('Which ones')
                ->options([
                    'all' => 'Everything published',
                    'picked' => 'The ones I choose',
                ])
                ->default('all')
                ->native(false)
                ->live(),

            // Stored as a flat list of ids in the order they are dragged. A
            // page later unpublished or deleted simply stops appearing.
            Repeater::make('pages')
                ->label('Chosen')
                ->simple(
                    Select::make('page')
                        ->options(fn (callable $get) => static::choices($get('../../page_type')))
                        ->searchable()
                        ->required(),
                )
                ->addActionLabel('Add one')
                ->reorderable()
                ->defaultItems(1)
                ->visible(fn (callable $get) => $get('source') === 'picked')
                ->helperText('Drag to set the order they appear in.')
                ->live(),

            Select::make('order')
                ->label('Order')
                ->options([
                    'title' => 'Title, A to Z',
                    'newest' => 'Newest first',
                    'price' => 'Cheapest first',
                ])
                ->default('title')
                ->native(false)
                ->visible(fn (callable $get) => $get('source') !== 'picked')
                ->live(),

            TextInput::make('limit')
                ->label('How many')
                ->numeric()
                ->minValue(1)
                ->placeholder('All of them')
                ->live(debounce: 400),

            Select::make('columns')
                ->label('Columns')
                ->options(['2' => 'Two', '3' => 'Three', '4' => 'Four'])
                ->default('3')
                ->native(false)
                ->live(),

            TextInput::make('empty')
                ->label('Text when there is nothing to list')
                ->placeholder('Nothing to show yet.')
                ->live(debounce: 400),
        ];
    }

    /**
     * The pages this block lists.
     *
     * Here rather than in the Blade view: a view running an Eloquent query is
     * where a page builder starts turning into WordPress.
     *
     * @param  array<string, mixed>  $attributes  collapsed to $locale
     * @return Collection<int, Page>
     */
    public static function items(array $attributes, ?Page $current = null): Collection
    {
        $type = $attributes['page_type'] ?? null;

        if (! is_string($type) || ! array_key_exists($type, static::types())) {
            return collect();
        }

        $items = ($attributes['source'] ?? 'all') === 'picked'
            ? static::picked($attributes, $type)
            : static::everything($attributes, $type, $current);

        $limit = (int) ($attributes['limit'] ?? 0);

        return $limit > 0 ? $items->take($limit) : $items;
    }

    /** @return Collection<int, Page> */
    protected static function everything(array $attributes, string $type, ?Page $current): Collection
    {
        // Published only, in the editor preview as well as on the live page.
        // A preview listing half-finished drafts is a preview of a site that
        // does not exist.
        $items = Page::query()
            ->where('status', 'published')
            ->ofType($type)
            ->when($current, fn ($query) => $query->whereKeyNot($current->getKey()))
            ->with('slugs')
            ->get();

        return match ($attributes['order'] ?? 'title') {
            'newest' => $items->sortByDesc('published_at')->values(),
            'price' => $items->sortBy(fn (Page $page) => static::price($page) ?? INF)->values(),
            default => $items->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE)->values(),
        };
    }

    /** @return Collection<int, Page> */
    protected static function picked(array $attributes, string $type): Collection
    {
        $ids = collect($attributes['pages'] ?? [])
            ->flatten()
            ->filter()
            ->map(fn (mixed $id) => (string) $id)
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Page::query()
            ->where('status', 'published')
            ->ofType($type)
            ->whereKey($ids->all())
            ->with('slugs')
            ->get()
            ->sortBy(fn (Page $page) => $ids->search((string) $page->getKey()))
            ->values();
    }

    /** The cheapest tier or variation, whichever kind of page this is. */
    public static function price(Page $page): ?float
    {
        return $page->type === ProductType::type()
            ? ProductType::fromPrice($page)
            : ServiceType::fromPrice($page);
    }

    /** @return array<int|string, string> */
    protected static function choices(mixed $type): array
    {
        $types = array_keys(static::types());

        $narrowed = is_string($type) && in_array($type, $types, true);

        return Page::query()
            ->where('status', 'published')
            ->ofType($narrowed ? $type : $types)
            ->orderBy('title')
            ->pluck('title', 'id')
            ->all();
    }
}
