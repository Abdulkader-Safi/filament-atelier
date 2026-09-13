@php
    // The tiers belong to the page, not to the block. On a page with none
    // (the homepage, say) the block renders nothing rather than an empty
    // table, so a client can drop it anywhere without breaking a layout.
    $tiers = collect($page?->data('tiers') ?? [])->filter(fn ($tier) => filled($tier['name'] ?? null));
@endphp

@if ($tiers->isNotEmpty())
    <section {{ $shared->class(['px-6 py-20']) }} id="pricing">
        <div class="mx-auto max-w-5xl">
            <div class="max-w-2xl">
                @if ($heading = $attributes['heading'] ?? null)
                    <h2 class="text-3xl font-semibold tracking-tight text-balance text-slate-900">{{ $heading }}</h2>
                @endif

                @if ($sub = $attributes['subheading'] ?? null)
                    <p class="mt-3 text-pretty text-slate-600">{{ $sub }}</p>
                @endif
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 lg:grid-cols-3">
                @foreach ($tiers as $tier)
                    @php
                        $isPick = (bool) ($tier['recommended'] ?? false);
                        $features = collect(preg_split('/\r\n|\r|\n/', (string) ($tier['features'] ?? '')))
                            ->map(fn ($line) => trim($line))
                            ->filter();
                    @endphp

                    <div @class([
                        'relative flex flex-col rounded-2xl border p-8',
                        'border-slate-200 bg-white' => ! $isPick,
                        'border-teal-600 bg-teal-50/60 shadow-lg shadow-teal-900/5 lg:-mt-4 lg:mb-[-1rem]' => $isPick,
                    ])>
                        @if ($isPick)
                            <span class="absolute -top-3 start-8 rounded-full bg-teal-600 px-3 py-1 text-xs font-semibold tracking-wide text-white uppercase">
                                Most booked
                            </span>
                        @endif

                        <h3 class="text-lg font-semibold text-slate-900">{{ $tier['name'] }}</h3>

                        <p class="mt-4 flex items-baseline gap-1">
                            <span class="text-sm font-medium text-slate-500">AED</span>
                            <span class="text-4xl font-semibold tracking-tight text-slate-900">{{ number_format((float) ($tier['price'] ?? 0)) }}</span>
                            @if ($unit = $tier['unit'] ?? null)
                                <span class="text-sm text-slate-500">/ {{ $unit }}</span>
                            @endif
                        </p>

                        @if ($features->isNotEmpty())
                            <ul class="mt-6 space-y-3 text-sm text-slate-600">
                                @foreach ($features as $feature)
                                    <li class="flex gap-3">
                                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-teal-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 1 1 1.4-1.4l3.8 3.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        {{-- Scrolls to the request form on the same page and
                             preselects this tier. No JS framework involved. --}}
                        <a href="#request"
                           data-tier="{{ $tier['name'] }}"
                           @class([
                               'mt-8 inline-flex justify-center rounded-lg px-4 py-2.5 text-sm font-semibold transition',
                               'bg-teal-600 text-white hover:bg-teal-700' => $isPick,
                               'bg-slate-900 text-white hover:bg-slate-700' => ! $isPick,
                           ])>
                            Choose {{ $tier['name'] }}
                        </a>
                    </div>
                @endforeach
            </div>

            @if ($note = $attributes['note'] ?? null)
                <p class="mt-8 text-sm text-slate-500">{{ $note }}</p>
            @endif
        </div>
    </section>

    <script>
        // Clicking a tier picks it in the form below. Progressive: without
        // this the link still jumps to the form, with the default selected.
        document.querySelectorAll('[data-tier]').forEach((link) => {
            link.addEventListener('click', () => {
                const select = document.querySelector('[data-request-option]')
                if (select) select.value = link.dataset.tier
            })
        })
    </script>
@endif
