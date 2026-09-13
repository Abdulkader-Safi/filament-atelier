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

    {{-- No editor script here on purpose. The preview controller injects it,
         so a layout you write yourself gets the same behaviour for free. --}}
</body>
</html>
