@php
    use Safi\Atelier\Media;
    $logos = $attributes['logos'] ?? [];
@endphp
<section {{ $shared->class(['px-6 py-12']) }}>
    <div class="mx-auto max-w-5xl text-center">
        @if ($heading = $attributes['heading'] ?? null)
            <p class="text-sm font-medium uppercase tracking-widest text-neutral-500">{{ $heading }}</p>
        @endif

        <div class="mt-8 flex flex-wrap items-center justify-center gap-x-10 gap-y-6">
            @foreach ($logos as $logo)
                @php $src = Media::url($logo['image'] ?? null); @endphp
                @if ($src)
                    @if ($url = $logo['url'] ?? null)
                        <a href="{{ $url }}" rel="noopener">
                            <img src="{{ $src }}" alt="{{ $logo['name'] ?? '' }}" loading="lazy"
                                 class="h-8 w-auto opacity-60 transition hover:opacity-100" height="32">
                        </a>
                    @else
                        <img src="{{ $src }}" alt="{{ $logo['name'] ?? '' }}" loading="lazy"
                             class="h-8 w-auto opacity-60" height="32">
                    @endif
                @endif
            @endforeach
        </div>
    </div>
</section>
