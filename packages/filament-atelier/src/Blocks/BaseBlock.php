<?php

declare(strict_types=1);

namespace Safi\Atelier\Blocks;

use Illuminate\Support\Str;
use Safi\Atelier\Block;

/**
 * Sensible defaults so a block class is only the parts that differ.
 * Implement Block directly if you want none of this.
 */
abstract class BaseBlock implements Block
{
    public static function label(): string
    {
        return Str::headline(class_basename(static::class));
    }

    public static function icon(): string
    {
        return 'heroicon-o-square-3-stack-3d';
    }

    public static function category(): string
    {
        return 'Content';
    }

    public static function supports(): array
    {
        return [];
    }

    public static function translatable(): array
    {
        return [];
    }

    /** `hero` resolves to the `atelier::blocks.hero` view. */
    public static function view(): string
    {
        return 'atelier::blocks.'.static::type();
    }

    /** Starting attributes when the block is added to a page. */
    public static function defaults(): array
    {
        return [];
    }
}
