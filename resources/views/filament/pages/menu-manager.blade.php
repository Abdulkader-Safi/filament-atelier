<x-filament-panels::page>
    @if (empty($this->locationOptions))
        <x-filament::section>
            No menu locations are registered. Add one with
            <code>AtelierPlugin::make()->menuLocations([...])</code> in your panel provider.
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

        {{ $this->form }}
    @endif
</x-filament-panels::page>
