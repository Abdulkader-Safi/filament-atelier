{{--
    Everything in the head that depends on the page: title, description,
    canonical, hreflang, Open Graph and Twitter.

    Include this from your own layout if you replace `atelier.layout`:

        @include('atelier::partials.meta')

    It emits the `<title>` too, so don't write your own alongside it.

    All four variables are optional and are passed by Atelier's controllers.
    A layout rendered outside them still works, it just has less to say.
--}}
@php
    $locales = config('atelier.locales', []);
    $page = $page ?? null;
    $locale = $locale ?? app()->getLocale();
    $isPreview = $preview ?? false;
    $title = $title ?? ($page?->metaTitle($locale) ?? config('app.name'));
@endphp
<title>{{ $title }}</title>

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
