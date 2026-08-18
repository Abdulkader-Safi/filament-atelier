@php
    use Safi\Atelier\Media;
    $center = ($attributes['align'] ?? 'left') === 'center';
    $image = Media::url($attributes['image'] ?? null);
@endphp
<section {{ $shared
    ->class(['relative overflow-hidden px-6 py-24 sm:py-32', 'text-center' => $center])
    ->style([
        "background-image:url('{$image}')" => (bool) $image,
        'background-size:cover;background-position:center' => (bool) $image,
    ]) }}>
    @if ($image)
        <div class="absolute inset-0 bg-black/50"></div>
    @endif

    <div @class(['relative mx-auto max-w-3xl', 'text-white' => (bool) $image])>
        @if ($eyebrow = $attributes['eyebrow'] ?? null)
            <p @class(['text-sm font-semibold uppercase tracking-widest', 'text-neutral-500' => ! $image, 'text-white/80' => (bool) $image])>
                {{ $eyebrow }}
            </p>
        @endif

        @if ($heading = $attributes['heading'] ?? null)
            <h1 class="mt-3 text-4xl font-semibold tracking-tight text-balance sm:text-6xl">{{ $heading }}</h1>
        @endif

        @if ($subheading = $attributes['subheading'] ?? null)
            <p @class(['mt-6 text-lg text-pretty sm:text-xl', 'text-neutral-600' => ! $image, 'text-white/90' => (bool) $image])>
                {{ $subheading }}
            </p>
        @endif

        @if ($label = $attributes['cta_label'] ?? null)
            <div @class(['mt-10 flex gap-4', 'justify-center' => $center])>
                <a href="{{ $attributes['cta_url'] ?? '#' }}"
                   @class(['rounded-md px-5 py-3 text-sm font-medium transition', 'bg-neutral-900 text-white hover:bg-neutral-700' => ! $image, 'bg-white text-neutral-900 hover:bg-neutral-100' => (bool) $image])>
                    {{ $label }}
                </a>
            </div>
        @endif
    </div>
</section>
