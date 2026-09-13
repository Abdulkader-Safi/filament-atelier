<?php

declare(strict_types=1);

namespace Safi\Atelier;

use InvalidArgumentException;

/**
 * The page types a panel knows about. Same shape as {@see BlockRegistry}:
 * classes in, keyed by what they call themselves.
 */
class PageTypeRegistry
{
    /** The type every page without one carries. Never registered, never listed. */
    public const DEFAULT = 'page';

    /** @var array<string, class-string<PageType>> */
    protected array $types = [];

    /** @param class-string<PageType>|array<int, class-string<PageType>> $type */
    public function register(string|array $type): static
    {
        foreach ((array) $type as $class) {
            if (! is_subclass_of($class, PageType::class)) {
                throw new InvalidArgumentException(
                    "{$class} must implement ".PageType::class,
                );
            }

            if ($class::type() === self::DEFAULT) {
                throw new InvalidArgumentException(
                    "'".self::DEFAULT."' is the type an ordinary page carries and cannot be registered.",
                );
            }

            $this->types[$class::type()] = $class;
        }

        return $this;
    }

    public function has(?string $type): bool
    {
        return $type !== null && isset($this->types[$type]);
    }

    /** @return class-string<PageType>|null */
    public function resolve(?string $type): ?string
    {
        return $type === null ? null : ($this->types[$type] ?? null);
    }

    /** @return array<string, class-string<PageType>> */
    public function all(): array
    {
        return $this->types;
    }

    /**
     * Key to plural label, for the select on the Collection block.
     *
     * @return array<string, string>
     */
    public function options(): array
    {
        return array_map(fn (string $type) => $type::pluralLabel(), $this->types);
    }

    /**
     * A type's slug prefix for one locale, without slashes.
     *
     * Lives here rather than on the type so a class implementing the
     * interface directly, without the base class, still gets it.
     */
    public function prefix(?string $type, string $locale): ?string
    {
        $class = $this->resolve($type)
            ?? (is_string($type) && is_subclass_of($type, PageType::class) ? $type : null);

        if ($class === null) {
            return null;
        }

        $prefix = $class::prefix();

        if (is_array($prefix)) {
            $prefix = $prefix[$locale] ?? null;
        }

        $prefix = is_string($prefix) ? trim($prefix, '/') : null;

        return $prefix === '' ? null : $prefix;
    }
}
