@php
    $items = $attributes['items'] ?? [];
    $cols = match ((string) ($attributes['columns'] ?? '3')) {
        '2' => 'sm:grid-cols-2',
        '4' => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };
@endphp
<section {{ $shared->class(['px-6 py-16']) }}>
    <div class="mx-auto max-w-5xl">
        @if ($heading = $attributes['heading'] ?? null)
            <h2 class="text-2xl font-semibold tracking-tight text-balance sm:text-3xl">{{ $heading }}</h2>
        @endif

        @if ($sub = $attributes['subheading'] ?? null)
            <p class="mt-3 max-w-2xl text-pretty text-neutral-600">{{ $sub }}</p>
        @endif

        <div class="mt-10 grid grid-cols-1 gap-8 {{ $cols }}">
            @foreach ($items as $item)
                <div>
                    @if ($icon = $item['icon'] ?? null)
                        @php
                            // A typo in an icon name must not take down a live page.
                            try { $svg = svg($icon, 'mb-3 h-6 w-6 text-neutral-900')->toHtml(); }
                            catch (\Throwable) { $svg = null; }
                        @endphp
                        {!! $svg !!}
                    @endif
                    <h3 class="font-medium text-neutral-900">{{ $item['title'] ?? '' }}</h3>
                    @if ($body = $item['body'] ?? null)
                        <p class="mt-2 text-pretty text-neutral-600">{{ $body }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
