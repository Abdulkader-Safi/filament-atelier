{{--
    One service in a Collection block. The only file that knows what
    ServiceType called its fields, which is why the type names it through
    cardView() instead of the package guessing.

    It receives $page (the service) and $locale.
--}}
@php
    $image = $page->data('card_image');
    $price = $page->data('starting_price');
@endphp
<a href="{{ $page->url($locale) ?? '#' }}"
   class="group block overflow-hidden rounded-lg border border-neutral-200 transition hover:border-neutral-400">
    @if ($image)
        <img src="{{ Storage::disk(config('atelier.media.disk'))->url($image) }}"
             alt="{{ $page->title }}"
             class="aspect-[3/2] w-full object-cover">
    @endif

    <div class="p-5">
        <h3 class="font-medium text-neutral-900 group-hover:underline">{{ $page->title }}</h3>

        @if ($excerpt = $page->data('excerpt', $locale))
            <p class="mt-2 text-sm text-pretty text-neutral-600">{{ $excerpt }}</p>
        @endif

        @if ($price)
            <p class="mt-3 text-sm text-neutral-500">From AED {{ number_format((float) $price) }}</p>
        @endif
    </div>
</a>
