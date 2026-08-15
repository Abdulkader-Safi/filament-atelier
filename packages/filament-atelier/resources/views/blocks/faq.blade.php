@php $items = $attributes['items'] ?? []; @endphp
<section data-atelier-block="{{ $id }}" class="px-6 py-16">
    <div class="mx-auto max-w-2xl">
        @if ($heading = $attributes['heading'] ?? null)
            <h2 class="mb-8 text-2xl font-semibold tracking-tight text-balance sm:text-3xl">{{ $heading }}</h2>
        @endif

        <div class="divide-y divide-neutral-200 border-y border-neutral-200">
            @foreach ($items as $item)
                {{-- Plain details, so it works with JS disabled. --}}
                <details class="group py-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium text-neutral-900">
                        {{ $item['question'] ?? '' }}
                        <span class="shrink-0 text-neutral-400 transition group-open:rotate-45">+</span>
                    </summary>
                    <p class="mt-3 text-pretty text-neutral-600">{{ $item['answer'] ?? '' }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
