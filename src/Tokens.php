<?php

declare(strict_types=1);

namespace Safi\Atelier;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Design tokens, emitted once as CSS custom properties and read by both the
 * public page and the editor preview.
 *
 * This is the thing that keeps the preview honest. If the editor and the front
 * end read different sources for colour and spacing, the preview lies and
 * feature 01 was pointless.
 *
 * Blocks store a reference, never a literal:
 *
 *     "background": { "token": "color.surface" }
 *
 * which the renderer turns into `var(--atelier-color-surface)` before the view
 * sees it. Changing the token changes every page that uses it.
 */
class Tokens
{
    /**
     * The shipped set. A host app overrides any subset through
     * `atelier.tokens` and inherits the rest, so an install that predates this
     * config key still gets a full palette.
     *
     * @return array<string, array<string, string>>
     */
    public static function defaults(): array
    {
        return [
            'color' => [
                'primary' => '#171717',
                'on-primary' => '#ffffff',
                'text' => '#171717',
                'muted' => '#737373',
                'surface' => '#ffffff',
                'border' => '#e5e5e5',
            ],
            'font' => [
                // The Latin stack. Arabic swaps in on the RTL side, which is
                // the whole reason this token exists.
                'sans' => 'ui-sans-serif, system-ui, sans-serif',
                'arabic' => '"Noto Sans Arabic", "Segoe UI", Tahoma, sans-serif',
            ],
            'space' => [
                'section' => '6rem',
                'gutter' => '1.5rem',
            ],
            'width' => [
                'container' => '80rem',
                'prose' => '48rem',
            ],
        ];
    }

    /** @return array<string, array<string, string>> */
    public static function all(): array
    {
        return array_replace_recursive(
            static::defaults(),
            config('atelier.tokens') ?: [],
        );
    }

    /**
     * The `<style>` contents for the layout head.
     *
     * Emitted inline rather than as a file because it is under a kilobyte and
     * a separate request for it would cost more than it saves. It also has to
     * be identical in the preview, and an inline block cannot go stale.
     */
    public static function css(): string
    {
        $lines = [];

        foreach (static::all() as $group => $tokens) {
            foreach ($tokens as $name => $value) {
                $lines[] = "--atelier-{$group}-{$name}:{$value}";
            }
        }

        // Arabic mirrors through dir, so the font follows dir rather than a
        // locale code. A host app adding a third RTL language gets it free.
        return ':root{'.implode(';', $lines).'}'
            .'[dir="rtl"]{--atelier-font-sans:var(--atelier-font-arabic)}';
    }

    /** `color.primary` becomes `var(--atelier-color-primary)`. */
    public static function value(string $path): ?string
    {
        if (! Arr::has(static::all(), $path)) {
            return null;
        }

        return 'var(--atelier-'.str_replace('.', '-', $path).')';
    }

    /**
     * One group as Filament select options, e.g. `color` gives
     * `['color.primary' => 'Primary']`.
     *
     * @return array<string, string>
     */
    public static function options(string $group): array
    {
        return collect(static::all()[$group] ?? [])
            ->mapWithKeys(fn (string $value, string $name) => [
                "{$group}.{$name}" => Str::headline($name),
            ])
            ->all();
    }

    /**
     * Turn a stored `{ "token": "color.primary" }` into a CSS value.
     *
     * Anything else passes through untouched, so a block attribute that holds
     * a plain string, a repeater list or null is unaffected.
     */
    public static function resolve(mixed $value): mixed
    {
        if (is_array($value) && isset($value['token']) && is_string($value['token'])) {
            return static::value($value['token']);
        }

        return $value;
    }
}
