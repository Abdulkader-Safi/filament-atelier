@php
    // What the page is decides what the form asks. A service offers its
    // tiers, a product its variations and a quantity, anything else is a
    // plain message.
    $kind = $page?->type ?? 'page';

    $options = collect(match ($kind) {
        'service' => $page?->data('tiers') ?? [],
        'product' => $page?->data('variations') ?? [],
        default => [],
    })->filter(fn ($row) => filled($row['name'] ?? null));

    $submitted = session('enquiry');
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
@endphp

<section {{ $shared->class(['px-6 py-20']) }} id="request">
    <div class="mx-auto max-w-3xl">
        @if ($heading = $attributes['heading'] ?? null)
            <h2 class="text-3xl font-semibold tracking-tight text-balance text-slate-900">{{ $heading }}</h2>
        @endif

        @if ($sub = $attributes['subheading'] ?? null)
            <p class="mt-3 text-pretty text-slate-600">{{ $sub }}</p>
        @endif

        @if ($submitted)
            <p class="mt-8 rounded-xl border border-teal-200 bg-teal-50 px-5 py-4 text-teal-900">
                {{ $submitted }}
            </p>
        @endif

        @if ($page === null || ! $page->exists)
            {{-- In the editor preview before the page exists, or on a page
                 this block cannot identify. Shows the shape, posts nothing. --}}
            <p class="mt-8 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-900">
                The form posts once this page is saved.
            </p>
        @else
            <form method="POST" action="{{ route('enquiry.store') }}" class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2">
                @csrf
                <input type="hidden" name="page_id" value="{{ $page->getKey() }}">

                {{-- A field nobody sees and a bot fills in. The controller
                     rejects any request that carries it. --}}
                <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

                @if ($options->isNotEmpty())
                    <label class="block sm:col-span-2">
                        <span class="text-sm font-medium text-slate-900">{{ $kind === 'product' ? 'Which one' : 'Which package' }}</span>
                        <select name="option" data-request-option
                                class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-900 focus:border-teal-600 focus:ring-1 focus:ring-teal-600">
                            @foreach ($options as $option)
                                <option value="{{ $option['name'] }}" @selected(($option['recommended'] ?? false))>
                                    {{ $option['name'] }}
                                    @if (isset($option['price']))
                                        — AED {{ number_format((float) $option['price']) }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </label>
                @endif

                @if ($kind === 'product')
                    <label class="block">
                        <span class="text-sm font-medium text-slate-900">How many</span>
                        <input type="number" name="quantity" value="1" min="1" max="99"
                               class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-teal-600 focus:ring-1 focus:ring-teal-600">
                    </label>
                @endif

                <label class="block">
                    <span class="text-sm font-medium text-slate-900">Your name</span>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-teal-600 focus:ring-1 focus:ring-teal-600">
                    @error('name')<span class="mt-1 block text-sm text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-900">Email</span>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-teal-600 focus:ring-1 focus:ring-teal-600">
                    @error('email')<span class="mt-1 block text-sm text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-900">Phone</span>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-teal-600 focus:ring-1 focus:ring-teal-600">
                </label>

                <label class="block sm:col-span-2">
                    <span class="text-sm font-medium text-slate-900">Anything we should know</span>
                    <textarea name="message" rows="4"
                              class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-teal-600 focus:ring-1 focus:ring-teal-600">{{ old('message') }}</textarea>
                </label>

                <div class="sm:col-span-2">
                    <button type="submit"
                            class="inline-flex rounded-lg bg-teal-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-teal-700">
                        {{ $attributes['button'] ?? 'Send the request' }}
                    </button>

                    @if ($note = $attributes['note'] ?? null)
                        <p class="mt-3 text-sm text-slate-500">{{ $note }}</p>
                    @endif
                </div>
            </form>
        @endif
    </div>
</section>
