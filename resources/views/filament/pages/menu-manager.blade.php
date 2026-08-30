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
            @forelse ($tree as $item)
                @include('atelier::filament.pages.partials.menu-item-row', ['item' => $item, 'isChild' => false])
            @empty
                <p class="p-6 text-center text-sm text-gray-400">Nothing here yet. Add one below.</p>
            @endforelse
        </div>

        <div>
            <x-filament::button size="sm" icon="heroicon-m-plus" color="gray" wire:click="addItem">
                Add a custom link
            </x-filament::button>
        </div>
    @endif
</x-filament-panels::page>
