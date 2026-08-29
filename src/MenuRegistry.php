<?php

declare(strict_types=1);

namespace Safi\Atelier;

use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Named menu locations (primary, footer, and so on) plus the Eloquent models
 * that can be picked as menu items.
 *
 * Registered from the panel provider, the same way blocks and layouts are:
 *
 *     AtelierPlugin::make()
 *         ->menuLocations(['primary' => 'Primary', 'footer' => 'Footer'])
 *         ->menuSources([\App\Models\Page::class]);
 *
 * A location is a key, a label and a max depth, the same shape a layout is a
 * key, a label and a view. One level of nesting is the default and the only
 * depth the editor and the public partial are built for; raising it needs
 * both changed to match, not just the number here.
 */
class MenuRegistry
{
    /** @var array<string, array{label: string, depth: int}> */
    protected array $locations = [];

    /** @var array<int, class-string<MenuSource>> */
    protected array $sources = [];

    /**
     * The long form is `['label' => ..., 'depth' => ...]`. The short form is
     * just the label.
     *
     * @param  array<string, string|array{label?: string, depth?: int}>  $locations
     */
    public function locations(array $locations): static
    {
        foreach ($locations as $key => $location) {
            if (is_string($location)) {
                $location = ['label' => $location];
            }

            $this->locations[$key] = [
                'label' => $location['label'] ?? Str::headline($key),
                'depth' => $location['depth'] ?? 1,
            ];
        }

        return $this;
    }

    /** @param  class-string<MenuSource>|array<int, class-string<MenuSource>>  $sources */
    public function sources(string|array $sources): static
    {
        foreach ((array) $sources as $class) {
            if (! is_subclass_of($class, MenuSource::class)) {
                throw new InvalidArgumentException(
                    "{$class} must implement ".MenuSource::class,
                );
            }

            $this->sources[] = $class;
        }

        return $this;
    }

    public function has(string $key): bool
    {
        return isset($this->locations[$key]);
    }

    public function label(string $key): string
    {
        return $this->locations[$key]['label'] ?? Str::headline($key);
    }

    /** How many levels deep a location's items may nest. Default 1: one level of children. */
    public function depth(string $key): int
    {
        return $this->locations[$key]['depth'] ?? 1;
    }

    /** @return array<string, string> key to label, for the location select. */
    public function options(): array
    {
        return array_map(fn (array $location) => $location['label'], $this->locations);
    }

    /** @return array<int, class-string<MenuSource>> */
    public function sourceClasses(): array
    {
        return $this->sources;
    }

    /** @return array<string, array{label: string, depth: int}> */
    public function all(): array
    {
        return $this->locations;
    }
}
