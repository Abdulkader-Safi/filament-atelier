<?php

declare(strict_types=1);

namespace Safi\Atelier\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $locale
 * @property string $from_slug
 * @property int $status
 */
class PageRedirect extends Model
{
    protected $table = 'atelier_page_redirects';

    protected $guarded = [];

    protected $casts = [
        'status' => 'integer',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }
}
