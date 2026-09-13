{{--
    Every block view receives: $attributes (already collapsed to $locale and
    with design tokens resolved), $shared (the background and padding controls
    the block opted into), $page (the page being rendered), $locale, $id,
    $node, $editing and $children.
--}}
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
        <div class="absolute inset-0 bg-slate-900/55"></div>
    @endif

    <div @class(['relative mx-auto max-w-3xl', 'text-white' => (bool) $image])>
        @if ($eyebrow = $attributes['eyebrow'] ?? null)
            <p @class([
                'text-sm font-semibold tracking-widest uppercase',
                'text-teal-700' => ! $image,
                'text-white/80' => (bool) $image,
            ])>{{ $eyebrow }}</p>
        @endif

        @if ($heading = $attributes['heading'] ?? null)
            <h1 class="mt-3 text-4xl font-semibold tracking-tight text-balance sm:text-6xl">{{ $heading }}</h1>
        @endif

        @if ($subheading = $attributes['subheading'] ?? null)
            <p @class([
                'mt-6 text-lg text-pretty sm:text-xl',
                'text-slate-600' => ! $image,
                'text-white/90' => (bool) $image,
            ])>{{ $subheading }}</p>
        @endif

        @if ($label = $attributes['cta_label'] ?? null)
            <div @class(['mt-10 flex gap-4', 'justify-center' => $center])>
                {{-- The brand colour comes from the design tokens in
                     config/atelier.php, so a rebrand is one file. --}}
                <a href="{{ \Safi\Atelier\Url::safe($attributes['cta_url'] ?? null) }}"
                   @class([
                       'rounded-lg px-5 py-3 text-sm font-semibold transition hover:opacity-90',
                       'bg-white text-slate-900 hover:bg-slate-100' => (bool) $image,
                   ])
                   @style(['background:var(--atelier-color-primary);color:var(--atelier-color-on-primary)' => ! $image])>
                    {{ $label }}
                </a>
            </div>
        @endif
    </div>
</section>
