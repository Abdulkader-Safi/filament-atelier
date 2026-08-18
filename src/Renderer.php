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
     * Whatever a view is handed, it must be safe to use.
     *
     * Repeater values are lists and pass straight through. Filament's
     * RichEditor keeps a TipTap document while editing, and a bug once wrote
     * that structure into the tree; the editor no longer does, but a public
     * page must not fatal on data an older version wrote, so convert rather
     * than trust.
     */
    protected function normalise(mixed $value): mixed
    {
        if (is_string($value) || $value === null) {
            return $value;
        }

        if (is_array($value)) {
            return isset($value['type'], $value['content'])
                ? RichContentRenderer::make($value)->toHtml()
                : $value;
        }

        return is_scalar($value) ? (string) $value : null;
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

        $attributes = $this->resolveTokens($this->localise(
            $node['attributes'] ?? [],
            $block::translatable(),
            $locale,
        ));

        $children = $node['children'] ?? [];

        $id = $node['id'] ?? Str::random(8);

        return view($block::view(), [
            'block' => $block,
            'attributes' => $attributes,
            // The root element's attributes: the id the editor tracks sections
            // by, plus whatever the block opted into through supports().
            'shared' => SharedControls::attributes($block::supports(), $attributes, $id),
            'children' => $children ? $this->render($children, $locale, $editing) : '',
            'id' => $id,
            'node' => $node,
            'locale' => $locale,
            'editing' => $editing,
        ])->render();
    }

    /**
     * Turn every stored `{ "token": "color.primary" }` into a CSS value.
     *
     * Done here rather than in each view, so a block author writes
     * `style="background:{{ $attributes['background'] }}"` and never learns
     * that tokens exist.
     */
    protected function resolveTokens(array $attributes): array
    {
        foreach ($attributes as $key => $value) {
            $attributes[$key] = is_array($value) && ! isset($value['token'])
                ? $this->resolveTokens($value)
                : Tokens::resolve($value);
        }

        return $attributes;
    }

    /**
     * Collapse per-locale maps down to the requested locale.
     *
     * Translatable attributes are stored as { "en": "...", "ar": "..." } so
     * both languages live in one tree with one section order.
     */
    protected function localise(array $attributes, array $translatable, string $locale): array
    {
        $fallback = array_key_first(config('atelier.locales', []) ?: [$locale => null]);

        foreach ($translatable as $key) {
            if (! array_key_exists($key, $attributes)) {
                continue;
            }

            $value = $attributes[$key];

            // A locale map is keyed by locale code. A repeater's value for one
            // locale is a list, so only unwrap when the keys look like locales.
            if (is_array($value) && ! array_is_list($value) && ! isset($value['type'])) {
                // Fall back to the default locale rather than rendering a hole.
                // A half-translated page should read as untranslated, not broken.
                $value = $value[$locale] ?? $value[$fallback] ?? null;
            }

            $attributes[$key] = $this->normalise($value);
        }

        return $attributes;
    }
}
