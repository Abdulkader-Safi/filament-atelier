<?php

declare(strict_types=1);

namespace Safi\Atelier\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page as FilamentPage;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Str;
use Safi\Atelier\MenuRegistry;
use Safi\Atelier\MenuSource;
use Safi\Atelier\Models\Menu;

/**
 * One location's item tree: a compact, indented list, editing done in a
 * Filament action modal rather than an inline form.
 *
 * Two decisions behind the shape, both taken 30 Aug 2026 after the first cut
 * read as cluttered and off-theme:
 *
 * Owning the tree as a plain array (`addItem`, `deleteItem`, `move`, each
 * calling `persist()` itself) rather than a Filament `Repeater`, modelled on
 * `PageEditor`'s own section list. The Repeater version needed `->after()`
 * hooks bolted onto three separate built-in actions to make add, delete and
 * reorder actually save (task 13's earlier "Fixed" note), because none of
 * them are field updates Livewire's own diffing picks up. Owning the array
 * sidesteps that class of bug entirely.
 *
 * Editing in a modal Action rather than an inline expand: a row that turns
 * into a form reflows everything below it and looks like nothing else in
 * the panel. A Filament `Action` modal is what "Add a custom link" already
 * used, is exactly the panel's own theme for free, and is the shape
 * Shopify's own menu editor uses too, checked on Mobbin. The list itself
 * stays a drag-free, single-line-per-item list: label, an Edit link, a
 * Delete link, no URL shown in the row, the same restraint Shopify's list
 * has.
 */
class MenuManager extends FilamentPage
{
    protected string $view = 'atelier::filament.pages.menu-manager';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bars-3';

    protected static ?string $navigationLabel = 'Menus';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 20;

    public string $location = '';

    /** The working item tree. Mirrors the row's `items` column. */
    public array $tree = [];

    public function mount(): void
    {
        $this->location = array_key_first($this->registry()->all()) ?? '';

        $this->loadLocation();
    }

    public function getTitle(): string
    {
        return 'Menus';
    }

    public function getSubheading(): ?string
    {
        return 'Navigation for the public site. Changes save as you make them.';
    }

    // Location switch --------------------------------------------------

    public function updatedLocation(): void
    {
        $this->loadLocation();
    }

    protected function loadLocation(): void
    {
        $this->tree = $this->location !== '' ? Menu::forLocation($this->location)->tree() : [];
    }

    /** @return array<string, string> */
    public function getLocationOptionsProperty(): array
    {
        return $this->registry()->options();
    }

    // Tree mutation ----------------------------------------------------

    public function addItem(?string $parentId = null): void
    {
        $id = 'm_'.Str::lower(Str::random(6));

        $item = ['id' => $id, 'label' => [], 'url' => [], 'target' => '_self', 'children' => []];

        if ($parentId === null) {
            $this->tree[] = $item;
        } else {
            $parentIndex = $this->indexOf($this->tree, $parentId);

            if ($parentIndex === null) {
                return;
            }

            $this->tree[$parentIndex]['children'][] = $item;
        }

        $this->persist();
        $this->mountAction('editItem', ['id' => $id]);
    }

    public function move(string $id, int $offset): void
    {
        $location = $this->locate($id);

        if (! $location) {
            return;
        }

        if ($location['parentIndex'] === null) {
            $this->swap($this->tree, $location['index'], $location['index'] + $offset);
        } else {
            $this->swap($this->tree[$location['parentIndex']]['children'], $location['index'], $location['index'] + $offset);
        }

        $this->persist();
    }

    protected function swap(array &$items, int $from, int $to): void
    {
        if ($to < 0 || $to >= count($items)) {
            return;
        }

        [$items[$from], $items[$to]] = [$items[$to], $items[$from]];
    }

    /**
     * Rebuilds the whole tree from a drag-and-drop's result: the top-level
     * order, and each top-level item's own children order. One call rather
     * than "move" and "reparent" as separate operations, because a single
     * drop can be both at once, an item dragged out of "Services" and
     * dropped between two top-level items in the same gesture.
     *
     * Every id is looked up against the tree as it stood before the drag,
     * so the browser only ever tells this method a shape, never new item
     * content; an id it doesn't recognise (stale state, a tampered request)
     * is dropped rather than trusted. A dragged-in id's own children are
     * discarded rather than carried across: the UI never offers a second
     * level to drag into, so nothing should arrive with one, and dropping
     * them is what actually enforces that rather than merely assuming it.
     *
     * The one thing this refuses outright rather than tolerates: a payload
     * that leaves out an id the tree already had. A sync() built from a
     * DOM read mid-drag, or one that raced a Livewire re-render, is the
     * likeliest way that happens, and this method has no way to tell that
     * apart from a genuine deletion request, so it treats every apparent
     * deletion as suspect and no-ops rather than risk silently discarding
     * an item deleteItem() was never actually called for.
     *
     * @param  array<int, string>  $top  Item ids, top-level, in their new order.
     * @param  array<string, array<int, string>>  $children  Item ids per parent id, in their new order.
     */
    public function reorderTree(array $top, array $children = []): void
    {
        $flat = $this->flatten($this->tree);

        $incoming = array_unique(array_merge($top, ...array_values($children)));

        if (array_diff(array_keys($flat), $incoming) !== []) {
            return;
        }

        $newTree = [];

        foreach ($top as $id) {
            if (! isset($flat[$id])) {
                continue;
            }

            $item = $flat[$id];
            $item['children'] = [];

            foreach ($children[$id] ?? [] as $childId) {
                if (! isset($flat[$childId]) || $childId === $id) {
                    continue;
                }

                $child = $flat[$childId];
                $child['children'] = [];
                $item['children'][] = $child;
            }

            $newTree[] = $item;
        }

        $this->tree = $newTree;
        $this->persist();
    }

    /** @return array<string, array> id to item, one level flattened, `children` left as found. */
    protected function flatten(array $tree): array
    {
        $flat = [];

        foreach ($tree as $item) {
            $flat[$item['id']] = $item;

            foreach ($item['children'] ?? [] as $child) {
                $flat[$child['id']] = $child;
            }
        }

        return $flat;
    }

    protected function indexOf(array $items, string $id): ?int
    {
        foreach ($items as $index => $item) {
            if (($item['id'] ?? null) === $id) {
                return $index;
            }
        }

        return null;
    }

    /** Where an item lives: its own index, plus its parent's index if it's a child. */
    protected function locate(string $id): ?array
    {
        $topIndex = $this->indexOf($this->tree, $id);

        if ($topIndex !== null) {
            return ['parentIndex' => null, 'index' => $topIndex];
        }

        foreach ($this->tree as $parentIndex => $parent) {
            $childIndex = $this->indexOf($parent['children'] ?? [], $id);

            if ($childIndex !== null) {
                return ['parentIndex' => $parentIndex, 'index' => $childIndex];
            }
        }

        return null;
    }

    protected function findItem(string $id): ?array
    {
        $location = $this->locate($id);

        if (! $location) {
            return null;
        }

        return $location['parentIndex'] === null
            ? $this->tree[$location['index']]
            : $this->tree[$location['parentIndex']]['children'][$location['index']];
    }

    // Edit and delete, as Filament actions --------------------------------

    public function editItemAction(): Action
    {
        $locales = config('atelier.locales', []);

        return Action::make('editItem')
            ->label('Edit')
            ->modalHeading('Edit item')
            ->modalSubmitActionLabel('Save')
            ->fillForm(fn (array $arguments): array => $this->findItem($arguments['id'] ?? '') ?? [])
            ->form([
                Tabs::make('Label')
                    ->tabs(collect($locales)->map(fn (array $locale, string $code) => Tab::make($locale['label'])->schema([
                        TextInput::make("label.{$code}")->label('Label')->maxLength(255),
                        // A URL is content, not configuration: English is
                        // /about, Arabic is /ar/about, exactly like a page's
                        // own per-locale slug. One shared URL would mean
                        // either language linking to the other's path.
                        TextInput::make("url.{$code}")
                            ->label('URL')
                            ->helperText('A path like /about, or a full https:// URL.')
                            ->maxLength(2048),
                    ]))->all())
                    ->columnSpanFull(),

                Select::make('target')
                    ->label('Opens in')
                    ->options(['_self' => 'Same tab', '_blank' => 'New tab'])
                    ->default('_self')
                    ->native(false),
            ])
            ->action(function (array $data, array $arguments): void {
                $this->updateItem($arguments['id'], $data);
            });
    }

    public function deleteItemAction(): Action
    {
        return Action::make('deleteItem')
            ->label('Delete')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Delete this item?')
            ->action(function (array $arguments): void {
                $this->deleteItem($arguments['id']);
            });
    }

    protected function updateItem(string $id, array $data): void
    {
        $location = $this->locate($id);

        if (! $location) {
            return;
        }

        $existingChildren = $location['parentIndex'] === null
            ? ($this->tree[$location['index']]['children'] ?? [])
            : [];

        $updated = [
            'id' => $id,
            'label' => $data['label'] ?? [],
            'url' => $data['url'] ?? [],
            'target' => $data['target'] ?? '_self',
            'children' => $existingChildren,
        ];

        if ($location['parentIndex'] === null) {
            $this->tree[$location['index']] = $updated;
        } else {
            $this->tree[$location['parentIndex']]['children'][$location['index']] = $updated;
        }

        $this->persist();
    }

    protected function deleteItem(string $id): void
    {
        $location = $this->locate($id);

        if (! $location) {
            return;
        }

        if ($location['parentIndex'] === null) {
            array_splice($this->tree, $location['index'], 1);
        } else {
            array_splice($this->tree[$location['parentIndex']]['children'], $location['index'], 1);
        }

        $this->persist();
    }

    // Model-sourced items --------------------------------------------------

    /** One "Add a …" header action per registered {@see MenuSource}. */
    protected function getHeaderActions(): array
    {
        return collect($this->registry()->sourceClasses())
            ->map(fn (string $class) => Action::make('add-'.Str::slug($class))
                ->label('Add '.$class::menuSourceLabel())
                ->color('gray')
                ->form([
                    Select::make('id')
                        ->label($class::menuSourceLabel())
                        ->options($class::menuSourceOptions())
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data) use ($class): void {
                    $this->addFromSource($class, $data['id']);
                }))
            ->all();
    }

    /** @param  class-string<MenuSource>  $class */
    protected function addFromSource(string $class, int|string $id): void
    {
        $model = $class::menuSourceFind($id);

        if (! $model) {
            return;
        }

        $defaultLocale = array_key_first(config('atelier.locales', []));

        $this->tree[] = [
            'id' => 'm_'.Str::lower(Str::random(6)),
            'label' => [$defaultLocale => $model->getMenuLabel()],
            // Only the default locale is prefilled, same as the label: a
            // source hands over one string, the other locale's URL is the
            // editor's to fill in, exactly like an untranslated label.
            'url' => [$defaultLocale => $model->getMenuUrl()],
            'target' => '_self',
            'children' => [],
        ];

        $this->persist();
    }

    // Persistence ----------------------------------------------------------

    protected function persist(): void
    {
        if ($this->location === '') {
            return;
        }

        Menu::forLocation($this->location)->update(['items' => $this->tree]);
    }

    protected function registry(): MenuRegistry
    {
        return app(MenuRegistry::class);
    }

    /** Whether a top-level item may take children, for the "Add a sub-item" control. */
    public function canNest(): bool
    {
        return $this->location !== '' && $this->registry()->depth($this->location) > 0;
    }
}
