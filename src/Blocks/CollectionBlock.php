<?php

declare(strict_types=1);

namespace Safi\Atelier\Blocks;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Collection;
use Safi\Atelier\Models\Page;
use Safi\Atelier\PageTypeRegistry;

/**
 * Lists pages of one type, each rendered through that type's card view.
 *
 * This is what makes a page type worth having: a custom property nobody can
 * list is a form field with no consumer. The block knows nothing about what a
 * type's fields are called, and it does not need to. It finds the pages and
 * hands each one to the type's own Blade partial.
 *
 * Published pages only, in the editor as well as on the public page. A
 * preview that lists half-finished drafts is a preview of a site that does
 * not exist.
 */
class CollectionBlock extends BaseBlock
{
    public static function type(): string
    {
        return 'collection';
    }

    public static function label(): string
    {
        return 'Collection';
    }

    public static function icon(): string
    {
        return 'heroicon-o-rectangle-stack';
    }

    public static function category(): string
    {
        return 'Content';
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
            'source' => 'all',
            'order' => 'title',
            'columns' => '3',
        ];
    }

    public function schema(): array
    {
        $types = app(PageTypeRegistry::class)->options();

        return [
            Select::make('page_type')
                ->label('What to list')
                ->options($types)
                ->default(array_key_first($types))
                ->required()
                // A site with one type has one answer, and a select with one
                // answer is still worth showing here: the block is generic,
                // and hiding the field would make it look broken.
                ->native(false)
                ->live(),

            TextInput::make('heading')
                ->label('Heading')
                ->live(debounce: 400),

            Select::make('source')
                ->label('Which ones')
                ->options([
                    'all' => 'Everything published',
                    'picked' => 'The ones I choose',
                    'children' => 'Only the ones under this page',
                ])
                ->default('all')
                ->native(false)
                ->helperText('Under this page means by slug path: a page at services shows services/web-design.')
                ->live(),

            // Hand-picked, in the order they are dragged into. Stored as a
            // flat list of ids, so a page that is later unpublished or
            // deleted simply stops appearing rather than breaking the block.
            Repeater::make('pages')
                ->label('Chosen')
                ->simple(
                    Select::make('page')
                        // The relative path reaches the block's own type
                        // select, two levels up from inside a repeater item.
                        // choices() copes with it resolving to nothing by
                        // offering every typed page instead of none.
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
                    'oldest' => 'Oldest first',
                ])
                ->default('title')
                ->native(false)
                // Hand-picked already has an order: the one they dragged.
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
                ->options(['1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four'])
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
     * On the view rather than in the view, because a Blade view running an
     * Eloquent query is where a page builder starts turning into WordPress.
     *
     * @param  array<string, mixed>  $attributes  collapsed to $locale
     * @return Collection<int, Page>
     */
    public static function items(array $attributes, string $locale, ?Page $current = null): Collection
    {
        $type = $attributes['page_type'] ?? null;

        if (! is_string($type) || $type === '') {
            return collect();
        }

        $source = $attributes['source'] ?? 'all';

        if ($source === 'picked') {
            return static::picked($attributes, $type);
        }

        // Under this page is the slug path, the same fact breadcrumbs are
        // built on, so it needs no parent column.
        if ($source === 'children') {
            $items = $current?->children($locale) ?? collect();

            $items = $items->filter(fn (Page $page) => $page->type === $type)->values();
        } else {
            $items = Page::query()
                ->where('status', 'published')
                ->ofType($type)
                ->when($current, fn ($query) => $query->whereKeyNot($current->getKey()))
                ->with('slugs')
                ->get();
        }

        $items = match ($attributes['order'] ?? 'title') {
            'newest' => $items->sortByDesc('published_at')->values(),
            'oldest' => $items->sortBy('published_at')->values(),
            default => $items->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE)->values(),
        };

        return static::limited($items, $attributes);
    }

    /**
     * The hand-picked ones, in the order they were dragged into.
     *
     * Scoped to the chosen type as well as to the ids, so switching the type
     * after picking drops the stale picks instead of listing a product in a
     * services grid.
     *
     * @param  array<string, mixed>  $attributes
     * @return Collection<int, Page>
     */
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

        $items = Page::query()
            ->where('status', 'published')
            ->ofType($type)
            ->whereKey($ids->all())
            ->with('slugs')
            ->get()
            ->sortBy(fn (Page $page) => $ids->search((string) $page->getKey()))
            ->values();

        return static::limited($items, $attributes);
    }

    /**
     * Pages for the picker. Published only, because the block lists published
     * pages and offering a draft is offering a blank.
     *
     * With a type, that type's pages by title. Without one, every typed page
     * with its type in the label, so the picker is never empty for want of
     * knowing which select to read.
     *
     * @return array<int|string, string>
     */
    public static function choices(mixed $type): array
    {
        $registry = app(PageTypeRegistry::class);

        $known = array_keys($registry->all());

        if ($known === []) {
            return [];
        }

        $narrowed = is_string($type) && $registry->has($type);

        $pages = Page::query()
            ->where('status', 'published')
            ->ofType($narrowed ? $type : $known)
            ->orderBy('title')
            ->get(['id', 'title', 'type']);

        return $pages
            ->mapWithKeys(fn (Page $page) => [
                $page->getKey() => $narrowed
                    ? $page->title
                    : $page->title.' ('.($registry->resolve($page->type)::label()).')',
            ])
            ->all();
    }

    /**
     * @param  Collection<int, Page>  $items
     * @param  array<string, mixed>  $attributes
     * @return Collection<int, Page>
     */
    protected static function limited(Collection $items, array $attributes): Collection
    {
        $limit = (int) ($attributes['limit'] ?? 0);

        return $limit > 0 ? $items->take($limit) : $items;
    }
}
