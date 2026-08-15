@php
    use Safi\Atelier\Media;
    $src = Media::url($attributes['image'] ?? null);
    $max = match ($attributes['width'] ?? 'container') {
        'wide' => 'max-w-5xl',
        'full' => 'max-w-none',
        default => 'max-w-3xl',
    };
@endphp
<section data-atelier-block="{{ $id }}" class="px-6 py-12">
    <figure class="mx-auto {{ $max }}">
        @if ($src)
            <img src="{{ $src }}" alt="{{ $attributes['alt'] ?? '' }}" loading="lazy"
                 class="w-full rounded-lg object-cover" width="1600" height="900">
        @else
            <div class="flex aspect-video w-full items-center justify-center rounded-lg bg-neutral-100 text-sm text-neutral-400">
                No image chosen
            </div>
        @endif

        @if ($caption = $attributes['caption'] ?? null)
            <figcaption class="mt-3 text-center text-sm text-neutral-500">{{ $caption }}</figcaption>
        @endif
    </figure>
</section>
