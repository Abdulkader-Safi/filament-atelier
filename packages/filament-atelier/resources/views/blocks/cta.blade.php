<section data-atelier-block="{{ $id }}" class="px-6 py-16">
    <div class="mx-auto max-w-4xl rounded-2xl bg-neutral-900 px-8 py-12 text-center text-white">
        @if ($heading = $attributes['heading'] ?? null)
            <h2 class="text-2xl font-semibold tracking-tight text-balance sm:text-3xl">{{ $heading }}</h2>
        @endif

        @if ($body = $attributes['body'] ?? null)
            <p class="mx-auto mt-3 max-w-xl text-pretty text-white/80">{{ $body }}</p>
        @endif

        @if ($label = $attributes['cta_label'] ?? null)
            <a href="{{ $attributes['cta_url'] ?? '#' }}"
               class="mt-8 inline-block rounded-md bg-white px-5 py-3 text-sm font-medium text-neutral-900 transition hover:bg-neutral-100">
                {{ $label }}
            </a>
        @endif
    </div>
</section>
