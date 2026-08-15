<?php

declare(strict_types=1);

namespace Safi\Atelier\Filament\Pages;

use Filament\Pages\Page as FilamentPage;
use Filament\Panel;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Safi\Atelier\BlockRegistry;
use Safi\Atelier\Models\Page;

/**
 * The three-pane editor. Section list left, live preview centre, settings
 * right.
 *
 * The preview never renders here. It renders through the public layout in an
 * iframe, so the editor cannot accidentally become a second rendering path.
 */
class PageEditor extends FilamentPage
{
    protected string $view = 'atelier::filament.pages.page-editor';

    protected static bool $shouldRegisterNavigation = false;

    public Page $page;

    /** The working block tree. Mirrors draft_content. */
    public array $tree = [];

    public ?string $selectedId = null;

    public string $locale = 'en';

    public string $width = 'desktop';

    /** Settings-pane state for the selected block, flattened to $locale. */
    public ?array $data = [];

    public static function getRoutePath(Panel $panel): string
    {
        return '/atelier/{record}';
    }

    public function mount(int|string $record): void
    {
        $this->page = Page::findOrFail($record);
        $this->tree = $this->page->draft();
        $this->locale = array_key_first(config('atelier.locales'));

        $this->selectBlock($this->tree[0]['id'] ?? null);
    }

    public function getTitle(): string
    {
        return $this->page->title;
    }

    // Schema ---------------------------------------------------------------

    public function form(Schema $schema): Schema
    {
        $block = $this->selectedBlock();

        return $schema
            ->components($block ? $block->schema() : [])
            ->statePath('data');
    }

    /** Called by Livewire whenever a settings field changes. */
    public function updatedData(): void
    {
        $index = $this->selectedIndex();

        if ($index === null) {
            return;
        }

        $this->tree[$index]['attributes'] = $this->mergeLocale(
            $this->tree[$index]['attributes'] ?? [],
            $this->data ?? [],
        );

        $this->persist();
    }

    // Section list ---------------------------------------------------------

    public function selectBlock(?string $id): void
    {
        $this->selectedId = $id;
        $this->data = $this->flattenLocale($this->selectedNode()['attributes'] ?? []);
    }

    public function addBlock(string $type): void
    {
        $block = $this->registry()->resolve($type);

        if (! $block) {
            return;
        }

        $id = 'b_'.Str::lower(Str::random(6));

        $this->tree[] = [
            'id' => $id,
            'type' => $type,
            'attributes' => $block::defaults(),
            'children' => [],
        ];

        $this->persist();
        $this->selectBlock($id);
    }

    public function duplicateBlock(string $id): void
    {
        $index = $this->indexOf($id);

        if ($index === null) {
            return;
        }

        $copy = $this->tree[$index];
        $copy['id'] = 'b_'.Str::lower(Str::random(6));

        array_splice($this->tree, $index + 1, 0, [$copy]);

        $this->persist();
        $this->selectBlock($copy['id']);
    }

    public function deleteBlock(string $id): void
    {
        $index = $this->indexOf($id);

        if ($index === null) {
            return;
        }

        array_splice($this->tree, $index, 1);

        $this->persist();

        if ($this->selectedId === $id) {
            $this->selectBlock($this->tree[0]['id'] ?? null);
        }
    }

    public function toggleHidden(string $id): void
    {
        $index = $this->indexOf($id);

        if ($index === null) {
            return;
        }

        $this->tree[$index]['hidden'] = ! ($this->tree[$index]['hidden'] ?? false);

        $this->persist();
    }

    public function move(string $id, int $offset): void
    {
        $from = $this->indexOf($id);
        $to = $from + $offset;

        if ($from === null || $to < 0 || $to >= count($this->tree)) {
            return;
        }

        [$this->tree[$from], $this->tree[$to]] = [$this->tree[$to], $this->tree[$from]];

        $this->persist();
    }

    // Toolbar --------------------------------------------------------------

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;

        // Re-flatten the settings pane onto the new locale's values.
        $this->selectBlock($this->selectedId);

        $this->persist();
    }

    public function setWidth(string $width): void
    {
        $this->width = $width;
    }

    public function publish(): void
    {
        $this->page->publish();
    }

    // Helpers --------------------------------------------------------------

    public function getPreviewUrlProperty(): string
    {
        return URL::signedRoute('atelier.preview', [
            'page' => $this->page->getKey(),
            'locale' => $this->locale,
        ]);
    }

    public function getSectionsProperty(): array
    {
        return collect($this->tree)->map(function (array $node) {
            $block = $this->registry()->resolve($node['type'] ?? '');

            return [
                'id' => $node['id'],
                'label' => $this->rowLabel($node, $block),
                'type' => $block?->label() ?? $node['type'],
                'icon' => $block?->icon() ?? 'heroicon-o-question-mark-circle',
                'hidden' => $node['hidden'] ?? false,
            ];
        })->all();
    }

    public function getPickerProperty(): array
    {
        return collect($this->registry()->byCategory())
            ->map(fn (array $blocks) => collect($blocks)
                ->map(fn (string $class, string $type) => [
                    'type' => $type,
                    'label' => $class::label(),
                    'icon' => $class::icon(),
                ])->values()->all())
            ->all();
    }

    /**
     * Label the row by the block's own heading where it has one. "Hero" three
     * times in a list tells the client nothing.
     */
    protected function rowLabel(array $node, ?object $block): string
    {
        $heading = $node['attributes']['heading'] ?? null;

        if (is_array($heading)) {
            $heading = $heading[$this->locale] ?? reset($heading);
        }

        $heading = is_string($heading) ? trim(strip_tags($heading)) : '';

        return $heading !== ''
            ? Str::limit($heading, 40)
            : ($block?->label() ?? 'Section');
    }

    protected function persist(): void
    {
        $this->page->update(['draft_content' => $this->tree]);

        $this->dispatch('atelier-refresh');
    }

    protected function registry(): BlockRegistry
    {
        return app(BlockRegistry::class);
    }

    protected function selectedBlock(): ?object
    {
        $type = $this->selectedNode()['type'] ?? null;

        return $type ? $this->registry()->resolve($type) : null;
    }

    protected function selectedNode(): ?array
    {
        $index = $this->selectedIndex();

        return $index === null ? null : $this->tree[$index];
    }

    protected function selectedIndex(): ?int
    {
        return $this->selectedId ? $this->indexOf($this->selectedId) : null;
    }

    protected function indexOf(string $id): ?int
    {
        foreach ($this->tree as $index => $node) {
            if (($node['id'] ?? null) === $id) {
                return $index;
            }
        }

        return null;
    }

    /** Per-locale maps down to plain values for the form. */
    protected function flattenLocale(array $attributes): array
    {
        foreach ($this->translatableKeys() as $key) {
            if (is_array($attributes[$key] ?? null)) {
                $attributes[$key] = $attributes[$key][$this->locale] ?? '';
            }
        }

        return $attributes;
    }

    /** Plain form values back into per-locale maps, leaving other locales alone. */
    protected function mergeLocale(array $stored, array $incoming): array
    {
        $translatable = $this->translatableKeys();

        foreach ($incoming as $key => $value) {
            if (in_array($key, $translatable, true)) {
                $map = is_array($stored[$key] ?? null) ? $stored[$key] : [];
                $map[$this->locale] = $value;
                $stored[$key] = $map;

                continue;
            }

            $stored[$key] = $value;
        }

        return $stored;
    }

    protected function translatableKeys(): array
    {
        $block = $this->selectedBlock();

        return $block ? $block::translatable() : [];
    }
}
