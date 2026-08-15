@php
    use Safi\Atelier\Media;
    $items = $attributes['items'] ?? [];
@endphp
<section data-atelier-block="{{ $id }}" class="px-6 py-16">
    <div class="mx-auto max-w-5xl">
        @if ($heading = $attributes['heading'] ?? null)
            <h2 class="mb-10 text-2xl font-semibold tracking-tight text-balance sm:text-3xl">{{ $heading }}</h2>
        @endif

        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
            @foreach ($items as $item)
                <figure class="rounded-xl border border-neutral-200 p-6">
                    <blockquote class="text-pretty text-neutral-800">&ldquo;{{ $item['quote'] ?? '' }}&rdquo;</blockquote>
                    <figcaption class="mt-4 flex items-center gap-3">
                        @if ($avatar = Media::url($item['avatar'] ?? null))
                            <img src="{{ $avatar }}" alt="" loading="lazy"
                                 class="h-10 w-10 rounded-full object-cover" width="80" height="80">
                        @endif
                        <div>
                            <div class="text-sm font-medium text-neutral-900">{{ $item['name'] ?? '' }}</div>
                            @if ($role = $item['role'] ?? null)
                                <div class="text-sm text-neutral-500">{{ $role }}</div>
                            @endif
                        </div>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
