<?php

declare(strict_types=1);

namespace Safi\Atelier;

/**
 * Feature flags for anything still being proven out. Off by default.
 *
 * The menu manager is the first thing gated this way: it registers a real
 * page, in a client's own sidebar, and once that's shipped there's no clean
 * way back to "this doesn't exist yet" if it turns out to need more work.
 * A flag is the escape hatch, on `config('atelier.experimental')` the same
 * way `locales` and `menus` live in config, or per panel via
 * `AtelierPlugin::make()->experimental([...])`, which overrides config
 * rather than only adding to it, so a panel can turn something off that
 * config turned on just as easily as the reverse.
 *
 * Deliberately outside `MenuRegistry`: this isn't about menus, it's about
 * anything not trusted yet, menus just happen to be the first one.
 */
class ExperimentalFeatures
{
    /** @var array<string, bool> */
    protected array $flags = [];

    /** @param  array<string, bool>  $flags */
    public function set(array $flags): static
    {
        foreach ($flags as $key => $value) {
            $this->flags[$key] = (bool) $value;
        }

        return $this;
    }

    public function enabled(string $key): bool
    {
        return $this->flags[$key] ?? false;
    }
}
