@php
    $items = $attributes['items'] ?? [];
    $cols = match ((string) ($attributes['columns'] ?? '3')) {
        '2' => 'sm:grid-cols-2',
        '4' => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };
@endphp
<section {{ $shared->class(['px-6 py-20']) }}>
    <div class="mx-auto max-w-5xl">
        @if ($heading = $attributes['heading'] ?? null)
            <h2 class="text-3xl font-semibold tracking-tight text-balance text-slate-900">{{ $heading }}</h2>
        @endif

        @if ($sub = $attributes['subheading'] ?? null)
            <p class="mt-3 max-w-2xl text-pretty text-slate-600">{{ $sub }}</p>
        @endif

        <div class="mt-12 grid grid-cols-1 gap-10 {{ $cols }}">
            @foreach ($items as $item)
                <div>
                    @if ($icon = $item['icon'] ?? null)
                        @php
                            // A typo in an icon name must not take a live page down.
                            try { $svg = svg($icon, 'h-6 w-6 text-teal-600')->toHtml(); }
                            catch (\Throwable) { $svg = null; }
                        @endphp
                        <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-teal-50">{!! $svg !!}</div>
                    @endif

                    <h3 class="font-semibold text-slate-900">{{ $item['title'] ?? '' }}</h3>

                    @if ($body = $item['body'] ?? null)
                        <p class="mt-2 text-pretty text-slate-600">{{ $body }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
