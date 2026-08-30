{{--
    One row, recursing once into `children` (the editor and the renderer
    only ever build one level deep). Each item is a single <li>, drag handle
    and all, because SortableJS needs one element per draggable, not the
    row and its children list as separate siblings.

    Kept deliberately sparse after the first cut: label only, no URL in the
    row, edit opens a Filament action modal rather than expanding the row in
    place. Matches Shopify's own menu-item list, checked on Mobbin: a drag
    handle, the label, "Edit" and "Delete", nothing else.
--}}
<li
    data-item-id="{{ $item['id'] }}"
    wire:key="menu-row-{{ $item['id'] }}"
    class="border-t border-gray-100 first:border-t-0 dark:border-white/5"
>
    <div @class([
        'group flex items-center gap-1 py-1.5 pe-2',
        'bg-gray-50/60 ps-8 dark:bg-white/[0.02]' => $isChild,
        'ps-1' => ! $isChild,
    ])>
        <x-filament::icon
            icon="heroicon-m-bars-2"
            data-sortable-handle
            class="h-4 w-4 shrink-0 cursor-grab text-gray-300 active:cursor-grabbing dark:text-gray-600"
        />

        <div class="flex shrink-0 flex-col">
            <button type="button" wire:click="move('{{ $item['id'] }}', -1)" title="Move up"
                class="rounded p-0.5 text-gray-300 hover:text-gray-700 dark:hover:text-white">
                <x-filament::icon icon="heroicon-m-chevron-up" class="h-3 w-3" />
            </button>
            <button type="button" wire:click="move('{{ $item['id'] }}', 1)" title="Move down"
                class="rounded p-0.5 text-gray-300 hover:text-gray-700 dark:hover:text-white">
                <x-filament::icon icon="heroicon-m-chevron-down" class="h-3 w-3" />
            </button>
        </div>

        <span class="min-w-0 flex-1 truncate px-2 text-sm font-medium text-gray-950 dark:text-white">
            {{ \Safi\Atelier\Models\Menu::label($item, app()->getLocale()) ?: 'Untitled' }}
        </span>

        <div class="flex shrink-0 items-center gap-3 pe-1 text-xs font-medium opacity-0 transition group-hover:opacity-100">
            <button type="button" wire:click="mountAction('editItem', { id: '{{ $item['id'] }}' })"
                class="text-gray-500 hover:text-gray-900 hover:underline dark:text-gray-400 dark:hover:text-white">
                Edit
            </button>
            <button type="button" wire:click="mountAction('deleteItem', { id: '{{ $item['id'] }}' })"
                class="text-gray-500 hover:text-red-600 hover:underline dark:text-gray-400 dark:hover:text-red-400">
                Delete
            </button>
        </div>
    </div>

    @unless ($isChild)
        @if ($this->canNest())
            {{-- Always rendered, even with no children yet: an item with
                 nothing nested still needs a drop target to gain a first
                 one. Collapses to a thin strip when empty rather than
                 disappearing, so it stays discoverable. --}}
            <ul
                data-sortable-list
                data-parent-id="{{ $item['id'] }}"
                @class([
                    'min-h-2',
                    'border-t border-gray-100 dark:border-white/5' => ! empty($item['children']),
                ])
            >
                @foreach ($item['children'] ?? [] as $child)
                    @include('atelier::filament.pages.partials.menu-item-row', ['item' => $child, 'isChild' => true])
                @endforeach
            </ul>

            <div class="border-t border-gray-100 py-1.5 ps-8 dark:border-white/5">
                <button type="button" wire:click="addItem('{{ $item['id'] }}')"
                    class="text-xs text-gray-400 hover:text-gray-700 dark:hover:text-white">
                    + Add a sub-item
                </button>
            </div>
        @endif
    @endunless
</li>
