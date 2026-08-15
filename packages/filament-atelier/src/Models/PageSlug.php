<?php

declare(strict_types=1);

namespace Safi\Atelier\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSlug extends Model
{
    protected $table = 'atelier_page_slugs';

    protected $guarded = [];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }
}
