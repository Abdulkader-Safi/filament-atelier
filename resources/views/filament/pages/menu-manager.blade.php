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

        <div
            x-data="atelierMenuTree()"
            class="fi-section rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900"
        >
            @if (empty($tree))
                <p class="p-6 text-center text-sm text-gray-400">Nothing here yet. Add one below.</p>
            @else
                <ul data-sortable-list data-parent-id="">
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
        // Livewire re-renders the tree on every add, delete, edit save and
        // drop, which can replace the list elements a Sortable instance was
        // attached to. Rather than guess which Livewire lifecycle hook
        // fires when, a MutationObserver on the whole component reacts to
        // the DOM actually changing and (re-)initialises anything new,
        // skipping elements already set up via the _atelierSortable flag.
        Alpine.data('atelierMenuTree', () => ({
            init() {
                this.mount();

                new MutationObserver(() => this.mount())
                    .observe(this.$el, { childList: true, subtree: true });
            },

            mount() {
                this.$el.querySelectorAll('[data-sortable-list]').forEach((list) => {
                    if (list._atelierSortable) return;

                    list._atelierSortable = Sortable.create(list, {
                        group: 'atelier-menu-tree',
                        draggable: '[data-item-id]',
                        handle: '[data-sortable-handle]',
                        animation: 150,
                        onEnd: () => this.sync(),
                    });
                });
            },

            /** Reads every list's current order straight from the DOM and sends the whole shape in one call. */
            sync() {
                const top = [];
                const children = {};

                this.$el.querySelectorAll('[data-sortable-list]').forEach((list) => {
                    const ids = Array.from(list.children)
                        .filter((el) => el.matches('[data-item-id]'))
                        .map((el) => el.dataset.itemId);

                    if (list.dataset.parentId) {
                        children[list.dataset.parentId] = ids;
                    } else {
                        top.push(...ids);
                    }
                });

                this.$wire.call('reorderTree', top, children);
            },
        }));
    </script>
    @endscript
</x-filament-panels::page>
