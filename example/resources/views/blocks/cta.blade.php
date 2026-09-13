<section {{ $shared->class(['px-6 py-16']) }}>
    {{-- Painted from the tokens, so the loudest panel on the site follows a
         rebrand rather than staying whatever colour it was written as. --}}
    <div class="mx-auto max-w-4xl rounded-2xl px-8 py-14 text-center"
         style="background:var(--atelier-color-primary);color:var(--atelier-color-on-primary)">
        @if ($heading = $attributes['heading'] ?? null)
            <h2 class="text-3xl font-semibold tracking-tight text-balance">{{ $heading }}</h2>
        @endif

        @if ($body = $attributes['body'] ?? null)
            <p class="mx-auto mt-3 max-w-xl text-pretty opacity-90">{{ $body }}</p>
        @endif

        @if ($label = $attributes['cta_label'] ?? null)
            <a href="{{ $attributes['cta_url'] ?? '#' }}"
               class="mt-8 inline-block rounded-lg bg-white px-5 py-3 text-sm font-semibold transition hover:bg-slate-100"
               style="color:var(--atelier-color-primary)">
                {{ $label }}
            </a>
        @endif
    </div>
</section>
