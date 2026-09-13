<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Safi\Atelier\Models\Page;

/**
 * A request from the public site: a quote for a service, or an order for a
 * product. No payment, no cart. The client reads them in the panel and picks
 * up the phone.
 *
 * @property string $kind
 * @property string $page_title
 * @property string|null $option
 * @property string $status
 */
class Enquiry extends Model
{
    public const KINDS = ['service' => 'Service', 'product' => 'Product'];

    public const STATUSES = [
        'new' => 'New',
        'contacted' => 'Contacted',
        'won' => 'Won',
        'lost' => 'Lost',
    ];

    protected $guarded = [];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'quantity' => 'integer',
    ];

    /** Null once the page it came from is deleted. The title is kept. */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    public function scopeOfKind(Builder $query, string $kind): Builder
    {
        return $query->where('kind', $kind);
    }

    public function scopeWon(Builder $query): Builder
    {
        return $query->where('status', 'won');
    }
}
