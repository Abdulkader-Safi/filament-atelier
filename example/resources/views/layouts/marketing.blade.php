{{-- A third shell, to show the menu manager's render side. Same blocks as
     every other layout; only the header and footer differ. The nav is
     partials.nav, a normal view in this app, styled here rather than in a
     vendor-override file, which is what most developers will actually
     reach for. --}}
@php
    $locales = config('atelier.locales', []);
    $dir = $locales[$locale]['dir'] ?? 'ltr';
    $page = $page ?? null;
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
    <header class="border-b border-neutral-200 px-6 py-4">
        @include('partials.nav', ['location' => 'primary', 'locale' => $locale])
    </header>

    <main data-atelier-canvas>
        {!! $blocks !!}
    </main>

    <footer class="border-t border-neutral-200 px-6 py-4">
        @include('partials.nav', ['location' => 'footer', 'locale' => $locale])
    </footer>

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
