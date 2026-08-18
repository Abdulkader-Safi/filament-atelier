<?php

declare(strict_types=1);

namespace Safi\Atelier\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property string $title
 * @property string $status
 * @property array|null $draft_content
 * @property array|null $published_content
 * @property array|null $seo
 */
class Page extends Model
{
    protected $table = 'atelier_pages';

    protected $guarded = [];

    protected $casts = [
        'draft_content' => 'array',
        'published_content' => 'array',
        'seo' => 'array',
        'published_at' => 'datetime',
    ];

    public function slugs(): HasMany
    {
        return $this->hasMany(PageSlug::class, 'page_id');
    }

    /** Old URLs that still point here. Written when a slug changes. */
    public function redirects(): HasMany
    {
        return $this->hasMany(PageRedirect::class, 'page_id');
    }

    /** The working block tree. Always an array, never null. */
    public function draft(): array
    {
        return $this->draft_content ?? [];
    }

    /** The frozen block tree the public route renders. */
    public function published(): array
    {
        return $this->published_content ?? [];
    }

    public function publish(): void
    {
        $this->forceFill([
            'published_content' => $this->draft(),
            'status' => 'published',
            'published_at' => now(),
        ])->save();

        $this->snapshot();
    }

    public function unpublish(): void
    {
        $this->forceFill(['status' => 'draft', 'published_at' => null])->save();
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function hasUnpublishedChanges(): bool
    {
        return $this->isPublished() && $this->draft() !== $this->published();
    }

    // Revisions ------------------------------------------------------------

    public function revisions(): HasMany
    {
        return $this->hasMany(PageRevision::class, 'page_id')->latest('id');
    }

    /**
     * Freeze what was just published, then prune.
     *
     * Snapshotting the published tree rather than the draft means a revision
     * is always something that was once live, which is what "put it back"
     * means to the person asking for it.
     */
    public function snapshot(?string $label = null): PageRevision
    {
        $revision = $this->revisions()->create([
            'content' => $this->published(),
            'created_by' => auth()->id(),
            'label' => $label,
        ]);

        $this->pruneRevisions();

        return $revision;
    }

    /** Keep the newest N. Without this the table grows for the life of the site. */
    protected function pruneRevisions(): void
    {
        $keep = (int) config('atelier.revisions.keep', 20);

        if ($keep < 1) {
            return;
        }

        $oldest = $this->revisions()->skip($keep)->take(PHP_INT_MAX)->pluck('id');

        if ($oldest->isNotEmpty()) {
            PageRevision::whereKey($oldest)->delete();
        }
    }

    /**
     * Copy a revision back into the draft.
     *
     * Deliberately the draft and not the published column: restoring is an
     * undo the editor then looks at and publishes, not a silent change to the
     * live site.
     */
    public function restoreRevision(PageRevision $revision): void
    {
        $this->forceFill(['draft_content' => $revision->content ?? []])->save();
    }

    // Slugs ----------------------------------------------------------------

    public function slug(string $locale): ?string
    {
        return $this->slugs->firstWhere('locale', $locale)?->slug;
    }

    /** @param array<string, string|null> $slugs keyed by locale */
    public function setSlugs(array $slugs): void
    {
        foreach ($slugs as $locale => $slug) {
            $slug = trim((string) $slug, " \t\n\r\0\x0B/");

            if ($slug === '') {
                $slug = Str::slug($this->title) ?: 'page-'.$this->getKey();
            }

            $previous = $this->slug($locale);

            // This page is taking the slug, so any redirect still pointing an
            // old URL here is now a loop waiting to happen. Whoever claims a
            // slug owns it.
            PageRedirect::where('locale', $locale)->where('from_slug', $slug)->delete();

            $this->slugs()->updateOrCreate(['locale' => $locale], ['slug' => $slug]);

            if ($previous !== null && $previous !== $slug) {
                // Keyed on the unique pair rather than on this page's rows, so
                // it can never collide with a redirect another page left behind.
                PageRedirect::updateOrCreate(
                    ['locale' => $locale, 'from_slug' => $previous],
                    ['page_id' => $this->getKey(), 'status' => 301],
                );
            }

            // The next locale reads its own previous slug, so don't hand it a
            // collection that predates the write above.
            $this->unsetRelation('slugs');
        }
    }

    /** The public URL for a locale. The first configured locale has no prefix. */
    public function url(string $locale): ?string
    {
        $slug = $this->slug($locale);

        if ($slug === null) {
            return null;
        }

        return $locale === array_key_first(config('atelier.locales'))
            ? url($slug)
            : url("{$locale}/{$slug}");
    }

    // SEO ------------------------------------------------------------------

    public function seo(string $locale, string $key): ?string
    {
        return data_get($this->seo, "{$locale}.{$key}") ?: null;
    }

    /** Falls back to the page title so an unfilled field still renders something sane. */
    public function metaTitle(string $locale): string
    {
        return $this->seo($locale, 'meta_title') ?? $this->title;
    }
}
