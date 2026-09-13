<?php

declare(strict_types=1);

namespace Safi\Atelier;

use Illuminate\Support\Str;
use InvalidArgumentException;

class BlockRegistry
{
    /** @var array<string, class-string<Block>> */
    protected array $blocks = [];

    /** @param class-string<Block>|array<class-string<Block>> $block */
    public function register(string|array $block): static
    {
        foreach ((array) $block as $class) {
            if (! is_subclass_of($class, Block::class)) {
                throw new InvalidArgumentException(
                    "{$class} must implement ".Block::class,
                );
            }

            $this->blocks[$class::type()] = $class;
        }

        return $this;
    }

    public function has(string $type): bool
    {
        return isset($this->blocks[$type]);
    }

    public function resolve(string $type): ?Block
    {
        $class = $this->blocks[$type] ?? null;

        return $class ? app($class) : null;
    }

    /** @return array<string, class-string<Block>> */
    public function all(): array
    {
        return $this->blocks;
    }

    /**
     * A page type's template into a real block tree.
     *
     * A template names block types and, where it wants to, their attributes.
     * Everything else is filled in here: the id the editor tracks sections by,
     * and the block's own defaults, so a seeded hero arrives with the same
     * placeholder text as one added by hand. A type nobody registered is
     * skipped rather than seeded as an unknown block.
     *
     * @param  array<int, array<string, mixed>>  $template
     * @return array<int, array<string, mixed>>
     */
    public function tree(array $template): array
    {
        $tree = [];

        foreach ($template as $node) {
            $type = $node['type'] ?? null;
            $class = is_string($type) ? ($this->blocks[$type] ?? null) : null;

            if ($class === null) {
                continue;
            }

            $tree[] = [
                'id' => 'b_'.Str::lower(Str::random(6)),
                'type' => $type,
                // defaults() lives on BaseBlock, not on the Block interface,
                // so a block written against the interface alone has none.
                'attributes' => $node['attributes']
                    ?? (method_exists($class, 'defaults') ? $class::defaults() : []),
                'children' => $node['children'] ?? [],
            ];
        }

        return $tree;
    }

    /**
     * Block classes grouped by category, for the section picker.
     *
     * @return array<string, array<string, class-string<Block>>>
     */
    public function byCategory(): array
    {
        $grouped = [];

        foreach ($this->blocks as $type => $class) {
            $grouped[$class::category()][$type] = $class;
        }

        return $grouped;
    }
}
