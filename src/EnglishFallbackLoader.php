<?php

declare(strict_types=1);

namespace Safi\Atelier;

use Illuminate\Contracts\Translation\Loader;

/**
 * Fills any panel string missing from the current locale with the English
 * one, whatever the host app's fallback_locale is.
 *
 * English ships with the package and is complete, so it is the one locale
 * every panel string is known to exist in. Laravel only falls back to the
 * app's fallback_locale: a host that sets it to `ar` and runs the panel in
 * Arabic would otherwise see raw keys like `filament-atelier::atelier.x`.
 *
 * Scoped to Atelier's namespace, so the host's own strings resolve exactly
 * as before. Wraps the loader rather than using the Translator's missing-key
 * or locale callbacks, because each of those holds one callback for the
 * whole app and setting it would replace the host's.
 */
class EnglishFallbackLoader implements Loader
{
    public const NAMESPACE = 'filament-atelier';

    public function __construct(private Loader $loader) {}

    public function load($locale, $group, $namespace = null)
    {
        $lines = $this->loader->load($locale, $group, $namespace);

        if ($namespace !== self::NAMESPACE || $locale === 'en') {
            return $lines;
        }

        // The current locale wins key by key; English only fills the gaps.
        return array_replace_recursive($this->loader->load('en', $group, $namespace), $lines);
    }

    public function addNamespace($namespace, $hint)
    {
        $this->loader->addNamespace($namespace, $hint);
    }

    public function addJsonPath($path)
    {
        $this->loader->addJsonPath($path);
    }

    public function namespaces()
    {
        return $this->loader->namespaces();
    }

    /** FileLoader has methods outside the contract (addPath, jsonPaths) the Translator calls. */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->loader->{$method}(...$arguments);
    }
}
