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

            $this->slugs()->updateOrCreate(['locale' => $locale], ['slug' => $slug]);
        }

        $this->unsetRelation('slugs');
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
