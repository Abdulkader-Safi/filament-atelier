<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Enquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Safi\Atelier\Models\Page;

/**
 * The one write path the public site has, so everything arriving here is
 * treated as hostile until validated.
 *
 * Prices are never taken from the form. The posted option is matched against
 * what the page itself stores, and the price comes from there, because a
 * hidden input is a suggestion from the browser and nothing more.
 */
class EnquiryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'page_id' => ['required', 'integer', 'exists:atelier_pages,id'],
            'option' => ['nullable', 'string', 'max:120'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'message' => ['nullable', 'string', 'max:2000'],
            // A field a person never sees and a bot fills in.
            'website' => ['prohibited'],
        ]);

        $page = Page::findOrFail($data['page_id']);

        abort_unless($page->isPublished(), 404);

        $kind = $page->type === 'product' ? 'product' : 'service';

        [$option, $price] = $this->priced($page, $kind, $data['option'] ?? null);

        $quantity = $kind === 'product' ? ($data['quantity'] ?? 1) : 1;

        Enquiry::create([
            'kind' => $kind,
            'page_id' => $page->getKey(),
            'page_title' => $page->title,
            'option' => $option,
            'unit_price' => $price,
            'quantity' => $quantity,
            'total' => $price === null ? null : $price * $quantity,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'] ?? null,
        ]);

        return back()->with('enquiry', 'Thanks. We will come back to you within one working day.');
    }

    /**
     * The chosen option's own price, read off the page.
     *
     * An option the page does not offer is dropped rather than trusted, and
     * the request still lands: a mismatch is worth a phone call, not a 422 in
     * someone's face.
     *
     * @return array{0: ?string, 1: ?float}
     */
    protected function priced(Page $page, string $kind, ?string $option): array
    {
        if ($option === null || $option === '') {
            $price = $kind === 'product' ? (float) $page->data('price', null, 0) : 0.0;

            return [null, $price > 0 ? $price : null];
        }

        $rows = $kind === 'product'
            ? ($page->data('variations') ?? [])
            : ($page->data('tiers') ?? []);

        foreach ($rows as $row) {
            if (($row['name'] ?? null) === $option) {
                return [$option, isset($row['price']) ? (float) $row['price'] : null];
            }
        }

        return [null, null];
    }
}
