<?php

declare(strict_types=1);

namespace Safi\Atelier\Schema;

/**
 * A JSON-LD `@graph`: a bag of nodes that reference each other by `@id`.
 *
 * One script tag per page holding one graph, rather than a script tag per
 * schema. Nodes point at each other instead of repeating the organisation on
 * every node, and there is one place to look when something is wrong.
 */
class Graph
{
    /** @var array<string, array<string, mixed>> */
    protected array $nodes = [];

    /**
     * Add a node, keyed by its `@id`.
     *
     * Adding the same `@id` twice merges rather than duplicating, so two FAQ
     * blocks on one page contribute to one `FAQPage` instead of emitting two
     * of them. A node with no `@id` is appended under a generated key.
     *
     * @param  array<string, mixed>  $node
     */
    public function add(array $node): static
    {
        $node = static::node($node);

        if ($node === null) {
            return $this;
        }

        $id = $node['@id'] ?? '#node-'.count($this->nodes);

        $this->nodes[$id] = array_key_exists($id, $this->nodes)
            ? static::merge($this->nodes[$id], $node)
            : $node;

        return $this;
    }

    /**
     * Merge a node into one already in the graph.
     *
     * Lists concatenate, so two FAQ blocks on one page contribute their
     * questions to a single `FAQPage`. Everything else is overwritten by the
     * later value. `array_merge_recursive` cannot be used here: it would turn
     * two identical `@type` strings into an array of two strings, which is
     * valid JSON and invalid schema.
     *
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    protected static function merge(array $existing, array $incoming): array
    {
        foreach ($incoming as $key => $value) {
            $current = $existing[$key] ?? null;

            $existing[$key] = match (true) {
                is_array($current) && is_array($value) && array_is_list($current) && array_is_list($value) => [...$current, ...$value],
                is_array($current) && is_array($value) => static::merge($current, $value),
                default => $value,
            };
        }

        return $existing;
    }

    public function has(string $id): bool
    {
        return isset($this->nodes[$id]);
    }

    public function isEmpty(): bool
    {
        return $this->nodes === [];
    }

    /** @return array<int, array<string, mixed>> */
    public function nodes(): array
    {
        return array_values($this->nodes);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => $this->nodes(),
        ];
    }

    /**
     * The graph as JSON, safe to drop inside a `<script>` block.
     *
     * The escaping is a security boundary, not formatting. This lands inside
     * a script element, so a client typing `</script>` into a heading would
     * otherwise close it and everything after it is markup they wrote.
     * HEX_TAG handles that; HEX_AMP, HEX_APOS and HEX_QUOT close the same
     * class of hole.
     *
     * UNESCAPED_UNICODE is not cosmetic either: without it every Arabic
     * heading becomes `\uXXXX` and the payload triples in size.
     */
    public function toJson(): string
    {
        return json_encode(
            $this->toArray(),
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT,
        ) ?: '';
    }

    /**
     * A node, or null when it carries nothing but its own bookkeeping.
     *
     * `['@type' => 'PostalAddress']` with every field empty validates and says
     * nothing, and emitting it is worse than emitting no address at all. Every
     * nested node goes through here for the same reason.
     *
     * Deliberately separate from prune(), because a nested `['@id' => ...]` is
     * a reference to another node and is the whole point of a graph, so prune
     * must not touch it.
     *
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>|null
     */
    public static function node(array $node): ?array
    {
        $node = static::prune($node);

        $keys = array_keys($node);

        if ($node === [] || $keys === ['@type'] || $keys === ['@id'] || $keys === ['@type', '@id'] || $keys === ['@id', '@type']) {
            return null;
        }

        return $node;
    }

    /**
     * Drop empty values, recursively.
     *
     * A node full of nulls validates and says nothing, and every optional
     * field on every schema would otherwise need a guard at the call site.
     * `0` and `false` are kept: a price of zero is a fact.
     *
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    public static function prune(array $node): array
    {
        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $value = static::prune($value);

                // A nested node left holding only its own type says nothing:
                // a Person with no name, an address with no address. A bare
                // `@id` is different, that is a reference to another node and
                // is the whole point of a graph, so it stays.
                if (array_keys($value) === ['@type']) {
                    $value = [];
                }
            }

            if ($value === null || $value === '' || $value === []) {
                unset($node[$key]);

                continue;
            }

            $node[$key] = $value;
        }

        return $node;
    }
}
