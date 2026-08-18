<section {{ $shared->class(['px-6 py-16']) }}>
    <div class="mx-auto max-w-2xl">
        @if ($heading = $attributes['heading'] ?? null)
            <h2 class="text-2xl font-semibold tracking-tight text-balance sm:text-3xl">
                {{ $heading }}
            </h2>
        @endif

        @if ($body = $attributes['body'] ?? null)
            <div class="mt-6 space-y-4 text-pretty text-neutral-700 leading-relaxed">
                {!! $body !!}
            </div>
        @endif
    </div>
</section>
