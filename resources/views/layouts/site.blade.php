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

    <title>{{ $title ?? config('app.name') }}</title>

    @if ($isPreview)
        <meta name="robots" content="noindex, nofollow">
    @elseif ($page)
        @php
            $description = $page->seo($locale, 'meta_description');
            $ogImage = \Safi\Atelier\Media::url($page->seo($locale, 'og_image'));
            $canonical = $page->seo($locale, 'canonical') ?: $page->url($locale);
        @endphp

        @if ($description)
            <meta name="description" content="{{ $description }}">
        @endif

        @if ($canonical)
            <link rel="canonical" href="{{ $canonical }}">
        @endif

        {{-- Each locale points at the other, so neither competes with itself. --}}
        @foreach ($locales as $code => $config)
            @if ($url = $page->url($code))
                <link rel="alternate" hreflang="{{ $code }}" href="{{ $url }}">
            @endif
        @endforeach

        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $title }}">
        @if ($description)
            <meta property="og:description" content="{{ $description }}">
        @endif
        @if ($canonical)
            <meta property="og:url" content="{{ $canonical }}">
        @endif
        @if ($ogImage)
            <meta property="og:image" content="{{ $ogImage }}">
            <meta name="twitter:card" content="summary_large_image">
        @else
            <meta name="twitter:card" content="summary">
        @endif
        <meta name="twitter:title" content="{{ $title }}">
        @if ($description)
            <meta name="twitter:description" content="{{ $description }}">
        @endif
    @endif

    {{-- The public stylesheet, and the same one the editor preview loads.
         If these ever diverge, the preview stops being worth having. --}}
    @vite(['resources/css/app.css'])

    {{-- Design tokens, after the stylesheet so they win, and inline because
         the whole block is under a kilobyte. Both the preview and the public
         page read these, which is what stops the preview drifting. --}}
    <style>{!! \Safi\Atelier\Tokens::css() !!}body{font-family:var(--atelier-font-sans)}</style>
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
