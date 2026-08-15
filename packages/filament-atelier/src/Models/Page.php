<?php

declare(strict_types=1);

namespace Safi\Atelier\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $title
 * @property string $status
 * @property array|null $draft_content
 * @property array|null $published_content
 */
class Page extends Model
{
    protected $table = 'atelier_pages';

    protected $guarded = [];

    protected $casts = [
        'draft_content' => 'array',
        'published_content' => 'array',
        'published_at' => 'datetime',
    ];

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

    public function hasUnpublishedChanges(): bool
    {
        return $this->status === 'published'
            && $this->draft() !== $this->published();
    }
}
