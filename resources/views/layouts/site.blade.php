@php
    $locales = config('atelier.locales', []);
    $dir = $locales[$locale]['dir'] ?? 'ltr';
    $page = $page ?? null;
    $isPreview = $preview ?? false;
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @include('atelier::partials.meta')

    {{-- The public stylesheet, and the same one the editor preview loads.
         If these ever diverge, the preview stops being worth having. --}}
    @vite(['resources/css/app.css'])

    {{-- After the stylesheet so the tokens win. --}}
    @include('atelier::partials.tokens')
</head>
<body class="bg-white text-neutral-900 antialiased">
    <main data-atelier-canvas>
        {!! $blocks !!}
    </main>

    @if ($isPreview)
        {{-- Editor plumbing only. Never reaches a public page. --}}
        <script>
            document.addEventListener('click', (e) => {
                const section = e.target.closest('[data-atelier-block]')
                if (!section) return
                e.preventDefault()
                parent.postMessage({ atelier: 'select', id: section.dataset.atelierBlock }, '*')
            })
        </script>
    @endif
</body>
</html>
