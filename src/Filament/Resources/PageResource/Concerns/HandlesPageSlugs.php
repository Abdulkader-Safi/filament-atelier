<?php

declare(strict_types=1);

namespace Safi\Atelier\Filament\Resources\PageResource\Concerns;

use Safi\Atelier\Models\Page;
use Safi\Atelier\PageTypeRegistry;

/**
 * Slugs live in their own table, but the form edits them as `slugs.{locale}`,
 * which arrives as a top-level `slugs` key in the form data. Left in place it
 * reaches the insert as a column that does not exist.
 *
 * Both the create and the edit screen need the same strip-then-apply, so it
 * lives here rather than being written twice and forgotten a third time.
 */
trait HandlesPageSlugs
{
    /** @var array<string, string|null> */
    protected array $slugsToSave = [];

    protected function pullSlugs(array $data): array
    {
        $this->slugsToSave = $data['slugs'] ?? [];
        unset($data['slugs']);

        return $data;
    }

    /** @param array<string, string|null> $prefixes keyed by locale, create only */
    protected function applySlugs(Page $page, array $prefixes = []): void
    {
        // Always call it. With no slugs typed, setSlugs() generates them from
        // the title, and a page with no slug is unreachable.
        $page->setSlugs($this->slugsToSave ?: array_fill_keys(
            array_keys(config('atelier.locales', [])),
            null,
        ), $prefixes);
    }

    /**
     * The page type's slug prefix per locale.
     *
     * Read from the record rather than the resource so it is right whichever
     * screen calls it, and empty for an ordinary page.
     *
     * @return array<string, string|null>
     */
    protected function prefixesFor(Page $page): array
    {
        $registry = app(PageTypeRegistry::class);

        $prefixes = [];

        foreach (array_keys(config('atelier.locales', [])) as $locale) {
            $prefixes[$locale] = $registry->prefix($page->type, $locale);
        }

        return array_filter($prefixes);
    }

    protected function slugsForForm(Page $page): array
    {
        $slugs = [];

        foreach (array_keys(config('atelier.locales', [])) as $locale) {
            $slugs[$locale] = $page->slug($locale);
        }

        return $slugs;
    }
}
