{{--
    The generated landing page at /services and /ar/خدمات, rendered inside the
    configured Atelier layout so it gets the same shell as every other page.

    It receives $pages (published services), $type (the class) and $locale.
    Build a real page at the same slug and that page wins instead, which is
    what this site does: see the Services page in the panel.
--}}
<section class="px-6 py-20">
    <div class="mx-auto max-w-5xl">
        <h1 class="text-4xl font-semibold tracking-tight text-balance text-slate-900">{{ $type::pluralLabel() }}</h1>
        <p class="mt-3 max-w-2xl text-pretty text-slate-600">
            Everything we clean, and what it costs. Pick one to see its packages.
        </p>

        @if ($pages->isEmpty())
            <p class="mt-10 text-slate-600">Nothing published yet.</p>
        @else
            <div class="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($pages as $page)
                    @include($type::cardView(), ['page' => $page, 'locale' => $locale])
                @endforeach
            </div>
        @endif
    </div>
</section>
