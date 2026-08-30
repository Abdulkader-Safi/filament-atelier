<x-filament-panels::page>
    @if (empty($this->locationOptions))
        <x-filament::section>
            No menu locations are registered. Add one to <code>config('atelier.menus')</code>,
            the same place <code>locales</code> lives.
        </x-filament::section>
    @else
        <div class="max-w-xs">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="location">
                    @foreach ($this->locationOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>

        <div class="fi-section rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            @if (empty($tree))
                <p class="p-6 text-center text-sm text-gray-400">Nothing here yet. Add one below.</p>
            @else
                <ul id="atelier-menu-root" data-sortable-list data-parent-id="" x-data="atelierMenuSortable()">
                    @foreach ($tree as $item)
                        @include('atelier::filament.pages.partials.menu-item-row', ['item' => $item, 'isChild' => false])
                    @endforeach
                </ul>
            @endif
        </div>

        <div>
            <x-filament::button size="sm" icon="heroicon-m-plus" color="gray" wire:click="addItem">
                Add a custom link
            </x-filament::button>
        </div>
    @endif

    @script
    <script>
        // Drag reorder, including dragging an item between the top-level
        // list and a parent's children list (main becomes sub-item, and
        // back). SortableJS is already loaded globally by Filament's own
        // support package (window.Sortable), so this borrows it rather than
        // shipping a second copy.
        //
        // One Alpine component per sortable <ul>, not one global observer
        // watching the whole tree: a global MutationObserver re-running
        // setup on every mutation fired mid-drag too, on SortableJS's own
        // internal DOM shuffling, and a sync() that read the DOM while a
        // drag was still resolving lost a sibling entirely. Alpine's own
        // per-element lifecycle only (re)initialises a list when Livewire
        // actually replaces that element, which is what a plugin solving
        // the exact same problem (notebrainslab/filament-menu-manager)
        // does too, checked against its source rather than guessed at.
        function extractTree(container) {
            return Array.from(container.querySelectorAll(':scope > [data-item-id]')).map((el) => {
                const nested = el.querySelector(':scope > [data-sortable-list]');

                return { id: el.dataset.itemId, children: nested ? extractTree(nested) : [] };
            });
        }

        Alpine.data('atelierMenuSortable', () => ({
            init() {
                this.$nextTick(() => {
                    if (this.$el._atelierSortable) return;

                    this.$el._atelierSortable = Sortable.create(this.$el, {
                        group: 'atelier-menu-tree',
                        draggable: '[data-item-id]',
                        handle: '[data-sortable-handle]',
                        animation: 150,
                        // Without these the browser's own HTML5 drag API
                        // runs the gesture: a static ghost icon under the
                        // cursor, not the row following it, which reads as
                        // laggy even though nothing is actually slow.
                        // forceFallback makes SortableJS simulate the whole
                        // drag itself instead, the smooth-tracking behaviour
                        // most drag-and-drop UIs actually want; fallbackOnBody
                        // keeps the dragged row's clone positioned correctly
                        // while it crosses between lists, which native drag
                        // and a fallback confined to its own list both get
                        // wrong. The plugin this was checked against uses
                        // both for the same reason.
                        forceFallback: true,
                        fallbackOnBody: true,
                        onEnd: () => this.sync(),
                    });
                });
            },

            /** Walks from the one fixed root, not just this list, since a drop can move an item between levels. */
            sync() {
                const root = document.getElementById('atelier-menu-root');

                if (! root) return;

                const top = [];
                const children = {};

                extractTree(root).forEach((node) => {
                    top.push(node.id);

                    if (node.children.length) {
                        children[node.id] = node.children.map((child) => child.id);
                    }
                });

                this.$wire.call('reorderTree', top, children);
            },
        }));
    </script>
    @endscript
</x-filament-panels::page>
