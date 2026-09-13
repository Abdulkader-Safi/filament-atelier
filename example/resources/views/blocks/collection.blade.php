@php
    use App\Blocks\CollectionBlock;
    use App\PageTypes\ProductType;

    // The block queries; the view loops. $page is the page this block sits on,
    // which is how "everything except this one" works.
    $items = CollectionBlock::items($attributes, $page ?? null);

    // Each type names its own card, so this view never learns what a service
    // or a product is called its fields.
    $card = ($attributes['page_type'] ?? null) === ProductType::type()
        ? 'products.card'
        : 'services.card';

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

        @if ($items->isEmpty())
            <p class="mt-6 text-slate-600">{{ $attributes['empty'] ?? 'Nothing to show yet.' }}</p>
        @else
            <div class="mt-12 grid grid-cols-1 gap-8 {{ $cols }}">
                @foreach ($items as $item)
                    @include($card, ['page' => $item, 'locale' => $locale])
                @endforeach
            </div>
        @endif
    </div>
</section>
