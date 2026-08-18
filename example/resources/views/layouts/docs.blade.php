{{-- A second shell, to show layout switching. Sidebar on the start side,
     content beside it. The blocks are identical to the ones the marketing
     layout renders; only the shell differs. --}}
@php
    $locales = config('atelier.locales', []);
    $dir = $locales[$locale]['dir'] ?? 'ltr';
    // A layout should not assume it is being rendered by Atelier's controllers.
    $page = $page ?? null;
    $nav = \Safi\Atelier\Models\Page::query()->where('status', 'published')->orderBy('title')->get();
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @include('atelier::partials.meta')

    @vite(['resources/css/app.css'])

    @include('atelier::partials.tokens')
</head>
<body class="bg-white text-neutral-900 antialiased">
    <div class="mx-auto flex max-w-7xl gap-10 px-6 py-10">
        <aside class="hidden w-56 shrink-0 lg:block">
            <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-neutral-500">Pages</p>
            <nav class="space-y-1 border-s border-neutral-200 ps-4">
                @foreach ($nav as $item)
                    @if ($url = $item->url($locale))
                        <a href="{{ $url }}"
                           @class([
                               'block py-1 text-sm',
                               'font-medium text-neutral-900' => $page && $item->is($page),
                               'text-neutral-500 hover:text-neutral-900' => ! ($page && $item->is($page)),
                           ])>{{ $item->title }}</a>
                    @endif
                @endforeach
            </nav>
        </aside>

        <main class="min-w-0 flex-1" data-atelier-canvas>
            {!! $blocks !!}
        </main>
    </div>

    @if ($preview ?? false)
        <script>
            document.addEventListener('click', (e) => {
                const el = e.target.closest('[data-atelier-block]');
                if (el) parent.postMessage({ atelier: 'select', id: el.dataset.atelierBlock }, '*');
            });
        </script>
    @endif
</body>
</html>
