<x-layouts::app :title="__('Riders')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading>{{ __('Riders Management') }}</flux:heading>
            @canAs('rider.create')
                <flux:button variant="primary" :href="route('riders.create')" wire:navigate icon="plus">
                    {{ __('Add Rider') }}
                </flux:button>
            @endcanAs
        </div>

        <livewire:riders.rider-list />
    </div>
</x-layouts::app>
