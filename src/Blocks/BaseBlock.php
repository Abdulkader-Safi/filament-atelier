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

    /**
     * JSON-LD nodes this block contributes to the page's graph.
     *
     * The point of putting this on the block is that the data already exists:
     * an FAQ block holds questions and answers, so its schema is a transform
     * of what the client typed once, not a second thing to type.
     *
     * Return a list of nodes. A node carrying an `@id` already in the graph
     * merges into it, which is how two FAQ blocks on one page contribute to
     * one `FAQPage` rather than emitting two.
     *
     * @param  array<string, mixed>  $attributes  collapsed to $locale, tokens resolved
     * @return array<int, array<string, mixed>>
     */
    public static function structuredData(array $attributes, string $locale, string $url): array
    {
        return [];
    }
}
