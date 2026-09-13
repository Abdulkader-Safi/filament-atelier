<?php

declare(strict_types=1);

namespace Safi\Atelier\PageTypes;

use Illuminate\Support\Str;
use Safi\Atelier\PageType;

/**
 * Sensible defaults so a page type is only the parts that differ. A type with
 * no custom properties is six lines. Implement {@see PageType} directly if you
 * want none of this.
 */
abstract class BasePageType implements PageType
{
    public static function label(): string
    {
        return Str::headline(static::type());
    }

    public static function pluralLabel(): string
    {
        return Str::plural(static::label());
    }

    public static function icon(): string
    {
        return 'heroicon-o-document-text';
    }

    public static function navigationSort(): ?int
    {
        return null;
    }

    /** `service` becomes /admin/services. */
    public static function slug(): string
    {
        return Str::slug(Str::plural(static::type()));
    }

    public static function fields(): array
    {
        return [];
    }

    public static function translatable(): array
    {
        return [];
    }

    public static function template(): array
    {
        return [];
    }

    public static function blocks(): ?array
    {
        return null;
    }

    public static function cardView(): ?string
    {
        return null;
    }

    public static function prefix(): array|string|null
    {
        return null;
    }

    public static function schemaType(): ?string
    {
        return null;
    }

    public static function indexView(): ?string
    {
        return null;
    }
}
