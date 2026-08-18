<?php

declare(strict_types=1);

namespace Safi\Atelier;

use Illuminate\Support\Str;

/**
 * The layouts a page can be wrapped in.
 *
 * A site is rarely one shell. Marketing pages want a navbar and a footer,
 * documentation wants a sidebar, a landing page often wants neither. The
 * blocks are the same either way, so the shell is a per-page choice rather
 * than a different set of blocks.
 *
 * Registered from the panel provider, the same way blocks are:
 *
 *     AtelierPlugin::make()
 *         ->layouts([
 *             'default' => ['label' => 'Navbar and footer', 'view' => 'layouts.site'],
 *             'docs' => ['label' => 'Sidebar', 'view' => 'layouts.docs'],
 *         ]);
 *
 * A layout is a key, a label and a Blade view, so it is a map rather than a
 * class per layout. Blocks earn a class because they carry a schema, an icon,
 * a category and translatable keys. A layout carries none of that, and a class
 * holding three strings is ceremony.
 */
class LayoutRegistry
{
    /** @var array<string, array{label: string, view: string, description: ?string}> */
    protected array $layouts = [];

    /**
     * Register layouts.
     *
     * The long form is `['label' => ..., 'view' => ..., 'description' => ...]`.
     * The short form is just the view name, and the label comes from the key.
     *
     * @param  array<string, string|array{label?: string, view: string, description?: string}>  $layouts
     */
    public function register(array $layouts): static
    {
        foreach ($layouts as $key => $layout) {
            if (is_string($layout)) {
                $layout = ['view' => $layout];
            }

            $this->layouts[$key] = [
                'label' => $layout['label'] ?? Str::headline($key),
                'view' => $layout['view'],
                'description' => $layout['description'] ?? null,
            ];
        }

        return $this;
    }

    public function has(string $key): bool
    {
        return isset($this->layouts[$key]);
    }

    /**
     * The Blade view for a key, or null for a key nobody registered.
     *
     * Null on a miss rather than a throw: a page keeps a layout key after the
     * developer removes that layout from the panel provider, and a 500 on
     * every public page is a bad way to find out.
     */
    public function view(?string $key): ?string
    {
        return $key === null ? null : ($this->layouts[$key]['view'] ?? null);
    }

    /**
     * Key to label, for the select on the page settings screen.
     *
     * @return array<string, string>
     */
    public function options(): array
    {
        return array_map(fn (array $layout) => $layout['label'], $this->layouts);
    }

    /** @return array<string, string> */
    public function descriptions(): array
    {
        return array_filter(array_map(fn (array $layout) => $layout['description'], $this->layouts));
    }

    /** @return array<string, array{label: string, view: string, description: ?string}> */
    public function all(): array
    {
        return $this->layouts;
    }
}
