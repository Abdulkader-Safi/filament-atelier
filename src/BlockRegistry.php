<?php

declare(strict_types=1);

namespace Safi\Atelier;

use InvalidArgumentException;

class BlockRegistry
{
    /** @var array<string, class-string<Block>> */
    protected array $blocks = [];

    /** @param class-string<Block>|array<class-string<Block>> $block */
    public function register(string|array $block): static
    {
        foreach ((array) $block as $class) {
            if (!is_subclass_of($class, Block::class)) {
                throw new InvalidArgumentException(
                    "{$class} must implement " . Block::class,
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
