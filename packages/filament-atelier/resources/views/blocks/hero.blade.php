@php
    $align = ($attributes['align'] ?? 'left') === 'center';
@endphp
<section
    data-atelier-block="{{ $id }}"
    @class([
        'px-6 py-24 sm:py-32',
        'text-center' => $align,
    ])
>
    <div @class(['mx-auto max-w-3xl', 'mx-auto' => $align])>
        @if ($heading = $attributes['heading'] ?? null)
            <h1 class="text-4xl font-semibold tracking-tight text-balance sm:text-6xl">
                {{ $heading }}
            </h1>
        @endif

        @if ($subheading = $attributes['subheading'] ?? null)
            <p class="mt-6 text-lg text-pretty text-neutral-600 sm:text-xl">
                {{ $subheading }}
            </p>
        @endif

        @if ($label = $attributes['cta_label'] ?? null)
            <div @class(['mt-10 flex gap-4', 'justify-center' => $align])>
                <a
                    href="{{ $attributes['cta_url'] ?? '#' }}"
                    class="rounded-md bg-neutral-900 px-5 py-3 text-sm font-medium text-white transition hover:bg-neutral-700"
                >
                    {{ $label }}
                </a>
            </div>
        @endif
    </div>
</section>
