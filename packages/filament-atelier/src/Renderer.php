<?php

declare(strict_types=1);

namespace Safi\Atelier;

use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

/**
 * Walks a block tree and renders it. The preview and the public page both
 * come through here, which is what keeps them identical.
 */
class Renderer
{
    public function __construct(protected BlockRegistry $registry) {}

    /**
     * A translatable value must reach the view as a string.
     *
     * Filament's RichEditor keeps a TipTap document while editing, and a bug
     * once wrote that structure straight into the tree. The editor no longer
     * does, but a public page must not fatal on data written by an older
     * version, so convert rather than trust.
     */
    protected function toText(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value) && isset($value['type'])) {
            return RichContentRenderer::make($value)->toHtml();
        }

        return is_scalar($value) ? (string) $value : '';
    }

    /** @param array<int, array> $tree */
    public function render(array $tree, string $locale, bool $editing = false): string
    {
        return collect($tree)
            ->reject(fn (array $node) => ($node['hidden'] ?? false) && ! $editing)
            ->map(fn (array $node) => $this->renderBlock($node, $locale, $editing))
            ->implode("\n");
    }

    protected function renderBlock(array $node, string $locale, bool $editing): string
    {
        $type = $node['type'] ?? null;
        $block = $type ? $this->registry->resolve($type) : null;

        if (! $block) {
            // Unknown type renders nothing publicly, but the editor has to say
            // something or the section silently vanishes from the canvas.
            return $editing
                ? Blade::render(
                    '<div class="p-6 text-sm text-red-700 bg-red-50 border border-red-200">Unknown block type: {{ $type }}</div>',
                    ['type' => (string) $type],
                )
                : '';
        }

        $attributes = $this->localise(
            $node['attributes'] ?? [],
            $block::translatable(),
            $locale,
        );

        $children = $node['children'] ?? [];

        return view($block::view(), [
            'block' => $block,
            'attributes' => $attributes,
            'children' => $children ? $this->render($children, $locale, $editing) : '',
            'id' => $node['id'] ?? Str::random(8),
            'node' => $node,
            'locale' => $locale,
            'editing' => $editing,
        ])->render();
    }

    /**
     * Collapse per-locale maps down to the requested locale.
     *
     * Translatable attributes are stored as { "en": "...", "ar": "..." } so
     * both languages live in one tree with one section order.
     */
    protected function localise(array $attributes, array $translatable, string $locale): array
    {
        foreach ($translatable as $key) {
            $value = $attributes[$key] ?? null;

            if (is_array($value)) {
                $value = $value[$locale] ?? '';
            }

            $attributes[$key] = $this->toText($value);
        }

        return $attributes;
    }
}
