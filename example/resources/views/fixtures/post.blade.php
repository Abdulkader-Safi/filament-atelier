{{-- A host app's own route, nothing to do with Atelier's pages. Used by
     StructuredDataTest to prove a blog post can share the site's graph. --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>

    @include('atelier::partials.schema', ['nodes' => [[
        '@type' => 'Article',
        '@id' => url()->current().'#article',
        'headline' => $title,
        'datePublished' => '2026-08-01T09:00:00+00:00',
        'author' => ['@type' => 'Person', 'name' => 'Abdulkader Safi'],
        'publisher' => ['@id' => \Safi\Atelier\Schema\StructuredData::siteId('organization')],
    ]]])
</head>
<body>
    <h1>{{ $title }}</h1>
</body>
</html>
