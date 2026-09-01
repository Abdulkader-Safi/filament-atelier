@php
    $widths = config('atelier.preview.widths');
    $locales = config('atelier.locales');
    $selected = $this->selectedSection;
@endphp

<div
    class="atelier flex h-dvh w-full flex-col overflow-hidden bg-gray-100 dark:bg-gray-950"
    x-data="atelierEditor()"
>
    {{-- ── Toolbar ─────────────────────────────────────────────────── --}}
    <header class="flex h-12 shrink-0 items-center gap-2 border-b border-gray-200 bg-white px-3 dark:border-white/10 dark:bg-gray-900">
        <a
            href="{{ $this->backUrl }}"
            class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5"
            title="Back to pages"
        >
            <x-filament::icon icon="heroicon-m-arrow-left" class="h-4 w-4" />
        </a>

        <span class="max-w-48 truncate text-sm font-medium">{{ $this->page->title }}</span>

        @if ($this->page->hasUnpublishedChanges())
            <span class="rounded-md bg-amber-50 px-1.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-400/10 dark:text-amber-400">
                Unpublished changes
            </span>
        @elseif ($this->page->status === 'published')
            <span class="rounded-md bg-green-50 px-1.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-400/10 dark:text-green-400">
                Published
            </span>
        @else
            <span class="rounded-md bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-white/10 dark:text-gray-400">
                Draft
            </span>
        @endif

        {{-- Device widths, centred --}}
        <div class="absolute left-1/2 flex -translate-x-1/2 items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-white/5">
            @foreach ([
                'desktop' => 'heroicon-m-computer-desktop',
                'tablet' => 'heroicon-m-device-tablet',
                'mobile' => 'heroicon-m-device-phone-mobile',
            ] as $key => $icon)
                <button
                    type="button"
                    wire:click="setWidth('{{ $key }}')"
                    title="{{ ucfirst($key) }}"
                    @class([
                        'rounded-md p-1.5 transition',
                        'bg-white text-gray-950 shadow-sm dark:bg-white/10 dark:text-white' => $this->width === $key,
                        'text-gray-500 hover:text-gray-900 dark:hover:text-white' => $this->width !== $key,
                    ])
                >
                    <x-filament::icon :icon="$icon" class="h-4 w-4" />
                </button>
            @endforeach
        </div>

        <div class="ms-auto flex items-center gap-2">
            @if (count($locales) > 1)
                <div class="flex items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-white/5">
                    @foreach ($locales as $code => $locale)
                        <button
                            type="button"
                            wire:click="setLocale('{{ $code }}')"
                            title="{{ $locale['label'] }}"
                            @class([
                                'rounded-md px-2 py-1 text-xs font-medium transition',
                                'bg-white text-gray-950 shadow-sm dark:bg-white/10 dark:text-white' => $this->locale === $code,
                                'text-gray-500 hover:text-gray-900 dark:hover:text-white' => $this->locale !== $code,
                            ])
                        >{{ strtoupper($code) }}</button>
                    @endforeach
                </div>
            @endif

            <span class="h-5 w-px bg-gray-200 dark:bg-white/10"></span>

            <a
                href="{{ $this->shareUrl }}"
                target="_blank"
                class="rounded-lg p-1.5 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-white/5 dark:hover:text-white"
                title="Open preview in a new tab"
            >
                <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="h-4 w-4" />
            </a>

            <x-filament::button size="xs" wire:click="publish" wire:loading.attr="disabled">
                Publish
            </x-filament::button>
        </div>
    </header>

    <div class="flex min-h-0 flex-1">
        {{-- ── Icon rail ───────────────────────────────────────────── --}}
        <nav class="flex w-12 shrink-0 flex-col items-center gap-1 border-e border-gray-200 bg-white py-2 dark:border-white/10 dark:bg-gray-900">
            <button
                type="button"
                wire:click="closeInspector"
                title="Sections"
                @class([
                    'rounded-lg p-2 transition',
                    'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400' => ! $selected,
                    'text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5' => (bool) $selected,
                ])
            >
                <x-filament::icon icon="heroicon-o-square-3-stack-3d" class="h-5 w-5" />
            </button>

            <button
                type="button"
                @class([
                    'rounded-lg p-2 transition',
                    'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400' => (bool) $selected,
                    'text-gray-400' => ! $selected,
                ])
                title="{{ $selected ? 'Section settings' : 'Select a section first' }}"
                @disabled(! $selected)
            >
                <x-filament::icon icon="heroicon-o-adjustments-horizontal" class="h-5 w-5" />
            </button>
        </nav>

        {{-- ── One panel, two states ───────────────────────────────── --}}
        <aside class="flex w-72 shrink-0 flex-col border-e border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
            @if ($selected)
                {{-- Inspector --}}
                <div class="flex h-11 shrink-0 items-center gap-1 border-b border-gray-200 px-2 dark:border-white/10">
                    <button
                        type="button"
                        wire:click="closeInspector"
                        class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5"
                        title="Back to sections"
                    >
                        <x-filament::icon icon="heroicon-m-chevron-left" class="h-4 w-4" />
                    </button>

                    <x-filament::icon :icon="$selected['icon']" class="h-4 w-4 shrink-0 text-gray-400" />
                    <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $selected['type'] }}</span>

                    <div class="flex shrink-0 items-center">
                        <button type="button" wire:click="move('{{ $selected['id'] }}', -1)" title="Move up"
                                class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5">
                            <x-filament::icon icon="heroicon-m-arrow-up" class="h-3.5 w-3.5" />
                        </button>
                        <button type="button" wire:click="move('{{ $selected['id'] }}', 1)" title="Move down"
                                class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5">
                            <x-filament::icon icon="heroicon-m-arrow-down" class="h-3.5 w-3.5" />
                        </button>
                        <button type="button" wire:click="duplicateBlock('{{ $selected['id'] }}')" title="Duplicate"
                                class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5">
                            <x-filament::icon icon="heroicon-m-document-duplicate" class="h-3.5 w-3.5" />
                        </button>
                        <button type="button" wire:click="toggleHidden('{{ $selected['id'] }}')"
                                title="{{ $selected['hidden'] ? 'Show' : 'Hide' }}"
                                class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5">
                            <x-filament::icon :icon="$selected['hidden'] ? 'heroicon-m-eye-slash' : 'heroicon-m-eye'" class="h-3.5 w-3.5" />
                        </button>
                        <button type="button" wire:click="deleteBlock('{{ $selected['id'] }}')"
                                wire:confirm="Delete this section?" title="Delete"
                                class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-red-600 dark:hover:bg-white/5">
                            <x-filament::icon icon="heroicon-m-trash" class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-3">
                    {{ $this->form }}
                </div>
            @else
                {{-- Section list --}}
                <div class="flex h-11 shrink-0 items-center justify-between border-b border-gray-200 px-3 dark:border-white/10">
                    <span class="text-sm font-medium">Sections</span>
                    <span class="text-xs text-gray-400">{{ count($this->sections) }}</span>
                </div>

                <div class="flex-1 overflow-y-auto p-2">
                    @forelse ($this->sections as $section)
                        <div
                            wire:key="row-{{ $section['id'] }}"
                            @class([
                                'group mb-1 flex items-center gap-2 rounded-lg border border-transparent px-2 py-2 text-sm transition',
                                'hover:border-gray-200 hover:bg-gray-50 dark:hover:border-white/10 dark:hover:bg-white/5',
                                'opacity-50' => $section['hidden'],
                            ])
                        >
                            <x-filament::icon :icon="$section['icon']" class="h-4 w-4 shrink-0 text-gray-400" />

                            <button
                                type="button"
                                wire:click="selectBlock('{{ $section['id'] }}')"
                                class="min-w-0 flex-1 truncate text-start"
                            >{{ $section['label'] }}</button>

                            <div class="flex shrink-0 items-center opacity-0 transition group-hover:opacity-100">
                                <button type="button" wire:click="move('{{ $section['id'] }}', -1)" title="Move up"
                                        class="rounded p-0.5 text-gray-400 hover:text-gray-800 dark:hover:text-white">
                                    <x-filament::icon icon="heroicon-m-arrow-up" class="h-3.5 w-3.5" />
                                </button>
                                <button type="button" wire:click="move('{{ $section['id'] }}', 1)" title="Move down"
                                        class="rounded p-0.5 text-gray-400 hover:text-gray-800 dark:hover:text-white">
                                    <x-filament::icon icon="heroicon-m-arrow-down" class="h-3.5 w-3.5" />
                                </button>
                                <button type="button" wire:click="selectBlock('{{ $section['id'] }}')" title="Settings"
                                        class="rounded p-0.5 text-gray-400 hover:text-gray-800 dark:hover:text-white">
                                    <x-filament::icon icon="heroicon-m-pencil-square" class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>
                    @empty
                        <p class="px-3 py-8 text-center text-sm text-gray-400">
                            Nothing here yet. Add your first section below.
                        </p>
                    @endforelse
                </div>

                <div class="shrink-0 border-t border-gray-200 p-2 dark:border-white/10" x-data="{ open: false, q: '' }">
                    <x-filament::button
                        size="sm"
                        icon="heroicon-m-plus"
                        class="w-full"
                        x-on:click="open = ! open; q = ''; if (open) $nextTick(() => $refs.search.focus())"
                    >
                        Add section
                    </x-filament::button>

                    <div x-show="open" x-cloak x-on:click.outside="open = false" class="mt-2">
                        <input
                            type="search"
                            x-ref="search"
                            x-model="q"
                            placeholder="Search sections"
                            x-on:keydown.escape.stop="q ? q = '' : open = false"
                            class="w-full rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm placeholder:text-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5"
                        />

                        <div class="mt-2 max-h-[45vh] space-y-3 overflow-y-auto">
                            @foreach ($this->picker as $category => $blocks)
                                <div x-show="{{ Js::from(array_column($blocks, 'label')) }}.some((l) => l.toLowerCase().includes(q.trim().toLowerCase()))">
                                    <p class="px-1 pb-1 text-xs font-medium uppercase tracking-wide text-gray-400">{{ $category }}</p>
                                    @foreach ($blocks as $block)
                                        <button
                                            type="button"
                                            class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-start text-sm hover:bg-gray-50 dark:hover:bg-white/5"
                                            wire:click="addBlock('{{ $block['type'] }}')"
                                            x-on:click="open = false"
                                            x-show="{{ Js::from($block['label']) }}.toLowerCase().includes(q.trim().toLowerCase())"
                                        >
                                            <x-filament::icon :icon="$block['icon']" class="h-4 w-4 shrink-0 text-gray-400" />
                                            {{ $block['label'] }}
                                        </button>
                                    @endforeach
                                </div>
                            @endforeach

                            <p
                                class="px-1 py-6 text-center text-sm text-gray-400"
                                x-show="q.trim() && ! {{ Js::from(collect($this->picker)->flatten(1)->pluck('label')->all()) }}.some((l) => l.toLowerCase().includes(q.trim().toLowerCase()))"
                            >No section matches "<span x-text="q"></span>".</p>
                        </div>
                    </div>
                </div>
            @endif
        </aside>

        {{-- ── Canvas ──────────────────────────────────────────────── --}}
        <div class="flex min-w-0 flex-1 justify-center overflow-auto p-4">
            <iframe
                x-ref="preview"
                src="{{ $this->previewUrl }}"
                class="h-full w-full rounded-xl border border-gray-200 bg-white shadow-sm transition-[max-width] duration-200 dark:border-white/10"
                @if ($widths[$this->width] ?? null)
                    style="max-width: {{ $widths[$this->width] }}px"
                @endif
                title="Page preview"
            ></iframe>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('atelierEditor', () => ({
        init() {
            // Livewire says the draft changed. Fetch the preview and swap only
            // the canvas, so scroll position and the stylesheet survive.
            this.$wire.on('atelier-refresh', () => this.refresh());

            // Clicking a section in the preview selects it in the sidebar.
            window.addEventListener('message', (e) => {
                if (e.data?.atelier === 'select') this.$wire.selectBlock(e.data.id);
            });
        },

        async refresh() {
            const frame = this.$refs.preview;
            if (!frame?.contentDocument) return;

            const current = frame.contentDocument.querySelector('[data-atelier-canvas]');
            if (!current) return frame.contentWindow.location.reload();

            // Read the iframe's own src, not a cached URL. Livewire already
            // morphed it to the current locale by the time this event fires.
            const html = await (await fetch(frame.src, { headers: { 'X-Atelier-Preview': '1' } })).text();
            const next = new DOMParser()
                .parseFromString(html, 'text/html')
                .querySelector('[data-atelier-canvas]');

            if (!next) return;

            const y = frame.contentWindow.scrollY;
            current.innerHTML = next.innerHTML;
            frame.contentWindow.scrollTo(0, y);
        },
    }));
</script>
@endscript
