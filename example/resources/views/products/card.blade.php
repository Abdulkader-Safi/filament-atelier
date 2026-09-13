{{-- One product. Same contract as the service card: $page and $locale. --}}
@php
    $image = $page->data('card_image');
    $from = \App\PageTypes\ProductType::fromPrice($page);
    $variations = collect($page->data('variations') ?? [])->filter(fn ($row) => filled($row['name'] ?? null));
@endphp
<a href="{{ $page->url($locale) ?? '#' }}"
   class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:-translate-y-0.5 hover:border-teal-600 hover:shadow-lg hover:shadow-teal-900/5">
    @if ($image)
        <img src="{{ Storage::disk(config('atelier.media.disk'))->url($image) }}"
             alt="{{ $page->title }}"
             class="aspect-square w-full object-cover">
    @else
        <div class="flex aspect-square w-full items-center justify-center bg-slate-50">
            @php
                try { $svg = svg('heroicon-o-cube', 'h-10 w-10 text-slate-400')->toHtml(); }
                catch (\Throwable) { $svg = null; }
            @endphp
            {!! $svg !!}
        </div>
    @endif

    <div class="flex flex-1 flex-col p-6">
        <h3 class="font-semibold text-slate-900 group-hover:text-teal-700">{{ $page->title }}</h3>

        @if ($excerpt = $page->data('excerpt', $locale))
            <p class="mt-2 text-sm text-pretty text-slate-600">{{ $excerpt }}</p>
        @endif

        <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4 text-sm">
            @if ($from)
                <span class="font-semibold text-slate-900">
                    {{ $variations->count() > 1 ? 'From ' : '' }}AED {{ number_format($from) }}
                </span>
            @else
                <span class="text-slate-500">Ask for a price</span>
            @endif

            @if ($variations->count() > 1)
                <span class="text-slate-500">{{ $variations->count() }} sizes</span>
            @endif
        </div>
    </div>
</a>
