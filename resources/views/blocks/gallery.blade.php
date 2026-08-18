@php
    use Safi\Atelier\Media;
    $images = $attributes['images'] ?? [];
    $cols = match ((string) ($attributes['columns'] ?? '3')) {
        '2' => 'sm:grid-cols-2',
        '4' => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };
@endphp
<section {{ $shared->class(['px-6 py-16']) }}>
    <div class="mx-auto max-w-5xl">
        @if ($heading = $attributes['heading'] ?? null)
            <h2 class="mb-8 text-2xl font-semibold tracking-tight text-balance sm:text-3xl">{{ $heading }}</h2>
        @endif

        @if (filled($images))
            <div class="grid grid-cols-1 gap-4 {{ $cols }}">
                @foreach ($images as $item)
                    @if ($src = Media::url($item['image'] ?? null))
                        <img src="{{ $src }}" alt="{{ $item['alt'] ?? '' }}" loading="lazy"
                             class="aspect-4/3 w-full rounded-lg object-cover" width="800" height="600">
                    @endif
                @endforeach
            </div>
        @else
            <p class="text-sm text-neutral-400">No images yet.</p>
        @endif
    </div>
</section>
