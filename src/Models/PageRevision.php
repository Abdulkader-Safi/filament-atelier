<?php

declare(strict_types=1);

namespace Safi\Atelier\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array|null $content
 * @property int|null $created_by
 * @property string|null $label
 */
class PageRevision extends Model
{
    protected $table = 'atelier_page_revisions';

    /** Written once at publish and never edited, so there is nothing to stamp. */
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'content' => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }
}
