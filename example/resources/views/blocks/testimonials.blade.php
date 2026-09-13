@php $items = $attributes['items'] ?? []; @endphp
<section {{ $shared->class(['px-6 py-20']) }}>
    <div class="mx-auto max-w-5xl">
        @if ($heading = $attributes['heading'] ?? null)
            <h2 class="text-3xl font-semibold tracking-tight text-balance text-slate-900">{{ $heading }}</h2>
        @endif

        <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-3">
            @foreach ($items as $item)
                <figure class="flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-6">
                    <blockquote class="flex-1 text-pretty text-slate-700">
                        &ldquo;{{ $item['quote'] ?? '' }}&rdquo;
                    </blockquote>

                    <figcaption class="mt-6 text-sm">
                        <span class="font-semibold text-slate-900">{{ $item['name'] ?? '' }}</span>
                        @if ($role = $item['role'] ?? null)
                            <span class="text-slate-500"> · {{ $role }}</span>
                        @endif
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
