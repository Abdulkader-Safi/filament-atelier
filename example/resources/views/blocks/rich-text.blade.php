<section {{ $shared->class(['px-6 py-20']) }}>
    <div class="mx-auto max-w-2xl">
        @if ($heading = $attributes['heading'] ?? null)
            <h2 class="text-3xl font-semibold tracking-tight text-balance text-slate-900">{{ $heading }}</h2>
        @endif

        {{-- The editor's HTML, rendered as HTML. It comes from the panel, not
             from a visitor, which is the only reason this is not escaped. --}}
        <div class="prose prose-slate mt-6 max-w-none prose-a:text-teal-700">
            {!! $attributes['body'] ?? '' !!}
        </div>
    </div>
</section>
