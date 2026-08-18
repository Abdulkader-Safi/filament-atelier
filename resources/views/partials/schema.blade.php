{{--
    Structured data for a page Atelier does not own: a blog post, a services
    record, anything with its own model and its own route.

    Gives you the organisation and the site, so a post's publisher is the same
    node as every other page's rather than a second copy that drifts, and adds
    whatever you pass:

        @include('atelier::partials.schema', ['nodes' => [[
            '@type' => 'Article',
            '@id' => url()->current().'#article',
            'headline' => $post->title,
            'datePublished' => $post->published_at?->toAtomString(),
            'author' => ['@type' => 'Person', 'name' => $post->author->name],
            'publisher' => ['@id' => \Safi\Atelier\Schema\StructuredData::siteId('organization')],
        ]]])

    Empty values are dropped, nodes sharing an `@id` merge, and the encoding is
    safe inside a script block, all the same way an Atelier page's own graph is
    built.
--}}
@php
    $graph = \Safi\Atelier\Schema\StructuredData::for($nodes ?? [], $locale ?? null);
@endphp
@unless ($graph->isEmpty())
    <script type="application/ld+json">{!! $graph->toJson() !!}</script>
@endunless
