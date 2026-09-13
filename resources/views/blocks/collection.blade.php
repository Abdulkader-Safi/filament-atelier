@php
    use Safi\Atelier\Blocks\CollectionBlock;
    use Safi\Atelier\PageTypeRegistry;

    $items = CollectionBlock::items($attributes, $locale, $page ?? null);

    $type = app(PageTypeRegistry::class)->resolve($attributes['page_type'] ?? null);
    $card = ($type ? $type::cardView() : null) ?? 'atelier::partials.card';

    $cols = match ((string) ($attributes['columns'] ?? '3')) {
        '1' => '',
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

        @if ($items->isEmpty())
            <p class="mt-6 text-neutral-600">{{ $attributes['empty'] ?? 'Nothing to show yet.' }}</p>
        @else
            <div class="mt-10 grid grid-cols-1 gap-8 {{ $cols }}">
                @foreach ($items as $item)
                    {{-- The type's own partial. It is the only thing that knows
                         what the type called its fields. --}}
                    @include($card, ['page' => $item, 'locale' => $locale])
                @endforeach
            </div>
        @endif
    </div>
</section>
