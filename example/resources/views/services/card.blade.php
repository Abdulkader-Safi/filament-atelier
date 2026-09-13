{{--
    One service in a Collection block or on the services index. The only file
    that knows what ServiceType called its fields, which is why the type names
    it through cardView() rather than the package guessing.

    It receives $page (the service) and $locale.
--}}
@php
    $image = $page->data('card_image');
    $from = \App\PageTypes\ServiceType::fromPrice($page);
    $icon = $page->data('icon');
@endphp
<a href="{{ $page->url($locale) ?? '#' }}"
   class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:-translate-y-0.5 hover:border-teal-600 hover:shadow-lg hover:shadow-teal-900/5">
    @if ($image)
        <img src="{{ Storage::disk(config('atelier.media.disk'))->url($image) }}"
             alt="{{ $page->title }}"
             class="aspect-[3/2] w-full object-cover">
    @else
        <div class="flex aspect-[3/2] w-full items-center justify-center bg-teal-50">
            @php
                try { $svg = svg($icon ?: 'heroicon-o-sparkles', 'h-10 w-10 text-teal-600')->toHtml(); }
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
                <span class="font-semibold text-slate-900">From AED {{ number_format($from) }}</span>
            @else
                <span class="text-slate-500">Ask for a price</span>
            @endif

            @if ($duration = $page->data('duration'))
                <span class="text-slate-500">{{ $duration }}</span>
            @endif
        </div>
    </div>
</a>
