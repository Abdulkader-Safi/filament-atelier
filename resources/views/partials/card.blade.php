{{--
    The card the Collection block falls back to when a page type declares no
    cardView(). Title and link, nothing else: this file cannot know what the
    type called its fields, and a block that renders nothing at all reads as
    broken.

    Copy it into your own app as a starting point, then name it from your
    type's cardView().
--}}
<a href="{{ $page->url($locale) ?? '#' }}"
   class="block rounded-lg border border-neutral-200 p-6 transition hover:border-neutral-400">
    <h3 class="font-medium text-neutral-900">{{ $page->title }}</h3>
</a>
