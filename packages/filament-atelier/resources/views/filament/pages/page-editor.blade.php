@php
    $widths = config('atelier.preview.widths');
    $locales = config('atelier.locales');
@endphp

<div
    class="atelier -m-4 flex h-[calc(100vh-4rem)] flex-col sm:-m-6"
    x-data="atelierEditor(@js($this->previewUrl))"
>
    {{-- Toolbar --}}
    <div class="flex shrink-0 items-center gap-3 border-b border-gray-200 bg-white px-4 py-2 dark:border-white/10 dark:bg-gray-900">
        <span class="text-sm font-medium">{{ $this->page->title }}</span>

        @if ($this->page->hasUnpublishedChanges())
            <span class="rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-800">Unpublished changes</span>
        @else
            <span class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-white/10 dark:text-gray-300">
                {{ ucfirst($this->page->status) }}
            </span>
        @endif

        <div class="ms-auto flex items-center gap-1">
            {{-- Locale --}}
            @foreach ($locales as $code => $locale)
                <button
                    type="button"
                    wire:click="setLocale('{{ $code }}')"
                    @class([
                        'rounded px-2 py-1 text-xs font-medium',
                        'bg-primary-600 text-white' => $this->locale === $code,
                        'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/10' => $this->locale !== $code,
                    ])
                >{{ strtoupper($code) }}</button>
            @endforeach

            <span class="mx-2 h-4 w-px bg-gray-200 dark:bg-white/10"></span>

            {{-- Width --}}
            @foreach (['desktop' => 'Desktop', 'tablet' => 'Tablet', 'mobile' => 'Mobile'] as $key => $label)
                <button
                    type="button"
                    wire:click="setWidth('{{ $key }}')"
                    @class([
                        'rounded px-2 py-1 text-xs font-medium',
                        'bg-gray-900 text-white dark:bg-white dark:text-gray-900' => $this->width === $key,
                        'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/10' => $this->width !== $key,
                    ])
                >{{ $label }}</button>
            @endforeach

            <span class="mx-2 h-4 w-px bg-gray-200 dark:bg-white/10"></span>

            <x-filament::button size="xs" color="gray" wire:click="publish">Publish</x-filament::button>
        </div>
    </div>

    <div class="flex min-h-0 flex-1">
        {{-- Left: sections --}}
        <aside class="flex w-64 shrink-0 flex-col border-e border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
            <div class="flex-1 overflow-y-auto p-2">
                @forelse ($this->sections as $i => $section)
                    <div
                        wire:key="row-{{ $section['id'] }}"
                        @class([
                            'group mb-1 flex items-center gap-2 rounded-lg px-2 py-2 text-sm',
                            'bg-primary-50 text-primary-700 dark:bg-primary-500/10' => $this->selectedId === $section['id'],
                            'hover:bg-gray-50 dark:hover:bg-white/5' => $this->selectedId !== $section['id'],
                            'opacity-40' => $section['hidden'],
                        ])
                    >
                        <button type="button" class="flex min-w-0 flex-1 items-center gap-2 text-start"
                                wire:click="selectBlock('{{ $section['id'] }}')">
                            <x-filament::icon :icon="$section['icon']" class="h-4 w-4 shrink-0 text-gray-400" />
                            <span class="truncate">{{ $section['label'] }}</span>
                        </button>

                        <div class="flex shrink-0 items-center opacity-0 transition group-hover:opacity-100">
                            <button type="button" title="Move up" class="px-1 text-gray-400 hover:text-gray-700"
                                    wire:click="move('{{ $section['id'] }}', -1)">&uarr;</button>
                            <button type="button" title="Move down" class="px-1 text-gray-400 hover:text-gray-700"
                                    wire:click="move('{{ $section['id'] }}', 1)">&darr;</button>
                            <button type="button" title="Duplicate" class="px-1 text-gray-400 hover:text-gray-700"
                                    wire:click="duplicateBlock('{{ $section['id'] }}')">&#43;</button>
                            <button type="button" title="{{ $section['hidden'] ? 'Show' : 'Hide' }}"
                                    class="px-1 text-gray-400 hover:text-gray-700"
                                    wire:click="toggleHidden('{{ $section['id'] }}')">{{ $section['hidden'] ? '○' : '●' }}</button>
                            <button type="button" title="Delete" class="px-1 text-gray-400 hover:text-red-600"
                                    wire:click="deleteBlock('{{ $section['id'] }}')"
                                    wire:confirm="Delete this section?">&times;</button>
                        </div>
                    </div>
                @empty
                    <p class="p-4 text-center text-sm text-gray-500">No sections yet.</p>
                @endforelse
            </div>

            {{-- Picker --}}
            <div class="border-t border-gray-200 p-2 dark:border-white/10" x-data="{ open: false }">
                <x-filament::button size="sm" class="w-full" x-on:click="open = !open">Add section</x-filament::button>

                <div x-show="open" x-cloak x-on:click.outside="open = false" class="mt-2 space-y-3">
                    @foreach ($this->picker as $category => $blocks)
                        <div>
                            <p class="px-1 pb-1 text-xs font-medium uppercase tracking-wide text-gray-400">{{ $category }}</p>
                            @foreach ($blocks as $block)
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-2 rounded px-2 py-1.5 text-start text-sm hover:bg-gray-50 dark:hover:bg-white/5"
                                    wire:click="addBlock('{{ $block['type'] }}')"
                                    x-on:click="open = false"
                                >
                                    <x-filament::icon :icon="$block['icon']" class="h-4 w-4 text-gray-400" />
                                    {{ $block['label'] }}
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>

        {{-- Middle: preview --}}
        <div class="flex min-w-0 flex-1 justify-center overflow-auto bg-gray-100 p-4 dark:bg-gray-950">
            <iframe
                x-ref="preview"
                src="{{ $this->previewUrl }}"
                class="h-full w-full rounded-lg border border-gray-200 bg-white shadow-sm transition-[max-width] dark:border-white/10"
                @if ($widths[$this->width] ?? null)
                    style="max-width: {{ $widths[$this->width] }}px"
                @endif
                title="Page preview"
            ></iframe>
        </div>

        {{-- Right: settings --}}
        <aside class="w-80 shrink-0 overflow-y-auto border-s border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
            @if ($this->selectedId)
                {{ $this->form }}
            @else
                <p class="text-sm text-gray-500">Select a section to edit it.</p>
            @endif
        </aside>
    </div>
</div>

@script
<script>
    Alpine.data('atelierEditor', (previewUrl) => ({
        url: previewUrl,

        init() {
            // Livewire tells us the draft changed. Fetch the preview and swap
            // only the canvas, so scroll position and the stylesheet survive.
            this.$wire.on('atelier-refresh', () => this.refresh());
        },

        async refresh() {
            const frame = this.$refs.preview;
            if (!frame?.contentDocument) return;

            const current = frame.contentDocument.querySelector('[data-atelier-canvas]');
            if (!current) return frame.contentWindow.location.reload();

            const html = await (await fetch(this.url, { headers: { 'X-Atelier-Preview': '1' } })).text();
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
