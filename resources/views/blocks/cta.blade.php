<section {{ $shared->class(['px-6 py-16']) }}>
    {{-- Painted from the tokens, so the loudest panel on the page follows a
         rebrand rather than staying whatever colour it shipped as. --}}
    <div class="mx-auto max-w-4xl rounded-2xl px-8 py-12 text-center"
         style="background:var(--atelier-color-primary);color:var(--atelier-color-on-primary)">
        @if ($heading = $attributes['heading'] ?? null)
            <h2 class="text-2xl font-semibold tracking-tight text-balance sm:text-3xl">{{ $heading }}</h2>
        @endif

        @if ($body = $attributes['body'] ?? null)
            <p class="mx-auto mt-3 max-w-xl text-pretty opacity-80">{{ $body }}</p>
        @endif

        @if ($label = $attributes['cta_label'] ?? null)
            <a href="{{ \Safi\Atelier\Url::safe($attributes['cta_url'] ?? null) }}"
               class="mt-8 inline-block rounded-md bg-white px-5 py-3 text-sm font-medium transition hover:bg-neutral-100"
               style="color:var(--atelier-color-primary)">
                {{ $label }}
            </a>
        @endif
    </div>
</section>
