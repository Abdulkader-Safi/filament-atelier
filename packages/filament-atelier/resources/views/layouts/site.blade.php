@php
    $dir = config("atelier.locales.{$locale}.dir", 'ltr');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>

    @if ($preview ?? false)
        <meta name="robots" content="noindex, nofollow">
    @endif

    {{-- The public stylesheet, and the same one the editor preview loads.
         If these ever diverge, the preview stops being worth having. --}}
    @vite(['resources/css/app.css'])
</head>
<body class="bg-white text-neutral-900 antialiased">
    <main data-atelier-canvas>
        {!! $blocks !!}
    </main>

    @if ($preview ?? false)
        {{-- Editor plumbing only. Never reaches a public page. --}}
        <script>
            document.addEventListener('click', (e) => {
                const section = e.target.closest('[data-atelier-block]');
                if (!section) return;
                e.preventDefault();
                parent.postMessage({ atelier: 'select', id: section.dataset.atelierBlock }, '*');
            });
        </script>
    @endif
</body>
</html>
