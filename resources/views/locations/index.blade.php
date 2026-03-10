<x-layouts::app :title="__('Locations')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading>{{ __('Locations Management') }}</flux:heading>
            @canAs('location.create')
                <flux:button variant="primary" :href="route('locations.create')" wire:navigate icon="plus">
                    {{ __('Add Location') }}
                </flux:button>
            @endcanAs
        </div>

        <livewire:locations.location-list />
    </div>
</x-layouts::app>
