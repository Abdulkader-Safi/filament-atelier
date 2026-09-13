@php $items = $attributes['items'] ?? []; @endphp
<section {{ $shared->class(['px-6 py-20']) }}>
    <div class="mx-auto max-w-2xl">
        @if ($heading = $attributes['heading'] ?? null)
            <h2 class="mb-8 text-3xl font-semibold tracking-tight text-balance text-slate-900">{{ $heading }}</h2>
        @endif

        <div class="divide-y divide-slate-200 border-y border-slate-200">
            @foreach ($items as $item)
                {{-- Plain <details>, so it opens with JavaScript disabled. --}}
                <details class="group py-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium text-slate-900">
                        {{ $item['question'] ?? '' }}
                        <span class="shrink-0 text-xl leading-none text-teal-600 transition group-open:rotate-45">+</span>
                    </summary>
                    <p class="mt-3 text-pretty text-slate-600">{{ $item['answer'] ?? '' }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
