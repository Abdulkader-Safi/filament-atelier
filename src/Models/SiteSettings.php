<?php

declare(strict_types=1);

namespace Safi\Atelier\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The site's own details: the organisation behind it, its logo, its profiles,
 * its address. One row, always.
 *
 * @property array|null $data
 */
class SiteSettings extends Model
{
    protected $table = 'atelier_settings';

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * The single row, created on first read.
     *
     * Resolved through the container as a scoped binding rather than memoised
     * in a static: the structured data on every page reads this several times
     * and should not query for each, but a long-running worker or an Octane
     * process must not hold yesterday's address forever. Scoped bindings are
     * flushed between requests; a static is not.
     */
    public static function current(): static
    {
        return app()->has(static::class)
            ? app(static::class)
            : tap(static::query()->firstOrCreate([], ['data' => []]), fn (self $settings) => app()->instance(static::class, $settings));
    }

    /** Drop the resolved row, so the next read goes back to the database. */
    public static function forget(): void
    {
        app()->forgetInstance(static::class);
    }

    /** Keep the container's copy in step with what was just written. */
    protected static function booted(): void
    {
        static::saved(fn (self $settings) => app()->instance(static::class, $settings));
    }

    /**
     * A value from the row, by dot path.
     *
     * Returns null for anything blank, so a caller can use `??` without
     * checking for empty strings the form left behind.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = data_get(static::current()->data, $key);

        return blank($value) ? $default : $value;
    }

    /**
     * A translatable value, collapsed to a locale.
     *
     * Stored the way block attributes are, as a map keyed by locale, falling
     * back to the default locale rather than rendering a hole.
     */
    public static function translated(string $key, string $locale): ?string
    {
        $value = static::get($key);

        if (! is_array($value)) {
            return is_string($value) ? $value : null;
        }

        $fallback = array_key_first(config('atelier.locales', []) ?: [$locale => null]);

        $value = $value[$locale] ?? $value[$fallback] ?? null;

        return blank($value) ? null : (string) $value;
    }
}
