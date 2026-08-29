<?php

declare(strict_types=1);

namespace Safi\Atelier\Models;

use Illuminate\Database\Eloquent\Model;
use Safi\Atelier\MenuRegistry;

/**
 * One row per location. `items` is the whole tree for that location: a flat
 * top level, each item optionally carrying one level of `children`.
 *
 * The same tree-in-a-column shape a page's block tree uses, not a relational
 * nested-set or adjacency-list table. A menu is tens of items, not
 * thousands, so the query-efficiency case for a relational hierarchy doesn't
 * apply here, and this keeps the package storing hierarchy one way rather
 * than two.
 *
 * @property string $location
 * @property array $items
 */
class Menu extends Model
{
    protected $table = 'atelier_menus';

    protected $guarded = [];

    protected $casts = [
        'items' => 'array',
    ];

    /** The row for a location, creating an empty one the first time it's opened. */
    public static function forLocation(string $location): static
    {
        return static::query()->firstOrCreate(['location' => $location], ['items' => []]);
    }

    /** The item tree. Always an array, never null. */
    public function tree(): array
    {
        return $this->items ?? [];
    }

    /**
     * A location's tree, or `[]` for an empty or unregistered location.
     *
     * The one entry point a developer needs to put a menu anywhere: the
     * shipped `atelier::partials.menu` calls this, and so can a controller,
     * a Livewire component, an API response, or a hand-rolled Blade view
     * that never touches the shipped partial at all. Never writes a row for
     * a location nobody registered, the same reasoning as a missing layout
     * falling back rather than 500ing.
     */
    public static function treeFor(string $location): array
    {
        return app(MenuRegistry::class)->has($location)
            ? static::forLocation($location)->tree()
            : [];
    }

    /**
     * One item's label for a locale, falling back to the default locale
     * rather than rendering a hole. Same convention `Renderer::localise()`
     * and `SiteSettings::translated()` already use for everything else
     * translatable in the package.
     *
     * @param  array<string, mixed>  $item
     */
    public static function label(array $item, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $fallback = array_key_first(config('atelier.locales', []) ?: [$locale => null]);

        $label = data_get($item, "label.{$locale}") ?: data_get($item, "label.{$fallback}");

        return is_string($label) ? $label : '';
    }
}
