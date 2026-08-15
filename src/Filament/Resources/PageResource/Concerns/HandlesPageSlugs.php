<?php

declare(strict_types=1);

namespace Safi\Atelier\Filament\Resources\PageResource\Concerns;

use Safi\Atelier\Models\Page;

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

    protected function applySlugs(Page $page): void
    {
        // Always call it. With no slugs typed, setSlugs() generates them from
        // the title, and a page with no slug is unreachable.
        $page->setSlugs($this->slugsToSave ?: array_fill_keys(
            array_keys(config('atelier.locales', [])),
            null,
        ));
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
