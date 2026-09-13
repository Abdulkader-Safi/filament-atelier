{{--
    The generated landing page at /services and /ar/خدمات, rendered inside the
    configured Atelier layout so it gets the same shell and stylesheet as
    every other page.

    It receives $pages (published services), $type (the class) and $locale.
    Build a real page at the same slug and that page wins instead, which is
    what you want the moment the client asks for copy above the list.
--}}
<section class="px-6 py-16">
    <div class="mx-auto max-w-5xl">
        <h1 class="text-3xl font-semibold tracking-tight text-balance">{{ $type::pluralLabel() }}</h1>

        @if ($pages->isEmpty())
            <p class="mt-6 text-neutral-600">Nothing published yet.</p>
        @else
            <div class="mt-10 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($pages as $page)
                    @include($type::cardView(), ['page' => $page, 'locale' => $locale])
                @endforeach
            </div>
        @endif
    </div>
</section>
