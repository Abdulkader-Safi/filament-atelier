<?php

declare(strict_types=1);

namespace Safi\Atelier\Models;

use Illuminate\Database\Eloquent\Model;

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
}
