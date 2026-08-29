<?php

declare(strict_types=1);

namespace Safi\Atelier\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page as FilamentPage;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Safi\Atelier\MenuRegistry;
use Safi\Atelier\MenuSource;
use Safi\Atelier\Models\Menu;

/**
 * One location's item tree, edited with a native Filament `Repeater`.
 *
 * Reordering is the Repeater's own drag handle rather than a bespoke
 * Livewire+Alpine canvas: it is already keyboard-accessible, it is what the
 * rest of the panel uses for a list of things, and it means no tree-mutation
 * code to keep in step with what Filament already does for a flat-plus-one-
 * level array. See task 13's "Built" note for the fuller reasoning.
 */
class MenuManager extends FilamentPage
{
    protected string $view = 'atelier::filament.pages.menu-manager';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bars-3';

    protected static ?string $navigationLabel = 'Menus';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 20;

    public string $location = '';

    /** @var array{items: array} */
    public array $data = ['items' => []];

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
        $this->data = ['items' => $this->location !== ''
            ? Menu::forLocation($this->location)->tree()
            : []];

        unset($this->cachedSchemas['form']);
        $this->form->fill($this->data);
    }

    /** @return array<string, string> */
    public function getLocationOptionsProperty(): array
    {
        return $this->registry()->options();
    }

    // Schema -------------------------------------------------------------

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Repeater::make('items')
                    ->label('')
                    ->hiddenLabel()
                    ->schema($this->itemSchema(nested: $this->depth() > 1))
                    ->reorderable()
                    ->collapsible()
                    ->addActionLabel('Add a custom link')
                    ->itemLabel(fn (array $state) => $this->itemLabel($state))
                    ->defaultItems(0)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    /** @return array<int, Component> */
    protected function itemSchema(bool $nested): array
    {
        $locales = config('atelier.locales', []);

        $fields = [
            // Filament only dehydrates keys a field backs, so without this
            // the tree's own id, generated once when the item is added,
            // would be dropped on the first save and replaced by nothing.
            Hidden::make('id')->default(fn () => 'm_'.Str::lower(Str::random(6))),

            Tabs::make('Label')
                ->tabs(collect($locales)->map(fn (array $locale, string $code) => Tab::make($locale['label'])->schema([
                    TextInput::make("label.{$code}")->label('Label')->maxLength(255),
                ]))->all())
                ->columnSpanFull(),

            TextInput::make('url')
                ->label('URL')
                ->helperText('A path like /about, or a full https:// URL.')
                ->maxLength(2048)
                ->columnSpan(1),

            Select::make('target')
                ->label('Opens in')
                ->options(['_self' => 'Same tab', '_blank' => 'New tab'])
                ->default('_self')
                ->native(false)
                ->columnSpan(1),
        ];

        if ($nested) {
            $fields[] = Repeater::make('children')
                ->label('Sub-items')
                ->schema($this->itemSchema(nested: false))
                ->reorderable()
                ->collapsible()
                ->addActionLabel('Add a sub-item')
                ->itemLabel(fn (array $state) => $this->itemLabel($state))
                ->defaultItems(0)
                ->columnSpanFull();
        }

        return [
            Grid::make(2)->schema($fields)->columnSpanFull(),
        ];
    }

    /** Label the row by its own text where it has one, so the list isn't "Item" over and over. */
    protected function itemLabel(array $state): string
    {
        $label = $state['label'] ?? [];
        $label = is_array($label) ? (reset($label) ?: null) : $label;

        return is_string($label) && trim($label) !== '' ? $label : ($state['url'] ?? 'Item');
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

        $this->data['items'][] = [
            'id' => 'm_'.Str::lower(Str::random(6)),
            'label' => [array_key_first(config('atelier.locales', [])) => $model->getMenuLabel()],
            'url' => $model->getMenuUrl(),
            'target' => '_self',
            'children' => [],
        ];

        unset($this->cachedSchemas['form']);
        $this->form->fill($this->data);
        $this->persist();
    }

    // Persistence ----------------------------------------------------------

    /** Called by Livewire on every change under the `data` state path. */
    public function updatedData(): void
    {
        $this->persist();
    }

    protected function persist(): void
    {
        if ($this->location === '') {
            return;
        }

        Menu::forLocation($this->location)->update(['items' => $this->dehydratedItems()]);
    }

    /**
     * The form's dehydrated state, not the raw Livewire property.
     *
     * A Repeater tracks its rows by an internal key once the form has
     * hydrated them, not by the plain 0, 1, 2 the tree is stored under, so
     * reading `$this->data` straight after a `fill()` (switching location,
     * adding from a source) would write that internal keying into the
     * column. Dehydrating runs the Repeater back down to a clean list,
     * the same reasoning as `PageEditor::dehydratedData()`, without that
     * method's validation cost, because nothing on a menu item is required.
     */
    protected function dehydratedItems(): array
    {
        $state = ['data' => $this->data ?? []];

        $this->form->callBeforeStateDehydrated($state);
        $this->form->dehydrateState($state);
        $this->form->mutateDehydratedState($state);

        return data_get($state, 'data.items') ?? [];
    }

    protected function registry(): MenuRegistry
    {
        return app(MenuRegistry::class);
    }

    protected function depth(): int
    {
        return $this->location !== '' ? $this->registry()->depth($this->location) : 1;
    }
}
